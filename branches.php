<?php
$page_title = 'Branches';
require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();

$companiesStmt = $db->query("SELECT CompanyID, CompanyName FROM DI_Company WHERE IsActive = 1");
$companies = $companiesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_branch') {
    $companyId = $_POST['companyId'] ?? null;
    $branchCode = trim($_POST['branchCode'] ?? '');
    $branchName = trim($_POST['branchName'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $provinceCode = trim($_POST['provinceCode'] ?? '');
    $posId = trim($_POST['posId'] ?? '');
    $isHeadOffice = isset($_POST['isHeadOffice']) ? 1 : 0;

    if (!empty($companyId) && !empty($branchCode) && !empty($branchName)) {
        $stmt = $db->prepare("INSERT INTO DI_Branch (CompanyID, BranchCode, BranchName, BranchAddress, ProvinceCode, POSID, IsHeadOffice, IsActive, CreatedOn) VALUES (:cid, :bcode, :bname, :addr, :pcode, :posid, :isho, 1, GETDATE())");
        $stmt->execute([
            'cid' => $companyId,
            'bcode' => $branchCode,
            'bname' => $branchName,
            'addr' => $address,
            'pcode' => $provinceCode,
            'posid' => $posId,
            'isho' => $isHeadOffice
        ]);
        redirect('branches.php');
    }
}

$stmt = $db->query("SELECT b.*, c.CompanyName FROM DI_Branch b LEFT JOIN DI_Company c ON b.CompanyID = c.CompanyID ORDER BY b.CreatedOn DESC");
$branches = $stmt->fetchAll();
?>

<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Manage Branches (POS)</h2>
        <p class="text-slate-500 font-medium">Point of sale locations</p>
    </div>
    <button onclick="document.getElementById('addBranchForm').classList.toggle('hidden')"
        class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">
        <i class="fas fa-plus mr-2"></i> Add Branch
    </button>
</div>

<div id="addBranchForm" class="hidden bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8">
    <h3 class="font-bold text-slate-700 mb-4 border-b pb-2">Add New Branch</h3>
    <form action="branches.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="add_branch">

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Company *</label>
            <select name="companyId" required
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
                <option value="">Select Company</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?= $c['CompanyID'] ?>"><?= htmlspecialchars($c['CompanyName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Branch Code *</label>
            <input type="text" name="branchCode" required
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Branch Name *</label>
            <input type="text" name="branchName" required
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">POS ID</label>
            <input type="text" name="posId"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Province Code</label>
            <select name="provinceCode"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
                <option value="PB">Punjab (PB)</option>
                <option value="SD">Sindh (SD)</option>
                <option value="KP">KPK (KP)</option>
                <option value="BA">Balochistan (BA)</option>
                <option value="IS">Islamabad (IS)</option>
            </select>
        </div>

        <div class="flex items-center gap-2 mt-2">
            <input type="checkbox" name="isHeadOffice" id="isHeadOffice" class="w-5 h-5 accent-blue-600 rounded">
            <label for="isHeadOffice" class="text-sm font-bold text-slate-700 cursor-pointer">Is Head Office?</label>
        </div>

        <div class="md:col-span-2">
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Branch Address</label>
            <input type="text" name="address"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div class="md:col-span-2 mt-2">
            <button type="submit"
                class="bg-blue-600 text-white font-black py-3 px-6 rounded-xl shadow-lg hover:bg-blue-700">Save
                Branch</button>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b bg-slate-50/50 flex justify-between items-center text-sm">
        <h3 class="font-bold text-slate-800 uppercase tracking-wider">Branch/POS Locations</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead
                class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                <tr>
                    <th class="p-5">Branch Code</th>
                    <th class="p-5">Branch Name</th>
                    <th class="p-5">Company</th>
                    <th class="p-5">POS ID</th>
                    <th class="p-5">Head Office</th>
                    <th class="p-5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php if (empty($branches)): ?>
                    <tr>
                        <td colspan="6" class="p-5 text-center text-slate-500">No branches found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($branches as $b): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-5 font-bold text-blue-600"><?= htmlspecialchars($b['BranchCode']) ?></td>
                            <td class="p-5 font-bold text-slate-700"><?= htmlspecialchars($b['BranchName']) ?></td>
                            <td class="p-5 text-slate-500 font-medium"><?= htmlspecialchars($b['CompanyName']) ?></td>
                            <td class="p-5 text-slate-500 font-medium"><?= htmlspecialchars($b['POSID']) ?></td>
                            <td class="p-5">
                                <?= $b['IsHeadOffice'] ? '<span class="text-[10px] font-black bg-blue-100 px-2 py-1 rounded text-blue-600 uppercase">Yes</span>' : '<span class="text-[10px] font-black bg-slate-100 px-2 py-1 rounded text-slate-500 uppercase">No</span>' ?>
                            </td>
                            <td class="p-5">
                                <?php if ($b['IsActive']): ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-600 shadow-sm">Active</span>
                                <?php else: ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-red-100 text-red-600 shadow-sm">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>