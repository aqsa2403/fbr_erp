<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

echo "Checking Table Schema...\n";
$stmt = $db->query("SELECT SCHEMA_NAME(schema_id) AS SchemaName, name FROM sys.tables WHERE name = 'DI_Invoice_Detail'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Schema: {$row['SchemaName']}, Table: {$row['name']}\n";
}
