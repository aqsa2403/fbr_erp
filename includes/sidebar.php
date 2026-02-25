<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="fixed left-0 top-0 h-full w-64 bg-slate-900 text-white p-6 shadow-2xl z-20">
    <div class="flex items-center gap-3 mb-10 border-b border-slate-800 pb-5">
        <div class="bg-blue-600 p-2 rounded-lg"><i class="fas fa-building text-xl"></i></div>
        <h2 class="text-xl font-black tracking-tighter italic">FBR <span class="text-blue-500 text-sm">ERP</span></h2>
    </div>

    <nav class="space-y-3">
        <a href="index.php"
            class="nav-item flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'index.php' ? 'bg-blue-600 text-white shadow-lg' : 'hover:bg-slate-800 text-slate-400' ?>">
            <i class="fas fa-chart-pie w-6 text-center mr-2"></i> Dashboard
        </a>
        <a href="invoices.php"
            class="nav-item flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'invoices.php' ? 'bg-blue-600 text-white shadow-lg' : 'hover:bg-slate-800 text-slate-400' ?>">
            <i class="fas fa-file-invoice-dollar w-6 text-center mr-2"></i> Invoices
        </a>
        <a href="companies.php"
            class="nav-item flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'companies.php' ? 'bg-blue-600 text-white shadow-lg' : 'hover:bg-slate-800 text-slate-400' ?>">
            <i class="fas fa-city w-6 text-center mr-2"></i> Companies
        </a>
        <a href="branches.php"
            class="nav-item flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'branches.php' ? 'bg-blue-600 text-white shadow-lg' : 'hover:bg-slate-800 text-slate-400' ?>">
            <i class="fas fa-code-branch w-6 text-center mr-2"></i> Branches
        </a>
        <a href="users.php"
            class="nav-item flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'users.php' ? 'bg-blue-600 text-white shadow-lg' : 'hover:bg-slate-800 text-slate-400' ?>">
            <i class="fas fa-users w-6 text-center mr-2"></i> Users & Roles
        </a>
        <a href="fbr_sync.php"
            class="nav-item flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'fbr_sync.php' ? 'bg-blue-600 text-white shadow-lg' : 'hover:bg-slate-800 text-slate-400' ?>">
            <i class="fas fa-sync-alt w-6 text-center mr-2"></i> FBR Sync Queue
        </a>
    </nav>
</div>