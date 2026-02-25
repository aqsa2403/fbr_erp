<?php
$page_title = 'Users';
require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO DI_User (Username, PasswordHash, FullName, IsActive, CreatedOn) VALUES (:uname, :hash, :fname, 1, GETDATE())");
        $stmt->execute([
            'uname' => $username,
            'hash' => $hash,
            'fname' => $fullname
        ]);
        redirect('users.php');
    }
}

$stmt = $db->query("SELECT UserID, Username, FullName, IsActive, CreatedOn FROM DI_User ORDER BY CreatedOn DESC");
$users = $stmt->fetchAll();
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

        <div class="md:col-span-2">
            <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Password *</label>
            <input type="password" name="password" required
                class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-xl outline-none focus:border-blue-500">
        </div>

        <div class="md:col-span-2 mt-2">
            <button type="submit"
                class="bg-blue-600 text-white font-black py-3 px-6 rounded-xl shadow-lg hover:bg-blue-700">Save
                User</button>
        </div>
    </form>
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
                    <th class="p-5">ID</th>
                    <th class="p-5">Username</th>
                    <th class="p-5">Full Name</th>
                    <th class="p-5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="4" class="p-5 text-center text-slate-500">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-5 font-bold text-blue-600"><?= $u['UserID'] ?></td>
                            <td class="p-5 font-bold text-slate-700"><?= htmlspecialchars($u['Username']) ?></td>
                            <td class="p-5 text-slate-500 font-medium"><?= htmlspecialchars($u['FullName']) ?></td>
                            <td class="p-5">
                                <?php if ($u['IsActive']): ?>
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