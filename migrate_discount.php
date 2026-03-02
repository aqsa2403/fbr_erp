<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

try {
    echo "Checking Table Existence...\n";
    $tables = $db->query("SELECT name FROM sys.tables WHERE name LIKE '%Invoice%'")->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);

    if (in_array('DI_Invoice_Detail', $tables)) {
        echo "Adding Discount columns to [DI_Invoice_Detail]...\n";
        $db->exec("ALTER TABLE [DI_Invoice_Detail] ADD DiscountPercentage DECIMAL(18,2) DEFAULT 0");
        $db->exec("ALTER TABLE [DI_Invoice_Detail] ADD DiscountAmount DECIMAL(18,2) DEFAULT 0");
        echo "Columns added successfully.\n";
    } else {
        echo "Table DI_Invoice_Detail NOT FOUND in sys.tables\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
