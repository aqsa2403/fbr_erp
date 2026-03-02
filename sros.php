<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_sro') {
        $sched = trim($_POST['scheduleNo']);
        $serial = trim($_POST['serialNo']);
        $desc = trim($_POST['description']);
        $stmt = $db->prepare("INSERT INTO DI_SRO (SROScheduleNo, SROItemSerialNo, Description, IsActive) VALUES (?, ?, ?, 1)");
        $stmt->execute([$sched, $serial, $desc]);
        redirect('sros.php');
    } elseif ($_POST['action'] === 'edit_sro') {
        $id = $_POST['sroId'];
        $sched = trim($_POST['scheduleNo']);
        $serial = trim($_POST['serialNo']);
        $desc = trim($_POST['description']);
        $stmt = $db->prepare("UPDATE DI_SRO SET SROScheduleNo = ?, SROItemSerialNo = ?, Description = ? WHERE SROID = ?");
        $stmt->execute([$sched, $serial, $desc, $id]);
        redirect('sros.php');
    }
}

$editSro = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM DI_SRO WHERE SROID = ?");
    $stmt->execute([$_GET['edit']]);
    $editSro = $stmt->fetch();
}

if (isset($_GET['delete'])) {
    try {
        $db->prepare("DELETE FROM DI_SRO WHERE SROID = ?")->execute([$_GET['delete']]);
    } catch (PDOException $e) {
    }
    redirect('sros.php');
}

$srosList = $db->query("SELECT * FROM DI_SRO ORDER BY SROID DESC")->fetchAll();

$page_title = 'SRO & Schedules';
require_once 'includes/header.php';
?>

<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">SRO Management</h2>
        <p class="text-slate-500 font-medium">Manage SRO Schedules and Item Serial Numbers</p>
    </div>
    <button onclick="document.getElementById('addSroForm').classList.toggle('hidden')"
        class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">
        <i class="fas fa-plus mr-2"></i> Add SRO
    </button>
</div>

<div id="addSroForm"
    class="<?= $editSro ? '' : 'hidden ' ?>bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8 max-w-2xl">
    <h3 class="font-bold text-slate-700 mb-4">
        <?= $editSro ? 'Edit SRO' : 'Add New SRO' ?>
    </h3>
    <form action="sros.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="<?= $editSro ? 'edit_sro' : 'add_sro' ?>">
        <?php if ($editSro): ?><input type="hidden" name="sroId" value="<?= $editSro['SROID'] ?>">
        <?php endif; ?>
        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Schedule No</label>
            <input type="text" name="scheduleNo"
                value="<?= $editSro ? htmlspecialchars($editSro['SROScheduleNo']) : '' ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>
        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Serial No</label>
            <input type="text" name="serialNo"
                value="<?= $editSro ? htmlspecialchars($editSro['SROItemSerialNo']) : '' ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>
        <div class="md:col-span-2">
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Description</label>
            <input type="text" name="description"
                value="<?= $editSro ? htmlspecialchars($editSro['Description']) : '' ?>"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>
        <button type="submit"
            class="bg-blue-600 text-white font-black py-3 px-6 rounded-xl shadow-lg hover:bg-blue-700 md:col-span-2">
            <?= $editSro ? 'Update' : 'Save' ?> SRO
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-widest border-b">
            <tr>
                <th class="p-5">Schedule</th>
                <th class="p-5">Serial</th>
                <th class="p-5">Description</th>
                <th class="p-5 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 text-sm">
            <?php foreach ($srosList as $s): ?>
                <tr class="hover:bg-slate-50">
                    <td class="p-5 font-bold text-slate-700">
                        <?= htmlspecialchars($s['SROScheduleNo']) ?>
                    </td>
                    <td class="p-5 text-slate-600">
                        <?= htmlspecialchars($s['SROItemSerialNo']) ?>
                    </td>
                    <td class="p-5 text-slate-500">
                        <?= htmlspecialchars($s['Description']) ?>
                    </td>
                    <td class="p-5 text-center flex justify-center gap-2">
                        <a href="?edit=<?= $s['SROID'] ?>" class="text-blue-500 hover:text-blue-700 font-bold"><i
                                class="fas fa-edit"></i></a>
                        <a href="?delete=<?= $s['SROID'] ?>" onclick="return confirm('Delete SRO?');"
                            class="text-red-500 hover:text-red-700 font-bold"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>