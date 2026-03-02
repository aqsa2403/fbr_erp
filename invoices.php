<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

// AJAX Handler for Invoice Details
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_details' && isset($_GET['invoiceId'])) {
    header('Content-Type: application/json');
    $invId = $_GET['invoiceId'];

    // Fetch Master
    $mStmt = $db->prepare("SELECT m.*, b.BranchName FROM DI_Invoice_Master m JOIN DI_Branch b ON m.BranchID = b.BranchID WHERE m.InvoiceID = ?");
    $mStmt->execute([$invId]);
    $master = $mStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Details
    $dStmt = $db->prepare("SELECT d.*, u.UOMCode FROM DI_Invoice_Detail d LEFT JOIN DI_UOM u ON d.UOMID = u.UOMID WHERE d.InvoiceID = ?");
    $dStmt->execute([$invId]);
    $details = $dStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['master' => $master, 'details' => $details]);
    exit;
}

$branchStmt = $db->query("SELECT BranchID, BranchName FROM DI_Branch WHERE IsActive = 1");
$branches = $branchStmt->fetchAll();

$uomStmt = $db->query("SELECT UOMID, UOMCode, Description FROM DI_UOM WHERE IsActive = 1");
$uoms = $uomStmt->fetchAll();

$sroStmt = $db->query("SELECT SROID, SROScheduleNo, Description FROM DI_SRO WHERE IsActive = 1");
$sros = $sroStmt->fetchAll();

