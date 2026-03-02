<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = $_POST['roleId'] ?? null;
        $branchId = $_POST['branchId'] ?? null;

        if (!empty($username) && !empty($password)) {
            $db->beginTransaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO DI_User (Username, PasswordHash, FullName, IsActive, CreatedOn) VALUES (:uname, :hash, :fname, 1, GETDATE())");
                $stmt->execute(['uname' => $username, 'hash' => $hash, 'fname' => $fullname]);
                $userId = $db->lastInsertId();

                if ($roleId) {
                    $db->prepare("INSERT INTO DI_UserRole (UserID, RoleID) VALUES (?, ?)")->execute([$userId, $roleId]);
                }
                if ($branchId) {
                    $db->prepare("INSERT INTO DI_UserBranch (UserID, BranchID) VALUES (?, ?)")->execute([$userId, $branchId]);
                }

                $db->commit();
                redirect('users.php');
            } catch (Exception $e) {
                $db->rollBack();
            }
        }
    }
}

$roles = $db->query("SELECT * FROM DI_Role")->fetchAll();
$branches = $db->query("SELECT BranchID, BranchName FROM DI_Branch WHERE IsActive = 1")->fetchAll();

$stmt = $db->query("SELECT UserID, Username, FullName, IsActive, CreatedOn FROM DI_User ORDER BY CreatedOn DESC");
$users = $stmt->fetchAll();

$page_title = 'Users';
require_once 'includes/header.php';
?>

<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Manage Users & Roles</h2>
        <p class="text-slate-500 font-medium">System administrative accounts</p>
    </div>
    <button onclick="document.getElementById('addUserForm').classList.toggle('hidden')"
        class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">
        <i class="fas fa-plus mr-2"></i> Add User
    </button>
</div>

<div id="addUserForm" class="hidden bg-white p-6 rounded-2xl shadow-sm border border-slate-200 mb-8">
    <h3 class="font-bold text-slate-700 mb-4 border-b pb-2">Add New User Account</h3>
    <form action="users.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="add_user">

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Username *</label>
            <input type="text" name="username" required
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Full Name</label>
            <input type="text" name="fullname"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div class="md:col-span-1">
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Role</label>
            <select name="roleId"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500 font-bold">
                <option value="">Select Role</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['RoleID'] ?>"><?= $r['RoleName'] ?></option><?php endforeach; ?>
            </select>
        </div>

        <div class="md:col-span-1">
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Assigned Branch</label>
            <select name="branchId"
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500 font-bold">
                <option value="">Select Branch</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['BranchID'] ?>"><?= $b['BranchName'] ?></option><?php endforeach; ?>
            </select>
        </div>

        <div class="md:col-span-2 mt-2">
            <button type="submit"
                class="bg-blue-600 text-white font-black py-3 px-6 rounded-xl shadow-lg hover:bg-blue-700">Save
                User</button>
        </div>
    </form>
</div>

<div class="mb-4">
    <a href="roles.php"
        class="text-blue-600 font-bold text-sm bg-blue-50 px-4 py-2 rounded-lg hover:bg-blue-100 transition-all"><i
            class="fas fa-user-tag mr-2"></i> Configure System Roles</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b bg-slate-50/50 flex justify-between items-center text-sm">
        <h3 class="font-bold text-slate-800 uppercase tracking-wider">System Users</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead
                class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                <tr>
                    <th class="p-5">Username</th>
                    <th class="p-5">Full Name</th>
                    <th class="p-5">Effective Role</th>
                    <th class="p-5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php
                $stmt = $db->query("SELECT u.*, r.RoleName FROM DI_User u LEFT JOIN DI_UserRole ur ON u.UserID = ur.UserID LEFT JOIN DI_Role r ON ur.RoleID = r.RoleID ORDER BY u.CreatedOn DESC");
                $userList = $stmt->fetchAll();
                ?>
                <?php foreach ($userList as $u): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-5 font-bold text-slate-700"><?= htmlspecialchars($u['Username']) ?></td>
                        <td class="p-5 text-slate-500 font-medium"><?= htmlspecialchars($u['FullName']) ?></td>
                        <td class="p-5 font-bold text-blue-600">
                            <?= htmlspecialchars($u['RoleName'] ?? 'No Role Assigned') ?></td>
                        <td class="p-5">
                            <span
                                class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?= $u['IsActive'] ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' ?>">
                                <?= $u['IsActive'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>