<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

$logs = $db->query("SELECT a.*, u.Username FROM DI_AuditLog a LEFT JOIN DI_User u ON a.ChangedBy = u.UserID ORDER BY ChangedOn DESC")->fetchAll();

$page_title = 'Audit Logs';
require_once 'includes/header.php';
?>

<div class="mb-8">
    <h2 class="text-2xl font-black text-slate-800 tracking-tight">System Audit Trail</h2>
    <p class="text-slate-500 font-medium">Monitor all changes and administrative actions</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-xs">
        <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-black border-b tracking-widest">
            <tr>
                <th class="p-5 text-left">Timestamp</th>
                <th class="p-5 text-left">Action</th>
                <th class="p-5 text-left">Table</th>
                <th class="p-5 text-left">User</th>
                <th class="p-5 text-left">Changes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($logs as $l): ?>
                <tr class="hover:bg-slate-50/50">
                    <td class="p-5 text-slate-400 font-medium">
                        <?= $l['ChangedOn'] ?>
                    </td>
                    <td class="p-5">
                        <span
                            class="px-2 py-1 rounded-lg font-black uppercase text-[9px] <?= $l['ActionType'] === 'INSERT' ? 'bg-emerald-100 text-emerald-600' : ($l['ActionType'] === 'UPDATE' ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600') ?>">
                            <?= $l['ActionType'] ?>
                        </span>
                    </td>
                    <td class="p-5 font-bold text-slate-700">
                        <?= $l['TableName'] ?>
                    </td>
                    <td class="p-5 font-medium text-slate-600">
                        <?= htmlspecialchars($l['Username'] ?? 'System') ?>
                    </td>
                    <td class="p-5">
                        <div class="max-w-xs truncate text-slate-400 italic" title="<?= htmlspecialchars($l['NewData']) ?>">
                            <?= htmlspecialchars($l['NewData']) ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>