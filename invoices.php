<?php
$page_title = 'Invoices';
require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();

$branchStmt = $db->query("SELECT BranchID, BranchName FROM DI_Branch WHERE IsActive = 1");
$branches = $branchStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_invoice') {
    $branchId = $_POST['branchId'];
    $invoiceDate = $_POST['invoiceDate'];
    $invoiceNumber = 'INV-' . time();
    $buyerName = $_POST['buyerName'];
    $buyerNTN = $_POST['buyerNTN'];
    $totalValue = $_POST['totalValue'];


    $cbStmt = $db->prepare("SELECT CompanyID FROM DI_Branch WHERE BranchID = ?");
    $cbStmt->execute([$branchId]);
    $companyId = $cbStmt->fetchColumn();

    if ($companyId && !empty($totalValue)) {
        $stmt = $db->prepare("INSERT INTO DI_Invoice_Master 
            (CompanyID, BranchID, InvoiceType, InvoiceDate, InvoiceNumber, BuyerNTNCNIC, BuyerBusinessName, TotalInvoiceValue, CreatedOn) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())");
        $stmt->execute([
            $companyId,
            $branchId,
            'Sales',     
            $invoiceDate,
            $invoiceNumber,
            $buyerNTN,
            $buyerName,
            $totalValue
        ]);
        redirect('invoices.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $invoiceId = $_POST['invoiceId'];
    $newStatus = (int) $_POST['newStatus'];

    $stmt = $db->prepare("UPDATE DI_Invoice_Master SET IsSubmittedToFBR = ?, UpdatedOn = GETDATE() WHERE InvoiceID = ?");
    $stmt->execute([$newStatus, $invoiceId]);
    redirect('invoices.php');
}

$stmt = $db->query("
    SELECT i.*, b.BranchName 
    FROM DI_Invoice_Master i 
    LEFT JOIN DI_Branch b ON i.BranchID = b.BranchID 
    ORDER BY i.CreatedOn DESC
");
$invoices = $stmt->fetchAll();
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

<div id="addInvoiceForm" class="hidden bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8">
    <h3 class="font-bold text-slate-700 mb-4 border-b pb-2">Create New Invoice Tracker</h3>
    <form action="invoices.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="add_invoice">

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Branch *</label>
            <select name="branchId" required
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
                <option value="">Select Branch</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['BranchID'] ?>"><?= htmlspecialchars($b['BranchName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Invoice Date *</label>
            <input type="date" name="invoiceDate" required value="<?= date('Y-m-d') ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Buyer Name *</label>
            <input type="text" name="buyerName" required
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Buyer NTN/CNIC</label>
            <input type="text" name="buyerNTN"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div class="md:col-span-2">
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Total Invoice Value *</label>
            <input type="number" step="0.01" name="totalValue" required
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div class="md:col-span-2 mt-2">
            <button type="submit"
                class="bg-blue-600 text-white font-black py-3 px-6 rounded-xl shadow-lg hover:bg-blue-700">Save
                Invoice</button>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
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
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="6" class="p-5 text-center text-slate-500">No invoices found.</td>
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
                                        <option value="0" <?= !$inv['IsSubmittedToFBR'] ? 'selected' : '' ?>
                                            style="background-color: white; color: black; font-size: 14px; text-transform: none;">
                                            Pending</option>
                                        <option value="1" <?= $inv['IsSubmittedToFBR'] ? 'selected' : '' ?>
                                            style="background-color: white; color: black; font-size: 14px; text-transform: none;">
                                            Submitted</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>