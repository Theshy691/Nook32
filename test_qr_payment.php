<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set(
    'error_log',
    '/Applications/XAMPP/xamppfiles/logs/php_error_log'
);

require_once __DIR__ . '/classes/TestDatabase.php';

try {
    $database = new TestDatabase();
    $connection = $database->getConnection();

    // Safety check: never continue unless connected to test.
    $result = $connection->query(
        "SELECT DATABASE() AS db_name"
    );
    $row = $result->fetch_assoc();

    if (($row['db_name'] ?? '') !== 'test') {
        throw new RuntimeException(
            'Safety check failed: not connected to test database.'
        );
    }

    // Check whether the test order and payment tables exist.
    $result = $connection->query("
        SELECT order_id, payment_method, payment_status
        FROM orders
        WHERE order_id = 1
        LIMIT 1
    ");

    $order = $result->fetch_assoc();

    echo "Connected to test database successfully.\n";

    if ($order) {
        echo "Test order found.\n";
        echo "Order ID: " . (int) $order['order_id'] . "\n";
        echo "Payment method: "
            . htmlspecialchars($order['payment_method']) . "\n";
        echo "Payment status: "
            . htmlspecialchars($order['payment_status']) . "\n";
    } else {
        echo "Order ID 1 was not found in the test database.\n";
        echo "No data was changed.\n";
    }

} catch (Throwable $e) {
    error_log(
        'QR test setup failed: '
        . get_class($e) . ': '
        . $e->getMessage()
    );

    http_response_code(500);
    echo "Test setup failed. Check the PHP error log.";
}