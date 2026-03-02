<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

echo "--- DI_Invoice_Detail ---\n";
$stmt = $db->query("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'DI_Invoice_Detail'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Column: {$row['COLUMN_NAME']} ({$row['DATA_TYPE']})\n";
}

echo "\n--- DI_Invoice_Master ---\n";
$stmt = $db->query("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'DI_Invoice_Master'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Column: {$row['COLUMN_NAME']} ({$row['DATA_TYPE']})\n";
}
