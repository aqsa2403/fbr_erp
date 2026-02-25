<?php
require_once __DIR__ . '/../config/config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Dashboard' ?> - FBR DI ERP</title>
   
    <script src="https://cdn.tailwindcss.com"></script>
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-slate-50 font-sans text-slate-900">

    <?php require_once 'sidebar.php'; ?>

   
    <div class="ml-64 p-8">
       
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 id="main-title" class="text-3xl font-black text-slate-800 tracking-tight">
                    <?= $page_title ?? 'Dashboard' ?></h1>
                <p class="text-slate-500 font-medium">FBR Digital Invoicing & ERP Backend</p>
            </div>
            <div class="flex gap-3">
                <span class="bg-blue-100 text-blue-700 font-bold px-4 py-2 rounded-lg border border-blue-200">
                    <i class="fas fa-user-circle mr-2"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php"
                    class="bg-red-100 text-red-700 font-bold px-4 py-2 rounded-lg border border-red-200 hover:bg-red-200">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>