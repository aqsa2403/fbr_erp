<?php
$page_title = 'FBR Sync Queue';
require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();

$stmt = $db->query("
    SELECT r.*, i.InvoiceNumber 
    FROM DI_FBR_RetryQueue r 
    LEFT JOIN DI_Invoice_Master i ON r.InvoiceID = i.InvoiceID 
    ORDER BY r.CreatedOn DESC
");
$queue = $stmt->fetchAll();
?>

<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">FBR Synchronization Queue</h2>
        <p class="text-slate-500 font-medium">Monitor invoices that failed to sync with the FBR system and are queued
            for retry.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 text-center">
    <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-l-red-500 border border-slate-200">
        <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest">Failed Syncs</p>
        <p class="text-3xl font-black text-slate-800 mt-1">
            <?php
            $failStmt = $db->query("SELECT COUNT(*) FROM DI_FBR_RetryQueue WHERE Status = 'Failed'");
            echo $failStmt->fetchColumn() ?: 0;
            ?>
        </p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-l-orange-500 border border-slate-200">
        <p class="text-[10px] font-bold text-orange-500 uppercase tracking-widest">Pending Retry</p>
        <p class="text-3xl font-black text-slate-800 mt-1">
            <?php
            $pendStmt = $db->query("SELECT COUNT(*) FROM DI_FBR_RetryQueue WHERE Status = 'Pending'");
            echo $pendStmt->fetchColumn() ?: 0;
            ?>
        </p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b bg-slate-50/50 flex justify-between items-center text-sm">
        <h3 class="font-bold text-slate-800 uppercase tracking-wider">Retry Queue Logs</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead
                class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                <tr>
                    <th class="p-5">Retry ID</th>
                    <th class="p-5">Invoice No</th>
                    <th class="p-5">Status</th>
                    <th class="p-5">Retry Count</th>
                    <th class="p-5">Next Retry</th>
                    <th class="p-5">Error Message</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php if (empty($queue)): ?>
                    <tr>
                        <td colspan="6" class="p-5 text-center text-slate-500">Queue is empty. All systems normal.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($queue as $q): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-5 font-bold text-blue-600"><?= $q['RetryID'] ?></td>
                            <td class="p-5 font-bold text-slate-700"><?= htmlspecialchars($q['InvoiceNumber']) ?></td>
                            <td class="p-5">
                                <?php if ($q['Status'] == 'Pending'): ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-orange-100 text-orange-600 shadow-sm">Pending</span>
                                <?php elseif ($q['Status'] == 'Failed'): ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-red-100 text-red-600 shadow-sm">Failed</span>
                                <?php else: ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-600 shadow-sm"><?= htmlspecialchars($q['Status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-5 font-bold text-slate-700"><?= $q['RetryCount'] ?></td>
                            <td class="p-5 text-slate-500 font-medium">
                                <?= $q['NextRetryTime'] ? date('Y-m-d H:i', strtotime($q['NextRetryTime'])) : '-' ?></td>
                            <td class="p-5 text-red-500 text-xs font-mono max-w-xs truncate"
                                title="<?= htmlspecialchars($q['ErrorMessage']) ?>">
                                <?= htmlspecialchars($q['ErrorMessage']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>