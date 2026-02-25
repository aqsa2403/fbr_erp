<?php
$page_title = 'Companies';
require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_company') {
        $companyName = trim($_POST['companyName'] ?? '');
        $ntn = trim($_POST['ntn'] ?? '');
        $strn = trim($_POST['strn'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $provinceCode = trim($_POST['provinceCode'] ?? '');

        if (!empty($companyName) && !empty($ntn)) {
            $stmt = $db->prepare("INSERT INTO DI_Company (CompanyName, NTN, STRN, HeadOfficeAddress, ProvinceCode, IsActive, CreatedOn) VALUES (:cname, :ntn, :strn, :addr, :pcode, 1, CURRENT_TIMESTAMP)");
            $stmt->execute([
                'cname' => $companyName,
                'ntn' => $ntn,
                'strn' => $strn,
                'addr' => $address,
                'pcode' => $provinceCode
            ]);
            redirect('companies.php');
        }
    } elseif ($_POST['action'] === 'edit_company') {
        $companyId = $_POST['companyId'] ?? 0;
        $companyName = trim($_POST['companyName'] ?? '');
        $ntn = trim($_POST['ntn'] ?? '');
        $strn = trim($_POST['strn'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $provinceCode = trim($_POST['provinceCode'] ?? '');

        if (!empty($companyName) && !empty($ntn) && $companyId) {
            $stmt = $db->prepare("UPDATE DI_Company SET CompanyName = :cname, NTN = :ntn, STRN = :strn, HeadOfficeAddress = :addr, ProvinceCode = :pcode WHERE CompanyID = :cid");
            $stmt->execute([
                'cname' => $companyName,
                'ntn' => $ntn,
                'strn' => $strn,
                'addr' => $address,
                'pcode' => $provinceCode,
                'cid' => $companyId
            ]);
            redirect('companies.php');
        }
    }
}

$editCompany = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM DI_Company WHERE CompanyID = ?");
    $stmt->execute([$_GET['edit']]);
    $editCompany = $stmt->fetch();
}

if (isset($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM DI_Company WHERE CompanyID = ?");
        $stmt->execute([$_GET['delete']]);
    } catch (PDOException $e) {
        // If deletion fails (e.g., due to foreign keys), it will just fail silently or we can show an error, but let's just redirect for now.
    }
    redirect('companies.php');
}

$stmt = $db->query("SELECT * FROM DI_Company ORDER BY CreatedOn DESC");
$companies = $stmt->fetchAll();
?>

<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Manage Companies</h2>
        <p class="text-slate-500 font-medium">Registered business entities</p>
    </div>
    <button onclick="document.getElementById('addCompanyForm').classList.toggle('hidden')"
        class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">
        <i class="fas fa-plus mr-2"></i> Add Company
    </button>
</div>

<div id="addCompanyForm"
    class="<?= $editCompany ? '' : 'hidden ' ?>bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <h3 class="font-bold text-slate-700"><?= $editCompany ? 'Edit Company' : 'Add New Company' ?></h3>
        <?php if ($editCompany): ?>
            <a href="companies.php" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i> Cancel</a>
        <?php endif; ?>
    </div>
    <form action="companies.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="<?= $editCompany ? 'edit_company' : 'add_company' ?>">
        <?php if ($editCompany): ?>
            <input type="hidden" name="companyId" value="<?= $editCompany['CompanyID'] ?>">
        <?php endif; ?>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Company Name *</label>
            <input type="text" name="companyName" required
                value="<?= $editCompany ? htmlspecialchars($editCompany['CompanyName']) : '' ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">NTN *</label>
            <input type="text" name="ntn" required
                value="<?= $editCompany ? htmlspecialchars($editCompany['NTN']) : '' ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">STRN</label>
            <input type="text" name="strn" value="<?= $editCompany ? htmlspecialchars($editCompany['STRN']) : '' ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Province Code</label>
            <select name="provinceCode"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
                <?php
                $provinces = ['PB' => 'Punjab (PB)', 'SD' => 'Sindh (SD)', 'KP' => 'KPK (KP)', 'BA' => 'Balochistan (BA)', 'IS' => 'Islamabad (IS)'];
                $selectedProv = $editCompany ? $editCompany['ProvinceCode'] : 'PB';
                foreach ($provinces as $code => $name): ?>
                    <option value="<?= $code ?>" <?= $selectedProv === $code ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Head Office Address</label>
            <input type="text" name="address"
                value="<?= $editCompany ? htmlspecialchars($editCompany['HeadOfficeAddress']) : '' ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div class="md:col-span-2 mt-2">
            <button type="submit"
                class="bg-blue-600 text-white font-black py-3 px-6 rounded-xl shadow-lg hover:bg-blue-700">
                <?= $editCompany ? 'Update Company' : 'Save Company' ?>
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b bg-slate-50/50 flex justify-between items-center text-sm">
        <h3 class="font-bold text-slate-800 uppercase tracking-wider">Company Registry</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead
                class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                <tr>
                    <th class="p-5">ID</th>
                    <th class="p-5">Company Name</th>
                    <th class="p-5">NTN</th>
                    <th class="p-5">STRN</th>
                    <th class="p-5">Province</th>
                    <th class="p-5">Status</th>
                    <th class="p-5 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php if (empty($companies)): ?>
                    <tr>
                        <td colspan="7" class="p-5 text-center text-slate-500">No companies found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($companies as $c): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-5 font-bold text-blue-600"><?= $c['CompanyID'] ?></td>
                            <td class="p-5 font-bold text-slate-700"><?= htmlspecialchars($c['CompanyName']) ?></td>
                            <td class="p-5 text-slate-500 font-medium"><?= htmlspecialchars($c['NTN']) ?></td>
                            <td class="p-5 text-slate-500 font-medium"><?= htmlspecialchars($c['STRN']) ?></td>
                            <td class="p-5"><span
                                    class="text-[10px] font-black bg-slate-100 px-2 py-1 rounded text-slate-500 uppercase"><?= htmlspecialchars($c['ProvinceCode']) ?></span>
                            </td>
                            <td class="p-5">
                                <?php if ($c['IsActive']): ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-600 shadow-sm">Active</span>
                                <?php else: ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-red-100 text-red-600 shadow-sm">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-5 text-center flex justify-center gap-2">
                                <a href="?edit=<?= $c['CompanyID'] ?>"
                                    class="text-blue-500 hover:text-blue-700 bg-blue-50 px-3 py-1.5 rounded-lg font-bold text-xs"><i
                                        class="fas fa-edit mr-1"></i> Edit</a>
                                <a href="?delete=<?= $c['CompanyID'] ?>"
                                    onclick="return confirm('Are you sure you want to delete this company?');"
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

<?php require_once 'includes/footer.php'; ?>