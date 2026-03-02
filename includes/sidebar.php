<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
    .custom-sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-sidebar::-webkit-scrollbar-track {
        background: #0f172a;
    }

    .custom-sidebar::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 10px;
    }

    .custom-sidebar::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }
</style>
<div
    class="fixed left-0 top-0 h-screen w-64 bg-slate-900 text-white p-6 shadow-2xl z-20 overflow-y-auto custom-sidebar">
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
        <div class="pt-5 border-t border-slate-800 mt-5">
            <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 px-3">Master Data</h3>
            <a href="uoms.php"
                class="flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'uoms.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 text-slate-400' ?>">
                <i class="fas fa-weight-hanging w-6 text-center mr-2"></i> Units (UOM)
            </a>
            <a href="sros.php"
                class="flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'sros.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 text-slate-400' ?>">
                <i class="fas fa-list-ol w-6 text-center mr-2"></i> SRO & Schedules
            </a>
            <a href="saletypes.php"
                class="flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'saletypes.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 text-slate-400' ?>">
                <i class="fas fa-tags w-6 text-center mr-2"></i> Sale Types
            </a>
            <a href="audit_logs.php"
                class="flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'audit_logs.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 text-slate-400' ?>">
                <i class="fas fa-history w-6 text-center mr-2"></i> Audit Logs
            </a>
            <a href="fbr_config.php"
                class="flex items-center p-3 rounded-xl transition-all <?= $currentPage === 'fbr_config.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 text-slate-400' ?>">
                <i class="fas fa-cogs w-6 text-center mr-2"></i> FBR Config
            </a>
        </div>
    </nav>
</div>