<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $roleName = trim($_POST['roleName']);
    if ($_POST['action'] === 'add_role' && !empty($roleName)) {
        $db->prepare("INSERT INTO DI_Role (RoleName) VALUES (?)")->execute([$roleName]);
        redirect('roles.php');
    }
}

if (isset($_GET['delete'])) {
    try {
        $db->prepare("DELETE FROM DI_Role WHERE RoleID = ?")->execute([$_GET['delete']]);
    } catch (PDOException $e) {
    }
    redirect('roles.php');
}

$roles = $db->query("SELECT * FROM DI_Role ORDER BY RoleID DESC")->fetchAll();

$page_title = 'Roles Management';
require_once 'includes/header.php';
?>

<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">System Roles</h2>
        <p class="text-slate-500 font-medium">Define access control levels</p>
    </div>
    <button onclick="document.getElementById('addRoleForm').classList.toggle('hidden')"
        class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">
        <i class="fas fa-plus mr-2"></i> Add Role
    </button>
</div>

<div id="addRoleForm" class="hidden bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8 max-w-md">
    <form action="roles.php" method="POST" class="flex gap-3">
        <input type="hidden" name="action" value="add_role">
        <input type="text" name="roleName" placeholder="Role Name (e.g. Manager)" required
            class="flex-1 bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500 font-bold">
        <button type="submit"
            class="bg-blue-600 text-white font-black py-3 px-6 rounded-xl shadow-lg hover:bg-blue-700">Save</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php foreach ($roles as $r): ?>
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all group">
            <div class="flex justify-between items-start">
                <div
                    class="bg-blue-50 text-blue-600 p-4 rounded-2xl mb-4 group-hover:bg-blue-600 group-hover:text-white transition-all">
                    <i class="fas fa-user-shield text-xl"></i>
                </div>
                <a href="?delete=<?= $r['RoleID'] ?>" onclick="return confirm('Delete this role?');"
                    class="text-slate-300 hover:text-red-500 transition-all"><i class="fas fa-trash-alt"></i></a>
            </div>
            <h4 class="font-black text-slate-800 tracking-tight text-lg mb-1">
                <?= htmlspecialchars($r['RoleName']) ?>
            </h4>
            <p class="text-xs text-slate-400 font-medium uppercase tracking-widest">System Level Role</p>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>