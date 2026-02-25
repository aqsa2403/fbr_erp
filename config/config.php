<?php
session_start();
define('BASE_URL', 'http://localhost/dashboard/erp/'); // Adjust based on your server setup

require_once __DIR__ . '/Database.php';

function redirect($url)
{
    header("Location: " . BASE_URL . $url);
    exit();
}

function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}
?>