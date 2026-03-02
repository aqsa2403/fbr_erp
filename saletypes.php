<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $code = trim($_POST['code']);
    $desc = trim($_POST['description']);
    if ($_POST['action'] === 'add_saletype') {
        $stmt = $db->prepare("INSERT INTO DI_SaleType (SaleTypeCode, Description, IsActive) VALUES (?, ?, 1)");
        $stmt->execute([$code, $desc]);
        redirect('saletypes.php');
    } elseif ($_POST['action'] === 'edit_saletype') {
        $stmt = $db->prepare("UPDATE DI_SaleType SET SaleTypeCode = ?, Description = ? WHERE SaleTypeID = ?");
        $stmt->execute([$code, $desc, $_POST['id']]);
        redirect('saletypes.php');
    }
}

$editSt = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM DI_SaleType WHERE SaleTypeID = ?");
    $stmt->execute([$_GET['edit']]);
    $editSt = $stmt->fetch();
}

$types = $db->query("SELECT * FROM DI_SaleType ORDER BY SaleTypeID DESC")->fetchAll();

$page_title = 'Sale Types';
require_once 'includes/header.php';
?>

<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Sale Types</h2>
        <p class="text-slate-500 font-medium">Categorize your sales transactions</p>
    </div>
    <button onclick="document.getElementById('addTypeForm').classList.toggle('hidden')"
        class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">
        <i class="fas fa-plus mr-2"></i> Add Type
    </button>
</div>

<div id="addTypeForm"
    class="<?= $editSt ? '' : 'hidden ' ?>bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8 max-w-2xl">
    <form action="saletypes.php" method="POST" class="grid grid-cols-1 gap-4">
        <input type="hidden" name="action" value="<?= $editSt ? 'edit_saletype' : 'add_saletype' ?>">
        <?php if ($editSt): ?><input type="hidden" name="id" value="<?= $editSt['SaleTypeID'] ?>">
        <?php endif; ?>
        <input type="text" name="code" placeholder="Sale Type Code" required
            value="<?= $editSt ? htmlspecialchars($editSt['SaleTypeCode']) : '' ?>"
            class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500 font-bold">
        <input type="text" name="description" placeholder="Description"
            value="<?= $editSt ? htmlspecialchars($editSt['Description']) : '' ?>"
            class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        <button type="submit" class="bg-blue-600 text-white font-black py-3 rounded-xl shadow-lg hover:bg-blue-700">
            <?= $editSt ? 'Update' : 'Save' ?> Sale Type
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-black border-b">
            <tr>
                <th class="p-5 text-left">Code</th>
                <th class="p-5 text-left">Description</th>
                <th class="p-5 text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($types as $t): ?>
                <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                    <td class="p-5 font-bold text-slate-700">
                        <?= htmlspecialchars($t['SaleTypeCode']) ?>
                    </td>
                    <td class="p-5 text-slate-500">
                        <?= htmlspecialchars($t['Description']) ?>
                    </td>
                    <td class="p-5 text-center">
                        <a href="?edit=<?= $t['SaleTypeID'] ?>" class="text-blue-500 hover:text-blue-700 mx-2"><i
                                class="fas fa-edit"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>