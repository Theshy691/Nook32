<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set(
    'error_log',
    '/Applications/XAMPP/xamppfiles/logs/php_error_log'
);

require_once __DIR__ . '/classes/OrderManager.php';
require_once __DIR__ . '/classes/TestDatabase.php';

try {
    // Connect to the TEST database only.
    $database = new TestDatabase();
    $conn = $database->getConnection();

    // Safety check: confirm the active database is "test".
    $result = $conn->query("SELECT DATABASE() AS db_name");
    $row = $result->fetch_assoc();

    if (($row['db_name'] ?? '') !== 'test') {
        throw new RuntimeException(
            'Safety check failed: not connected to the test database.'
        );
    }

    // Verify the test product and ingredient.
    $check = $conn->query("
        SELECT
            (
                SELECT COUNT(*)
                FROM products
                WHERE product_name = 'Test Coffee'
            ) AS product_count,
            (
                SELECT COUNT(*)
                FROM inventory
                WHERE item_name = 'Test Ingredient'
            ) AS ingredient_count,
            (
                SELECT quantity
                FROM inventory
                WHERE item_name = 'Test Ingredient'
                LIMIT 1
            ) AS stock
    ")->fetch_assoc();

    if (
        (int) $check['product_count'] !== 1 ||
        (int) $check['ingredient_count'] !== 1 ||
        (float) $check['stock'] !== 100.0
    ) {
        throw new RuntimeException(
            'Test setup is not in its expected starting state. '
            . 'Expected one test product and 100.000 units of test stock.'
        );
    }

    /*
     * OrderManager requires an instance of Database.
     * TestDatabase must extend Database and override getConnection()
     * to return the TEST connection. See the note below.
     */
    $manager = new OrderManager($database);

    // Create one paid cash order using fictional test data.
    $orderId = $manager->saveOrder(
        null,
        [[
            'product_id' => 1,
            'product_name' => 'Test Coffee',
            'quantity' => 1,
            'unit_price' => 50.00
        ]],
        'cash',
        0.00,
        null,
        'pending',
        50.00,
        true
    );

    // Check the remaining test stock.
    $stmt = $conn->prepare("
        SELECT quantity
        FROM inventory
        WHERE item_name = 'Test Ingredient'
        LIMIT 1
    ");
    $stmt->execute();
    $stockRow = $stmt->get_result()->fetch_assoc();
    $stock = (float) $stockRow['quantity'];
    $stmt->close();

    // Check the deduction log.
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS deduction_count
        FROM order_inventory_deductions
        WHERE order_id = ?
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $deductions = (int) $stmt->get_result()
        ->fetch_assoc()['deduction_count'];
    $stmt->close();

    if (abs($stock - 95.0) < 0.001 && $deductions === 1) {
        echo "PASS: Test order saved and inventory deducted once.\n";
        echo "Order ID: " . $orderId . "\n";
        echo "Remaining test stock: "
            . number_format($stock, 3) . " g\n";
        echo "Deduction records: " . $deductions . "\n";
    } else {
        echo "TEST RESULT DID NOT MATCH EXPECTATIONS.\n";
        echo "Order ID: " . $orderId . "\n";
        echo "Remaining test stock: "
            . number_format($stock, 3) . " g\n";
        echo "Deduction records: " . $deductions . "\n";
    }

} catch (Throwable $e) {
    error_log(
        'Inventory test failed: '
        . get_class($e) . ': '
        . $e->getMessage()
    );

    http_response_code(500);
    echo 'Test failed. Check the PHP error log for details.';
}