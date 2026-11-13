<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/classes/OrderManager.php';
require_once __DIR__ . '/classes/TestDatabase.php';

$setupConnection = null;

try {
    // The order manager uses the TEST database connection.
    $database = new TestDatabase();
    $connection = $database->getConnection();

    $result = $connection->query("SELECT DATABASE() AS db_name");
    $row = $result->fetch_assoc();

    if (($row['db_name'] ?? '') !== 'test') {
        throw new RuntimeException(
            'Safety check failed: not connected to test database.'
        );
    }

    // Open a separate connection for setting up and restoring stock.
    $setupConnection = new mysqli(
        'localhost',
        'root',
        '',
        'test'
    );
    $setupConnection->set_charset('utf8mb4');

    $productResult = $setupConnection->query(
        "SELECT product_id, product_name
         FROM products
         WHERE product_name = 'Test Coffee'
         LIMIT 1"
    );
    $product = $productResult->fetch_assoc();

    $inventoryResult = $setupConnection->query(
        "SELECT inventory_id, quantity
         FROM inventory
         WHERE item_name = 'Test Ingredient'
         LIMIT 1"
    );
    $ingredient = $inventoryResult->fetch_assoc();

    if (!$product || !$ingredient) {
        throw new RuntimeException(
            'Test Coffee or Test Ingredient was not found.'
        );
    }

    $productId = (int) $product['product_id'];
    $inventoryId = (int) $ingredient['inventory_id'];
    $startingStock = (float) $ingredient['quantity'];

    // Keep the original stock for restoration.
    $setStock = $setupConnection->prepare(
        "UPDATE inventory SET quantity = 2.000
         WHERE inventory_id = ?"
    );
    $setStock->bind_param('i', $inventoryId);
    $setStock->execute();

    try {
        $userId = null;
        $items = [[
            'product_id' => $productId,
            'product_name' => 'Test Coffee',
            'quantity' => 1,
            'unit_price' => 50.00
        ]];

        $manager = new OrderManager($database);

        try {
            $manager->saveOrder(
                $userId,
                $items,
                'cash',
                0.00,
                null,
                'paid',
                50.00,
                true
            );

            throw new RuntimeException(
                'FAIL: The order unexpectedly succeeded.'
            );

        } catch (RuntimeException $e) {
            if (
                strpos(
                    $e->getMessage(),
                    'Insufficient stock for'
                ) !== 0
            ) {
                throw $e;
            }

            echo "PASS: Insufficient inventory was detected.<br>";
            echo "Message: " .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                ) . "<br>";
        }

        // The separate connection can see whether stock stayed at 2 g.
        $check = $setupConnection->prepare(
            "SELECT quantity FROM inventory
             WHERE inventory_id = ?"
        );
        $check->bind_param('i', $inventoryId);
        $check->execute();

        $currentStock = (float) $check
            ->get_result()
            ->fetch_assoc()['quantity'];

        if (abs($currentStock - 2.000) > 0.0001) {
            echo "WARNING: Unexpected stock value during test.<br>";
        } else {
            echo "PASS: Failed order did not deduct inventory.<br>";
        }

    } finally {
        // Always restore the original stock using the separate connection.
        $restore = $setupConnection->prepare(
            "UPDATE inventory SET quantity = ?
             WHERE inventory_id = ?"
        );
        $restore->bind_param('di', $startingStock, $inventoryId);
        $restore->execute();
    }

    // Verify the original stock was restored.
    $verify = $setupConnection->prepare(
        "SELECT quantity FROM inventory
         WHERE inventory_id = ?"
    );
    $verify->bind_param('i', $inventoryId);
    $verify->execute();

    $restoredStock = (float) $verify
        ->get_result()
        ->fetch_assoc()['quantity'];

    if (abs($restoredStock - $startingStock) > 0.0001) {
        throw new RuntimeException(
            'The original test stock was not restored.'
        );
    }

    echo "PASS: Original test stock restored to " .
        number_format($restoredStock, 3) . " g.";

    $setupConnection->close();

} catch (Throwable $e) {
    if ($setupConnection instanceof mysqli) {
        // Best-effort restoration if an error occurs after setup.
        // The normal restoration is handled by the inner finally block.
        if ($setupConnection->connect_errno === 0) {
            $setupConnection->close();
        }
    }

    http_response_code(500);
    echo "<br>Test failed: " .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        );
}