<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/classes/OrderManager.php';
require_once __DIR__ . '/classes/TestDatabase.php';

try {
    $database = new TestDatabase();
    $connection = $database->getConnection();

    // Safety check: use only the test database.
    $result = $connection->query("SELECT DATABASE() AS db_name");
    $row = $result->fetch_assoc();

    if (($row['db_name'] ?? '') !== 'test') {
        throw new RuntimeException(
            'Safety check failed: not connected to test database.'
        );
    }

    // Find the already-paid fictional QR order.
    $reference = 'TEST-QR-001';

    $stmt = $connection->prepare(
        "SELECT order_id, payment_status
         FROM orders
         WHERE payment_reference = ?"
    );

    $stmt->bind_param('s', $reference);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order || $order['payment_status'] !== 'paid') {
        throw new RuntimeException(
            'The test QR order was not found or is not paid.'
        );
    }

    $orderId = (int) $order['order_id'];

    // Attempt the deduction again.
    $manager = new OrderManager($database);

    try {
        $manager->deductInventoryForOrder($orderId);

        echo "FAIL: A duplicate deduction was not blocked.";
    } catch (Throwable $e) {
        if (
            $e->getMessage() ===
            'Inventory has already been deducted for this order.'
        ) {
            echo "PASS: The duplicate deduction was blocked.<br>";
            echo "Message: " .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                );
        } else {
            throw $e;
        }
    }

} catch (Throwable $e) {
    http_response_code(500);

    echo "Test setup failed: " .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        );
}
