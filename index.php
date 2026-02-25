<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();

$stmt = $db->query("SELECT COUNT(*) as total FROM DI_Invoice_Master");
$totalInvoices = $stmt->fetchColumn() ?: 0;

$stmt = $db->query("SELECT SUM(TotalInvoiceValue) as totalSales FROM DI_Invoice_Master");
$totalSales = $stmt->fetchColumn() ?: 0;

$stmt = $db->query("SELECT COUNT(*) FROM DI_Company WHERE IsActive = 1");
$totalCompanies = $stmt->fetchColumn() ?: 0;

$stmt = $db->query("SELECT COUNT(*) FROM DI_Invoice_Master WHERE IsSubmittedToFBR = 0");
$pendingSyncs = $stmt->fetchColumn() ?: 0;


$submittedCount = $totalInvoices - $pendingSyncs;
$statusData = [
    'submitted' => $submittedCount,
    'pending' => $pendingSyncs
];

$stmt = $db->query("
    SELECT CAST(CreatedOn as DATE) as SaleDate, SUM(TotalInvoiceValue) as DailyTotal 
    FROM DI_Invoice_Master 
    WHERE CreatedOn >= DATEADD(day, -7, GETDATE())
    GROUP BY CAST(CreatedOn as DATE)
    ORDER BY SaleDate ASC
");
$weeklyDataRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$weekLabels = [];
$weekTotals = [];

foreach ($weeklyDataRaw as $row) {
    $weekLabels[] = date('M d', strtotime($row['SaleDate']));
    $weekTotals[] = (float) $row['DailyTotal'];
}

$stmt = $db->query("SELECT TOP 5 InvoiceNumber, CAST(InvoiceDate as DATE) as InvoiceDate, BuyerBusinessName, TotalInvoiceValue, IsSubmittedToFBR FROM DI_Invoice_Master ORDER BY CreatedOn DESC");
$recentInvoices = $stmt->fetchAll();
?>

<div id="stats-section" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 text-center">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Invoices</p>
        <p class="text-3xl font-black text-slate-800 mt-1"><?= number_format($totalInvoices) ?></p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-l-orange-500 border border-slate-200">
        <p class="text-[10px] font-bold text-orange-500 uppercase tracking-widest">Pending FBR Syncs</p>
        <p class="text-3xl font-black text-slate-800 mt-1"><?= number_format($pendingSyncs) ?></p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Sales Volume</p>
        <p class="text-2xl font-black text-blue-600 mt-1">Rs. <?= number_format($totalSales) ?></p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Registered Companies</p>
        <p class="text-3xl font-black text-emerald-600 mt-1"><?= number_format($totalCompanies) ?></p>
    </div>
</div>

<div id="charts-section" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2"><i class="fas fa-chart-pie text-blue-500"></i>
            FBR Integration Status</h3>
        <div class="h-64"><canvas id="statusChart"></canvas></div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2"><i
                class="fas fa-chart-line text-emerald-500"></i> Past 7 Days Sales Pipeline</h3>
        <div class="h-64"><canvas id="movementChart"></canvas></div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b bg-slate-50/50 flex justify-between items-center text-sm">
        <h3 id="table-title" class="font-bold text-slate-800 uppercase tracking-wider">Latest Invoices Generated</h3>
        <a href="invoices.php"
            class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200">View
            All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead
                class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                <tr>
                    <th class="p-5">Invoice No</th>
                    <th class="p-5">Date</th>
                    <th class="p-5">Buyer</th>
                    <th class="p-5">Total Value</th>
                    <th class="p-5">Sync Status</th>
                </tr>
            </thead>
            <tbody id="table-body" class="divide-y divide-slate-50 text-sm">
                <?php if (empty($recentInvoices)): ?>
                    <tr>
                        <td colspan="5" class="p-5 text-center text-slate-500">No invoices yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentInvoices as $inv): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-5 font-bold text-blue-600"><?= htmlspecialchars($inv['InvoiceNumber']) ?></td>
                            <td class="p-5 text-slate-700"><?= htmlspecialchars($inv['InvoiceDate']) ?></td>
                            <td class="p-5 font-bold text-slate-700"><?= htmlspecialchars($inv['BuyerBusinessName']) ?></td>
                            <td class="p-5 font-black">Rs. <?= number_format($inv['TotalInvoiceValue'], 2) ?></td>
                            <td class="p-5">
                                <?php if ($inv['IsSubmittedToFBR']): ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-600 shadow-sm">Synced</span>
                                <?php else: ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-orange-100 text-orange-600">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>

    document.addEventListener('DOMContentLoaded', function () {


        const ctx1 = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Submitted to FBR', 'Pending Queue'],
                datasets: [{
                    data: [<?= $statusData['submitted'] ?>, <?= $statusData['pending'] ?>],
                    backgroundColor: ['#10b981', '#f97316'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });


        const ctx2 = document.getElementById('movementChart').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: <?= json_encode($weekLabels) ?>,
                datasets: [
                    {
                        label: 'Daily Sales (Rs)',
                        data: <?= json_encode($weekTotals) ?>,
                        borderColor: '#3b82f6',
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

    });
</script>

<?php require_once 'includes/footer.php'; ?>