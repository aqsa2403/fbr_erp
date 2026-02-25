<?php
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT UserID, Username, PasswordHash, FullName FROM DI_User WHERE Username = :username AND IsActive = 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['PasswordHash'])) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['full_name'] = $user['FullName'];

            redirect('index.php');
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FBR DI ERP</title>
   
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-900 font-sans text-slate-900 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-6">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-slate-900 p-8 text-center border-b border-slate-800">
                <div class="inline-block bg-blue-600 p-3 rounded-xl mb-4"><i
                        class="fas fa-building text-2xl text-white"></i></div>
                <h2 class="text-3xl font-black text-white tracking-tighter italic">FBR <span
                        class="text-blue-500 text-lg">ERP</span></h2>
                <p class="text-slate-400 mt-2 font-medium">Digital Invoicing Portal</p>
            </div>

            <div class="p-8">
                <?php if (!empty($error)): ?>
                    <div
                        class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 font-bold text-center text-sm shadow-sm">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="space-y-5">
                    <div>
                        <label for="username"
                            class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Username</label>
                        <input type="text" id="username" name="username" required autofocus placeholder="e.g. admin"
                            autocomplete="username"
                            class="w-full bg-slate-50 border-2 border-slate-100 p-4 rounded-xl outline-none focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="password"
                            class="text-[10px] font-black text-slate-400 uppercase mb-1 block">Password</label>
                        <input type="password" id="password" name="password" required placeholder="••••••••"
                            autocomplete="current-password"
                            class="w-full bg-slate-50 border-2 border-slate-100 p-4 rounded-xl outline-none focus:border-blue-500 transition-colors">
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 text-white font-black py-4 rounded-xl shadow-lg hover:bg-blue-700 transition-colors mt-4">
                        SIGN IN
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>