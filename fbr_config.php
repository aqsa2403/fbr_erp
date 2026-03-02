<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    $branchId = $_POST['branchId'];
    $baseUrl = trim($_POST['baseUrl']);
    $clientId = trim($_POST['clientId']);
    $clientSecret = trim($_POST['clientSecret']);

    // Check if exists
    $check = $db->prepare("SELECT COUNT(*) FROM DI_BranchFBR_Config WHERE BranchID = ?");
    $check->execute([$branchId]);

    if ($check->fetchColumn() > 0) {
        $stmt = $db->prepare("UPDATE DI_BranchFBR_Config SET FBR_BaseURL = ?, FBR_ClientID = ?, FBR_ClientSecret = ? WHERE BranchID = ?");
        $stmt->execute([$baseUrl, $clientId, $clientSecret, $branchId]);
    } else {
        $stmt = $db->prepare("INSERT INTO DI_BranchFBR_Config (BranchID, FBR_BaseURL, FBR_ClientID, FBR_ClientSecret) VALUES (?, ?, ?, ?)");
        $stmt->execute([$branchId, $baseUrl, $clientId, $clientSecret]);
    }
    redirect('fbr_config.php');
}

$branches = $db->query("SELECT b.BranchID, b.BranchName, c.FBR_BaseURL, c.FBR_ClientID, c.FBR_ClientSecret 
                        FROM DI_Branch b 
                        LEFT JOIN DI_BranchFBR_Config c ON b.BranchID = c.BranchID 
                        WHERE b.IsActive = 1")->fetchAll();

$page_title = 'FBR Configuration';
require_once 'includes/header.php';
?>

<div class="mb-8">
    <h2 class="text-2xl font-black text-slate-800 tracking-tight">FBR API Configuration</h2>
    <p class="text-slate-500 font-medium">Manage API credentials and endpoints for each branch</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <?php foreach ($branches as $b): ?>
        <div
            class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all border-l-8 border-l-blue-600">
            <div class="flex items-center gap-4 mb-6">
                <div class="bg-blue-50 text-blue-600 p-4 rounded-2xl">
                    <i class="fas fa-key text-xl"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-800 text-xl tracking-tight">
                        <?= htmlspecialchars($b['BranchName']) ?>
                    </h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Branch API Settings</p>
                </div>
            </div>

            <form action="fbr_config.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="save_config">
                <input type="hidden" name="branchId" value="<?= $b['BranchID'] ?>">

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block tracking-wider">Base API
                        URL</label>
                    <input type="text" name="baseUrl" value="<?= htmlspecialchars($b['FBR_BaseURL'] ?? '') ?>"
                        placeholder="https://fbr.gov.pk/api/..."
                        class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-2xl outline-none focus:border-blue-500 font-medium transition-all">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block tracking-wider">Client
                            ID</label>
                        <input type="text" name="clientId" value="<?= htmlspecialchars($b['FBR_ClientID'] ?? '') ?>"
                            class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-2xl outline-none focus:border-blue-500 font-mono text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase mb-1 block tracking-wider">Client
                            Secret</label>
                        <input type="password" name="clientSecret"
                            value="<?= htmlspecialchars($b['FBR_ClientSecret'] ?? '') ?>"
                            class="w-full bg-slate-50 border-2 border-slate-100 p-3 rounded-2xl outline-none focus:border-blue-500 font-mono text-sm">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-slate-900 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-slate-800 transition-all flex items-center justify-center gap-3 mt-4">
                    <i class="fas fa-save"></i> Save Configuration
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>