$saleTypeStmt = $db->query("SELECT SaleTypeID, SaleTypeCode, Description FROM DI_SaleType WHERE IsActive = 1");
$saleTypes = $saleTypeStmt->fetchAll();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_invoice' || $_POST['action'] === 'edit_invoice') {
        $db->beginTransaction();
        try {
            $isEdit = ($_POST['action'] === 'edit_invoice');
            $invoiceId = $isEdit ? $_POST['invoiceId'] : null;
            $branchId = $_POST['branchId'];
            $invoiceDate = $_POST['invoiceDate'];
            $buyerName = $_POST['buyerName'];
            $buyerNTN = $_POST['buyerNTN'];
            $buyerProvince = $_POST['buyerProvince'] ?? null;
            $buyerAddress = $_POST['buyerAddress'] ?? null;
            $totalSaleValue = $_POST['totalSaleValue'];
            $totalSalesTax = $_POST['totalSalesTax'];
            $totalInvoiceValue = $_POST['totalInvoiceValue'];

            // Fetch Seller Info from Branch & Company
            $sellerStmt = $db->prepare("
                SELECT b.CompanyID, b.BranchName, b.BranchAddress, b.ProvinceCode, c.CompanyName, c.NTN 
                FROM DI_Branch b 
                JOIN DI_Company c ON b.CompanyID = c.CompanyID 
                WHERE b.BranchID = ?
            ");
            $sellerStmt->execute([$branchId]);
            $seller = $sellerStmt->fetch();

            if (!$seller)
                throw new Exception("Invalid Branch/Company details.");

            $companyId = $seller['CompanyID'];

            if ($isEdit) {
                $stmt = $db->prepare("UPDATE DI_Invoice_Master SET 
                    CompanyID = ?, BranchID = ?, InvoiceDate = ?, 
                    SellerNTN = ?, SellerBusinessName = ?, SellerProvince = ?, SellerAddress = ?, 
                    BuyerNTNCNIC = ?, BuyerBusinessName = ?, BuyerProvince = ?, BuyerAddress = ?,
                    TotalSaleValue = ?, TotalSalesTax = ?, TotalInvoiceValue = ?, UpdatedOn = CURRENT_TIMESTAMP 
                    WHERE InvoiceID = ?");
                $stmt->execute([
                    $companyId,
                    $branchId,
                    $invoiceDate,
                    $seller['NTN'],
                    $seller['CompanyName'],
                    $seller['ProvinceCode'],
                    $seller['BranchAddress'],
                    $buyerNTN,
                    $buyerName,
                    $buyerProvince,
                    $buyerAddress,
                    $totalSaleValue,
                    $totalSalesTax,
                    $totalInvoiceValue,
                    $invoiceId
                ]);

                // Delete old details and re-insert
                $db->prepare("DELETE FROM DI_Invoice_Detail WHERE InvoiceID = ?")->execute([$invoiceId]);
            } else {
                $invoiceNumber = 'INV-' . time();
                $stmt = $db->prepare("INSERT INTO DI_Invoice_Master 
                    (CompanyID, BranchID, InvoiceType, InvoiceDate, InvoiceNumber, 
                     SellerNTN, SellerBusinessName, SellerProvince, SellerAddress,
                     BuyerNTNCNIC, BuyerBusinessName, BuyerProvince, BuyerAddress,
                     TotalSaleValue, TotalSalesTax, TotalInvoiceValue, CreatedOn, IsSubmittedToFBR) 
                    OUTPUT INSERTED.InvoiceID
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 0)");
                $stmt->execute([
                    $companyId,
                    $branchId,
                    'Sales',
                    $invoiceDate,
                    $invoiceNumber,
                    $seller['NTN'],
                    $seller['CompanyName'],
                    $seller['ProvinceCode'],
                    $seller['BranchAddress'],
                    $buyerNTN,
                    $buyerName,
                    $buyerProvince,
                    $buyerAddress,
                    $totalSaleValue,
                    $totalSalesTax,
                    $totalInvoiceValue
                ]);
                $invoiceId = $stmt->fetchColumn();
            }

            if (!$invoiceId)
                throw new Exception("Failed to retrieve Invoice ID.");

            // Insert Details & Sync to Sales Fact
            if (!isset($_POST['items']) || !is_array($_POST['items']) || empty($_POST['items'])) {
                throw new Exception("At least one line item is required.");
            }

            $detailStmt = $db->prepare("INSERT INTO DI_Invoice_Detail 
                (InvoiceID, HSCode, ProductDescription, Quantity, UnitPrice, Rate, ValueSalesExcludingST, SalesTaxApplicable, TotalValues, UOMID, SaleTypeID, SROID) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $factStmt = $db->prepare("INSERT INTO Fact_Sales 
                    (DateKey, BranchID, CompanyID, SaleTypeID, HSCode, Quantity, SaleAmount, TaxAmount) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $dateKey = (int) date('Ymd', strtotime($invoiceDate));

            foreach ($_POST['items'] as $item) {
                $detailStmt->execute([
                    $invoiceId,
                    $item['hscode'],
                    $item['description'],
                    $item['qty'],
                    $item['price'],
                    $item['taxRate'],
                    $item['valueExcl'],
                    $item['taxAmount'],
                    $item['total'],
                    $item['uomId'] ?: null,
                    $item['saleTypeId'] ?: null,
                    $item['sroId'] ?: null
                ]);

                $factStmt->execute([
                    $dateKey,
                    (int) $branchId,
                    (int) $companyId,
                    (int) ($item['saleTypeId'] ?: 1),
                    $item['hscode'],
                    (float) $item['qty'],
                    (float) $item['valueExcl'],
                    (float) $item['taxAmount']
                ]);
            }

            $db->commit();
            redirect('invoices.php');
        } catch (Exception $e) {
            $db->rollBack();
            file_put_contents('save_error.txt', "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $error = $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update_status') {
        $invoiceId = $_POST['invoiceId'];
        $newStatus = (int) $_POST['newStatus'];
        $stmt = $db->prepare("UPDATE DI_Invoice_Master SET IsSubmittedToFBR = ? WHERE InvoiceID = ?");
        $stmt->execute([$newStatus, $invoiceId]);
        redirect('invoices.php');
    }
}

$editInvoice = null;
$editDetails = [];
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM DI_Invoice_Master WHERE InvoiceID = ?");
    $stmt->execute([$_GET['edit']]);
    $editInvoice = $stmt->fetch();

    if ($editInvoice) {
        $dStmt = $db->prepare("SELECT * FROM DI_Invoice_Detail WHERE InvoiceID = ?");
        $dStmt->execute([$_GET['edit']]);
        $editDetails = $dStmt->fetchAll();
    }
}

if (isset($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM DI_Invoice_Master WHERE InvoiceID = ?");
        $stmt->execute([$_GET['delete']]);
    } catch (PDOException $e) {
    }
    redirect('invoices.php');
}

$stmt = $db->query("
    SELECT i.*, b.BranchName 
    FROM DI_Invoice_Master i 
    LEFT JOIN DI_Branch b ON i.BranchID = b.BranchID 
    ORDER BY i.CreatedOn DESC
");
$invoices = $stmt->fetchAll();

$page_title = 'Invoices';
require_once 'includes/header.php';
?>

<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Manage Invoices</h2>
        <p class="text-slate-500 font-medium">Record sales and sync with FBR</p>
    </div>
    <button onclick="document.getElementById('addInvoiceForm').classList.toggle('hidden')"
        class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">
        <i class="fas fa-plus mr-2"></i> Create Invoice
    </button>
</div>

<div id="addInvoiceForm"
    class="<?= $editInvoice ? '' : 'hidden ' ?>bg-white p-8 rounded-2xl shadow-sm border border-slate-200 mb-8 max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h3 class="text-xl font-black text-slate-800 tracking-tight"><?= $editInvoice ? 'Edit' : 'Create' ?> Invoice
        </h3>
        <?php if ($editInvoice): ?>
            <a href="invoices.php" class="text-slate-400 hover:text-red-500 font-bold transition-all"><i
                    class="fas fa-times mr-1"></i> Cancel</a>
        <?php endif; ?>
    </div>

    <form action="invoices.php" method="POST" id="invoiceForm">
        <input type="hidden" name="action" value="<?= $editInvoice ? 'edit_invoice' : 'add_invoice' ?>">
        <?php if ($editInvoice): ?>
            <input type="hidden" name="invoiceId" value="<?= $editInvoice['InvoiceID'] ?>">
        <?php endif; ?>

        <!-- Master Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 bg-slate-50 p-6 rounded-2xl border border-slate-100">
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block tracking-widest">Branch
                    *</label>
                <select name="branchId" required
                    class="w-full bg-white border-2 border-slate-200 p-3 rounded-xl outline-none focus:border-blue-500 transition-all font-bold text-slate-700">
                    <option value="">Select Branch</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['BranchID'] ?>" <?= ($editInvoice && $editInvoice['BranchID'] == $b['BranchID']) ? 'selected' : '' ?>><?= htmlspecialchars($b['BranchName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block tracking-widest">Invoice Date
                    *</label>
                <input type="date" name="invoiceDate" required
                    value="<?= $editInvoice ? date('Y-m-d', strtotime($editInvoice['InvoiceDate'])) : date('Y-m-d') ?>"
                    class="w-full bg-white border-2 border-slate-200 p-3 rounded-xl outline-none focus:border-blue-500 transition-all font-bold text-slate-700">
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block tracking-widest">Buyer Name
                    *</label>
                <input type="text" name="buyerName" required
                    value="<?= $editInvoice ? htmlspecialchars($editInvoice['BuyerBusinessName']) : '' ?>"
                    class="w-full bg-white border-2 border-slate-200 p-3 rounded-xl outline-none focus:border-blue-500 transition-all font-bold text-slate-700">
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block tracking-widest">Buyer
                    NTN/CNIC</label>
                <input type="text" name="buyerNTN"
                    value="<?= $editInvoice ? htmlspecialchars($editInvoice['BuyerNTNCNIC']) : '' ?>"
                    class="w-full bg-white border-2 border-slate-200 p-3 rounded-xl outline-none focus:border-blue-500 transition-all font-bold text-slate-700">
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block tracking-widest">Buyer
                    Province</label>
                <select name="buyerProvince"
                    class="w-full bg-white border-2 border-slate-200 p-3 rounded-xl outline-none focus:border-blue-500 transition-all font-bold text-slate-700">
                    <option value="">Select Province</option>
                    <option value="Punjab" <?= ($editInvoice && $editInvoice['BuyerProvince'] == 'Punjab') ? 'selected' : '' ?>>Punjab</option>
                    <option value="Sindh" <?= ($editInvoice && $editInvoice['BuyerProvince'] == 'Sindh') ? 'selected' : '' ?>>Sindh</option>
                    <option value="KPK" <?= ($editInvoice && $editInvoice['BuyerProvince'] == 'KPK') ? 'selected' : '' ?>>
                        KPK</option>
                    <option value="Balochistan" <?= ($editInvoice && $editInvoice['BuyerProvince'] == 'Balochistan') ? 'selected' : '' ?>>Balochistan</option>
                    <option value="ICT" <?= ($editInvoice && $editInvoice['BuyerProvince'] == 'ICT') ? 'selected' : '' ?>>
                        ICT</option>
                </select>
            </div>
            <div class="md:col-span-4">
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block tracking-widest">Buyer
                    Address</label>
                <input type="text" name="buyerAddress"
                    value="<?= $editInvoice ? htmlspecialchars($editInvoice['BuyerAddress'] ?? '') : '' ?>"
                    class="w-full bg-white border-2 border-slate-200 p-3 rounded-xl outline-none focus:border-blue-500 transition-all font-bold text-slate-700">
            </div>
        </div>

        <!-- Detail Section -->
        <div class="mb-8 overflow-x-auto">
            <h4 class="text-sm font-black text-slate-400 uppercase mb-4 tracking-widest px-2">Line Items</h4>
            <table class="w-full" id="itemsTable">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                        <th class="pb-3 px-2">HS Code *</th>
                        <th class="pb-3 px-2">Description *</th>
                        <th class="pb-3 px-2 w-24">Qty</th>
                        <th class="pb-3 px-2 w-32">Price</th>
                        <th class="pb-3 px-2 w-24">Tax %</th>
                        <th class="pb-3 px-2">UOM</th>
                        <th class="pb-3 px-2">Total</th>
                        <th class="pb-3 px-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody" class="divide-y divide-slate-100">
                    <?php if (empty($editDetails)): ?>
                        <tr class="item-row">
                            <td class="py-3 px-1"><input type="text" name="items[0][hscode]" required
                                    class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-medium">
                            </td>
                            <td class="py-3 px-1"><input type="text" name="items[0][description]" required
                                    class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-medium">
                            </td>
                            <td class="py-3 px-1"><input type="number" step="0.01" name="items[0][qty]" required
                                    class="qty-input w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-bold text-center">
                            </td>
                            <td class="py-3 px-1"><input type="number" step="0.01" name="items[0][price]" required
                                    class="price-input w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-bold text-center">
                            </td>
                            <td class="py-3 px-1"><input type="number" step="0.01" name="items[0][taxRate]" required
                                    class="tax-rate-input w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-bold text-center"
                                    value="18"></td>
                            <td class="py-3 px-1">
                                <select name="items[0][uomId]"
                                    class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-xs font-bold">
                                    <option value="">UOM</option>
                                    <?php foreach ($uoms as $u): ?>
                                        <option value="<?= $u['UOMID'] ?>"><?= $u['UOMCode'] ?></option><?php endforeach; ?>
                                </select>
                            </td>
                            <td class="py-3 px-1 text-right font-black text-slate-700 text-sm row-total">0.00</td>
                            <td class="py-3 px-1 text-center">
                                <button type="button"
                                    class="remove-item text-slate-300 hover:text-red-500 transition-all"><i
                                        class="fas fa-trash"></i></button>
                                <input type="hidden" name="items[0][valueExcl]" class="value-excl">
                                <input type="hidden" name="items[0][taxAmount]" class="tax-amount">
                                <input type="hidden" name="items[0][total]" class="row-total-val">
                                <input type="hidden" name="items[0][saleTypeId]" value="1">
                                <input type="hidden" name="items[0][sroId]" value="">
                            </td>
                        </tr>
                    <?php else:
                        foreach ($editDetails as $i => $d): ?>
                            <tr class="item-row">
                                <td class="py-3 px-1"><input type="text" name="items[<?= $i ?>][hscode]" required
                                        value="<?= $d['HSCode'] ?>"
                                        class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-medium">
                                </td>
                                <td class="py-3 px-1"><input type="text" name="items[<?= $i ?>][description]" required
                                        value="<?= $d['ProductDescription'] ?>"
                                        class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-medium">
                                </td>
                                <td class="py-3 px-1"><input type="number" step="0.01" name="items[<?= $i ?>][qty]" required
                                        value="<?= $d['Quantity'] ?>"
                                        class="qty-input w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-bold text-center">
                                </td>
                                <td class="py-3 px-1"><input type="number" step="0.01" name="items[<?= $i ?>][price]" required
                                        value="<?= $d['UnitPrice'] ?>"
                                        class="price-input w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-bold text-center">
                                </td>
                                <td class="py-3 px-1"><input type="number" step="0.01" name="items[<?= $i ?>][taxRate]" required
                                        value="<?= $d['Rate'] ?>"
                                        class="tax-rate-input w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-bold text-center">
                                </td>
                                <td class="py-3 px-1">
                                    <select name="items[<?= $i ?>][uomId]"
                                        class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-xs font-bold">
                                        <option value="">UOM</option>
                                        <?php foreach ($uoms as $u): ?>
                                            <option value="<?= $u['UOMID'] ?>" <?= $u['UOMID'] == $d['UOMID'] ? 'selected' : '' ?>>
                                                <?= $u['UOMCode'] ?>
                                            </option><?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="py-3 px-1 text-right font-black text-slate-700 text-sm row-total">
                                    <?= number_format($d['TotalValues'], 2) ?>
                                </td>
                                <td class="py-3 px-1 text-center">
                                    <button type="button"
                                        class="remove-item text-slate-300 hover:text-red-500 transition-all"><i
                                            class="fas fa-trash"></i></button>
                                    <input type="hidden" name="items[<?= $i ?>][valueExcl]"
                                        value="<?= $d['ValueSalesExcludingST'] ?>" class="value-excl">
                                    <input type="hidden" name="items[<?= $i ?>][taxAmount]"
                                        value="<?= $d['SalesTaxApplicable'] ?>" class="tax-amount">
                                    <input type="hidden" name="items[<?= $i ?>][total]" value="<?= $d['TotalValues'] ?>"
                                        class="row-total-val">
                                    <input type="hidden" name="items[<?= $i ?>][saleTypeId]" value="<?= $d['SaleTypeID'] ?>">
                                    <input type="hidden" name="items[<?= $i ?>][sroId]" value="<?= $d['SROID'] ?>">
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
            <button type="button" id="addItem"
                class="mt-4 text-sm font-bold text-blue-600 hover:text-blue-800 transition-all"><i
                    class="fas fa-plus-circle mr-1"></i> Add Line Item</button>
        </div>

        <!-- Totals Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 border-t pt-8">
            <div class="md:col-span-2 text-slate-400 text-xs font-medium italic">
                * All values are auto-calculated. Ensure quantities and unit prices are correct before submitting.
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Subtotal (Excl.
                        Tax)</span>
                    <span id="displaySubtotal" class="font-bold text-slate-700">0.00</span>
                    <input type="hidden" name="totalSaleValue" id="inputSubtotal">
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Total Sales Tax</span>
                    <span id="displayTax" class="font-bold text-slate-700">0.00</span>
                    <input type="hidden" name="totalSalesTax" id="inputTax">
                </div>
                <div class="flex justify-between items-center py-3 border-t border-slate-200">
                    <span class="text-slate-800 font-black uppercase tracking-wider text-xs">Grand Total</span>
                    <span id="displayGrandTotal" class="text-xl font-black text-blue-600">0.00</span>
                    <input type="hidden" name="totalInvoiceValue" id="inputGrandTotal">
                </div>
                <button type="submit"
                    class="w-full bg-blue-600 text-white font-black py-4 rounded-2xl shadow-xl shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">
                    <?= $editInvoice ? 'Update' : 'Confirm & Save' ?> Invoice
                </button>
            </div>
        </div>
    </form>
</div>

<!-- JavaScript for Dynamic Interactions -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const itemsBody = document.getElementById('itemsBody');
        const addItemBtn = document.getElementById('addItem');
        let rowCount = <?= count($editDetails) ?: 1 ?>;

        function calculateRow(row) {
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const taxRate = parseFloat(row.querySelector('.tax-rate-input').value) || 0;

            const valueExcl = qty * price;
            const taxAmount = (valueExcl * taxRate) / 100;
            const total = valueExcl + taxAmount;

            row.querySelector('.value-excl').value = valueExcl.toFixed(2);
            row.querySelector('.tax-amount').value = taxAmount.toFixed(2);
            row.querySelector('.row-total-val').value = total.toFixed(2);
            row.querySelector('.row-total').textContent = total.toLocaleString(undefined, { minimumFractionDigits: 2 });

            calculateTotals();
        }

        function calculateTotals() {
            let subtotal = 0;
            let tax = 0;
            let grandTotal = 0;

            document.querySelectorAll('.item-row').forEach(row => {
                subtotal += parseFloat(row.querySelector('.value-excl').value) || 0;
                tax += parseFloat(row.querySelector('.tax-amount').value) || 0;
                grandTotal += parseFloat(row.querySelector('.row-total-val').value) || 0;
            });

            document.getElementById('displaySubtotal').textContent = subtotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
            document.getElementById('inputSubtotal').value = subtotal.toFixed(2);

            document.getElementById('displayTax').textContent = tax.toLocaleString(undefined, { minimumFractionDigits: 2 });
            document.getElementById('inputTax').value = tax.toFixed(2);

            document.getElementById('displayGrandTotal').textContent = grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
            document.getElementById('inputGrandTotal').value = grandTotal.toFixed(2);
        }

        itemsBody.addEventListener('input', function (e) {
            if (e.target.classList.contains('qty-input') ||
                e.target.classList.contains('price-input') ||
                e.target.classList.contains('tax-rate-input')) {
                calculateRow(e.target.closest('.item-row'));
            }
        });

        itemsBody.addEventListener('click', function (e) {
            if (e.target.closest('.remove-item')) {
                const rows = document.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    e.target.closest('.item-row').remove();
                    calculateTotals();
                } else {
                    alert('At least one item is required.');
                }
            }
        });

        addItemBtn.addEventListener('click', function () {
            const template = `
            <tr class="item-row">
                <td class="py-3 px-1"><input type="text" name="items[${rowCount}][hscode]" required class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-medium"></td>
                <td class="py-3 px-1"><input type="text" name="items[${rowCount}][description]" required class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-medium"></td>
                <td class="py-3 px-1"><input type="number" step="0.01" name="items[${rowCount}][qty]" required class="qty-input w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-bold text-center"></td>
                <td class="py-3 px-1"><input type="number" step="0.01" name="items[${rowCount}][price]" required class="price-input w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-bold text-center"></td>
                <td class="py-3 px-1"><input type="number" step="0.01" name="items[${rowCount}][taxRate]" required class="tax-rate-input w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-sm font-bold text-center" value="18"></td>
                <td class="py-3 px-1">
                    <select name="items[${rowCount}][uomId]" class="w-full bg-slate-50 border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-500 text-xs font-bold">
                        <option value="">UOM</option>
                        <?php foreach ($uoms as $u): ?><option value="<?= $u['UOMID'] ?>"><?= $u['UOMCode'] ?></option><?php endforeach; ?>
                    </select>
                </td>
                <td class="py-3 px-1 text-right font-black text-slate-700 text-sm row-total">0.00</td>
                <td class="py-3 px-1 text-center">
                    <button type="button" class="remove-item text-slate-300 hover:text-red-500 transition-all"><i class="fas fa-trash"></i></button>
                    <input type="hidden" name="items[${rowCount}][valueExcl]" class="value-excl">
                    <input type="hidden" name="items[${rowCount}][taxAmount]" class="tax-amount">
                    <input type="hidden" name="items[${rowCount}][total]" class="row-total-val">
                    <input type="hidden" name="items[${rowCount}][saleTypeId]" value="1">
                    <input type="hidden" name="items[${rowCount}][sroId]" value="">
                </td>
            </tr>
        `;
            itemsBody.insertAdjacentHTML('beforeend', template);
            rowCount++;
        });

        // Initial calculations
        calculateTotals();
    });
</script>


<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-8">
    <div class="p-6 border-b bg-slate-50/50 flex justify-between items-center text-sm">
        <h3 class="font-bold text-slate-800 uppercase tracking-wider">Invoice History</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead
                class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                <tr>
                    <th class="p-5">Invoice No</th>
                    <th class="p-5">Date</th>
                    <th class="p-5">Branch</th>
                    <th class="p-5">Buyer</th>
                    <th class="p-5">Total Value</th>
                    <th class="p-5">FBR Res</th>
                    <th class="p-5 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="7" class="p-5 text-center text-slate-500">No invoices found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-5 font-bold text-blue-600"><?= htmlspecialchars($inv['InvoiceNumber']) ?></td>
                            <td class="p-5 text-slate-700">
                                <?= htmlspecialchars(date('Y-m-d', strtotime($inv['InvoiceDate']))) ?></td>
                            <td class="p-5 font-bold text-slate-700"><?= htmlspecialchars($inv['BranchName']) ?></td>
                            <td class="p-5 text-slate-500 font-medium"><?= htmlspecialchars($inv['BuyerBusinessName']) ?></td>
                            <td class="p-5 font-black">Rs. <?= number_format($inv['TotalInvoiceValue'], 2) ?></td>
                            <td class="p-5">
                                <form action="invoices.php" method="POST" style="display:inline; margin: 0;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="invoiceId" value="<?= $inv['InvoiceID'] ?>">
                                    <select name="newStatus" onchange="this.form.submit()"
                                        style="padding: 4px; border-radius: 4px; border: 1px solid #ccc; background-color: <?php echo $inv['IsSubmittedToFBR'] ? '#10b981' : '#f59e0b'; ?>; color: white; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 10px;">
                                        <option value="0" <?= !$inv['IsSubmittedToFBR'] ? 'selected' : '' ?>>Pending</option>
                                        <option value="1" <?= $inv['IsSubmittedToFBR'] ? 'selected' : '' ?>>Submitted</option>
                                    </select>
                                </form>
                            </td>
                            <td class="p-5 text-center flex justify-center gap-2">
                                <button onclick="viewInvoice(<?= $inv['InvoiceID'] ?>)"
                                    class="text-slate-500 hover:text-slate-700 bg-slate-50 px-3 py-1.5 rounded-lg font-bold text-xs"><i
                                        class="fas fa-eye mr-1"></i> View</button>
                                <a href="?edit=<?= $inv['InvoiceID'] ?>"
                                    class="text-blue-500 hover:text-blue-700 bg-blue-50 px-3 py-1.5 rounded-lg font-bold text-xs"><i
                                        class="fas fa-edit mr-1"></i> Edit</a>
                                <a href="?delete=<?= $inv['InvoiceID'] ?>"
                                    onclick="return confirm('Are you sure you want to delete this invoice?');"
                                    class="text-red-500 hover:text-red-700 bg-red-50 px-3 py-1.5 rounded-lg font-bold text-xs"><i
                                        class="fas fa-trash mr-1"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Premium Invoice Modal -->
<div id="invoiceModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm modal-close-trigger"></div>
    <div
        class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl relative z-10 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-200">
        <!-- Modal Header -->
        <div class="p-6 border-b flex justify-between items-center bg-slate-50/80">
            <div>
                <h3 id="modalInvoiceNo" class="text-xl font-black text-slate-800 tracking-tight">INV-000000</h3>
                <p id="modalDate" class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Date:
                    2024-01-01</p>
            </div>
            <button class="modal-close-trigger text-slate-400 hover:text-red-500 transition-all text-xl"><i
                    class="fas fa-times"></i></button>
        </div>

        <!-- Modal Content -->
        <div class="p-8 overflow-y-auto">
            <!-- Party Info -->
            <div class="grid grid-cols-2 gap-8 mb-8 pb-8 border-b border-slate-100">
                <div>
                    <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-4">Seller Detail</h4>
                    <p id="modalSellerName" class="text-sm font-black text-slate-800"></p>
                    <p id="modalSellerNTN" class="text-xs text-slate-500 mt-1"></p>
                    <p id="modalSellerAddress" class="text-xs text-slate-500 mt-1 italic"></p>
                </div>
                <div>
                    <h4 class="text-[10px] font-black text-orange-600 uppercase tracking-widest mb-4">Buyer Detail</h4>
                    <p id="modalBuyerName" class="text-sm font-black text-slate-800"></p>
                    <p id="modalBuyerNTN" class="text-xs text-slate-500 mt-1"></p>
                    <p id="modalBuyerAddress" class="text-xs text-slate-500 mt-1 italic"></p>
                </div>
            </div>

            <!-- Items Table -->
            <table class="w-full text-left mb-8">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b pb-4">
                        <th class="py-3">HS Code</th>
                        <th class="py-3">Product Name</th>
                        <th class="py-3 text-center">Qty</th>
                        <th class="py-3 text-center">UOM</th>
                        <th class="py-3 text-right">Price</th>
                        <th class="py-3 text-right">Tax (%)</th>
                        <th class="py-3 text-right">Tax Amt</th>
                        <th class="py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody id="modalItemsBody" class="divide-y divide-slate-50">
                    <!-- Rows injected here -->
                </tbody>
            </table>

            <!-- Summary -->
            <div class="bg-slate-50 p-6 rounded-2xl flex justify-end">
                <div class="w-64 space-y-3">
                    <div class="flex justify-between text-xs font-bold text-slate-500 uppercase">
                        <span>Subtotal</span>
                        <span id="modalSubtotal">Rs. 0.00</span>
                    </div>
                    <div class="flex justify-between text-xs font-bold text-slate-500 uppercase">
                        <span>Total Tax</span>
                        <span id="modalTax">Rs. 0.00</span>
                    </div>
                    <div class="flex justify-between text-base font-black text-blue-600 border-t pt-3 mt-3">
                        <span>Grand Total</span>
                        <span id="modalGrandTotal">Rs. 0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-slate-50 border-t flex justify-end gap-3">
            <button
                class="modal-close-trigger px-6 py-2 rounded-xl font-bold border border-slate-200 text-slate-500 hover:bg-white transition-all">Close</button>
            <button onclick="window.print()"
                class="px-6 py-2 rounded-xl font-bold bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all"><i
                    class="fas fa-print mr-2"></i> Print</button>
        </div>
    </div>
</div>

<script>
    function viewInvoice(id) {
        const modal = document.getElementById('invoiceModal');
        const itemsBody = document.getElementById('modalItemsBody');

        // Show loading or reset
        itemsBody.innerHTML = '<tr><td colspan="6" class="p-10 text-center"><i class="fas fa-circle-notch fa-spin text-blue-500 text-2xl"></i></td></tr>';
        modal.classList.remove('hidden');

        fetch(`invoices.php?ajax=get_details&invoiceId=${id}`)
            .then(res => res.json())
            .then(data => {
                const m = data.master;
                const items = data.details;

                // Set Master Data
                document.getElementById('modalInvoiceNo').textContent = m.InvoiceNumber;
                document.getElementById('modalDate').textContent = 'Date: ' + m.InvoiceDate.split(' ')[0];

                document.getElementById('modalSellerName').textContent = m.SellerBusinessName || 'N/A';
                document.getElementById('modalSellerNTN').textContent = 'NTN: ' + (m.SellerNTN || 'N/A');
                document.getElementById('modalSellerAddress').textContent = (m.SellerAddress || '') + (m.SellerProvince ? ', ' + m.SellerProvince : '');

                document.getElementById('modalBuyerName').textContent = m.BuyerBusinessName;
                document.getElementById('modalBuyerNTN').textContent = 'NTN/CNIC: ' + (m.BuyerNTNCNIC || 'N/A');
                document.getElementById('modalBuyerAddress').textContent = (m.BuyerAddress || '') + (m.BuyerProvince ? ', ' + m.BuyerProvince : '');

                document.getElementById('modalSubtotal').textContent = 'Rs. ' + parseFloat(m.TotalSaleValue).toLocaleString(undefined, { minimumFractionDigits: 2 });
                document.getElementById('modalTax').textContent = 'Rs. ' + parseFloat(m.TotalSalesTax).toLocaleString(undefined, { minimumFractionDigits: 2 });
                document.getElementById('modalGrandTotal').textContent = 'Rs. ' + parseFloat(m.TotalInvoiceValue).toLocaleString(undefined, { minimumFractionDigits: 2 });

                // Set Details
                itemsBody.innerHTML = '';
                items.forEach(it => {
                    const row = `
                        <tr class="text-sm font-medium text-slate-700">
                            <td class="py-4 font-mono text-xs text-slate-400">${it.HSCode}</td>
                            <td class="py-4 font-bold text-slate-800">${it.ProductDescription}</td>
                            <td class="py-4 text-center font-black">${parseFloat(it.Quantity).toLocaleString()}</td>
                            <td class="py-4 text-center"><span class="px-2 py-1 bg-slate-100 rounded text-[10px] font-black text-slate-500 uppercase">${it.UOMCode || '-'}</span></td>
                            <td class="py-4 text-right">Rs. ${parseFloat(it.UnitPrice).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                            <td class="py-4 text-right">${parseFloat(it.Rate).toFixed(1)}%</td>
                            <td class="py-4 text-right text-orange-600 font-bold">Rs. ${parseFloat(it.SalesTaxApplicable).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                            <td class="py-4 text-right font-black text-blue-600 underline decoration-blue-100">Rs. ${parseFloat(it.TotalValues).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                        </tr>
                    `;
                    itemsBody.insertAdjacentHTML('beforeend', row);
                });
            });
    }

    // Modal Close Logic
    document.querySelectorAll('.modal-close-trigger').forEach(el => {
        el.addEventListener('click', () => {
            document.getElementById('invoiceModal').classList.add('hidden');
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>