<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_uom') {
        $code = trim($_POST['uomCode']);
        $desc = trim($_POST['description']);
        if (!empty($code)) {
            $stmt = $db->prepare("INSERT INTO DI_UOM (UOMCode, Description, IsActive) VALUES (?, ?, 1)");
            $stmt->execute([$code, $desc]);
            redirect('uoms.php');
        }
    } elseif ($_POST['action'] === 'edit_uom') {
        $id = $_POST['uomId'];
        $code = trim($_POST['uomCode']);
        $desc = trim($_POST['description']);
        if (!empty($code) && $id) {
            $stmt = $db->prepare("UPDATE DI_UOM SET UOMCode = ?, Description = ? WHERE UOMID = ?");
            $stmt->execute([$code, $desc, $id]);
            redirect('uoms.php');
        }
    }
}

$editUom = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM DI_UOM WHERE UOMID = ?");
    $stmt->execute([$_GET['edit']]);
    $editUom = $stmt->fetch();
}

if (isset($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM DI_UOM WHERE UOMID = ?");
        $stmt->execute([$_GET['delete']]);
    } catch (PDOException $e) {
    }
    redirect('uoms.php');
}

$uoms = $db->query("SELECT * FROM DI_UOM ORDER BY UOMID DESC")->fetchAll();

$page_title = 'Unit of Measure (UOM)';
require_once 'includes/header.php';
?>

<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Units of Measure</h2>
        <p class="text-slate-500 font-medium">Manage product measurement units (UOM)</p>
    </div>
    <button onclick="document.getElementById('addUomForm').classList.toggle('hidden')"
        class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">
        <i class="fas fa-plus mr-2"></i> Add UOM
    </button>
</div>

<div id="addUomForm"
    class="<?= $editUom ? '' : 'hidden ' ?>bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8 max-w-2xl">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold text-slate-700">
            <?= $editUom ? 'Edit UOM' : 'Add New UOM' ?>
        </h3>
        <?php if ($editUom): ?><a href="uoms.php" class="text-slate-400 hover:text-red-500"><i
                    class="fas fa-times"></i></a>
        <?php endif; ?>
    </div>
    <form action="uoms.php" method="POST" class="grid grid-cols-1 gap-4">
        <input type="hidden" name="action" value="<?= $editUom ? 'edit_uom' : 'add_uom' ?>">
        <?php if ($editUom): ?><input type="hidden" name="uomId" value="<?= $editUom['UOMID'] ?>">
        <?php endif; ?>
        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">UOM Code *</label>
            <input type="text" name="uomCode" required
                value="<?= $editUom ? htmlspecialchars($editUom['UOMCode']) : '' ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>
        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Description</label>
            <input type="text" name="description"
                value="<?= $editUom ? htmlspecialchars($editUom['Description']) : '' ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>
        <button type="submit"
            class="bg-blue-600 text-white font-black py-3 px-6 rounded-xl shadow-lg hover:bg-blue-700">
            <?= $editUom ? 'Update' : 'Save' ?> UOM
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-widest border-b">
            <tr>
                <th class="p-5">Code</th>
                <th class="p-5">Description</th>
                <th class="p-5 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 text-sm">
            <?php foreach ($uoms as $u): ?>
                <tr class="hover:bg-slate-50">
                    <td class="p-5 font-bold text-blue-600">
                        <?= htmlspecialchars($u['UOMCode']) ?>
                    </td>
                    <td class="p-5 text-slate-600">
                        <?= htmlspecialchars($u['Description']) ?>
                    </td>
                    <td class="p-5 text-center flex justify-center gap-2">
                        <a href="?edit=<?= $u['UOMID'] ?>"
                            class="text-blue-500 hover:text-blue-700 bg-blue-50 px-3 py-1.5 rounded-lg font-bold text-xs"><i
                                class="fas fa-edit"></i></a>
                        <a href="?delete=<?= $u['UOMID'] ?>" onclick="return confirm('Delete this UOM?');"
                            class="text-red-500 hover:text-red-700 bg-red-50 px-3 py-1.5 rounded-lg font-bold text-xs"><i
                                class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>