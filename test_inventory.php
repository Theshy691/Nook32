<?php

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/InventoryManager.php';

try {
    $database = new Database();
    $inventory = new InventoryManager($database);

    echo "<h2>Inventory Class Test</h2>";

    echo "Total inventory items: ";
    echo $inventory->getTotalItems();

    echo "<br>Low-stock items: ";
    echo $inventory->getLowStockCount();

    echo "<h3>Inventory Items</h3>";

    $items = $inventory->getAllItems();

    if (empty($items)) {
        echo "No inventory items yet. The class is working!";
    } else {
        foreach ($items as $item) {
            echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8');
            echo " — ";
            echo htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8');
            echo " ";
            echo htmlspecialchars($item['unit'], ENT_QUOTES, 'UTF-8');
            echo "<br>";
        }
    }

} catch (Throwable $e) {
    echo "Test failed: ";
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}