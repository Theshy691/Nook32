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

$transactionStarted = false;

try {
    // Connect to the TEST database only.
    $database = new TestDatabase();
    $connection = $database->getConnection();

    // Safety check: stop unless the database is "test".
    $result = $connection->query("SELECT DATABASE() AS db_name");
    $row = $result->fetch_assoc();

    if (($row['db_name'] ?? '') !== 'test') {
        throw new RuntimeException(
            'Safety check failed: not connected to test database.'
        );
    }

    // Locate our specific fictional QR order.
    $connection->begin_transaction();
    $transactionStarted = true;

    $stmt = $connection->prepare(
        "SELECT order_id, payment_method, payment_status
         FROM orders
         WHERE payment_reference = ?
         FOR UPDATE"
    );

    $reference = 'TEST-QR-001';
    $stmt->bind_param('s', $reference);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        throw new RuntimeException('Test QR order was not found.');
    }

    $orderId = (int) $order['order_id'];

    if ($order['payment_method'] !== 'qr') {
        throw new RuntimeException('Test order is not a QR order.');
    }

    if ($order['payment_status'] !== 'pending') {
        throw new RuntimeException(
            'Test order is not pending. Current status: '
            . $order['payment_status']
        );
    }

    // Mark the fictional test order as paid.
    $update = $connection->prepare(
        "UPDATE orders
         SET payment_status = 'paid'
         WHERE order_id = ?
           AND payment_method = 'qr'
           AND payment_status = 'pending'"
    );

    $update->bind_param('i', $orderId);
    $update->execute();

    if ($update->affected_rows !== 1) {
        throw new RuntimeException(
            'Could not mark the test QR order as paid.'
        );
    }

    // Deduct inventory within the same transaction.
    $manager = new OrderManager($database);
    $manager->deductInventoryForOrder($orderId);

    $connection->commit();
    $transactionStarted = false;

    echo "PASS: Test QR payment verified successfully.\n";
    echo "Test order ID: " . $orderId . "\n";
    echo "Payment status: paid\n";
    echo "Inventory deduction completed.\n";

} catch (Throwable $e) {
    if ($transactionStarted && isset($connection)) {
        try {
            $connection->rollback();
        } catch (Throwable $rollbackError) {
            error_log(
                'QR test rollback error: '
                . $rollbackError->getMessage()
            );
        }
    }

    error_log(
        'QR verification test failed: '
        . get_class($e) . ': '
        . $e->getMessage()
    );

    http_response_code(500);
    echo "Test failed. Check the PHP error log. "
        . "Any active transaction was rolled back.";
}