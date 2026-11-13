<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/OrderManager.php';

// Only allow payment verification through POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: order_history.php');
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Invalid security token. Please return to Order History and try again.');
}

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId <= 0) {
    header('Location: order_history.php?message=invalid');
    exit;
}

$transactionStarted = false;

try {
    // Use the same database connection for payment and inventory changes.
    $database = new Database();
    $connection = $database->getConnection();

    $connection->begin_transaction();
    $transactionStarted = true;

    // Lock the order so concurrent verification attempts cannot process it.
    $stmt = $connection->prepare(
        "SELECT payment_method, payment_status
         FROM orders
         WHERE order_id = ?
         FOR UPDATE"
    );

    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (
        !$order ||
        $order['payment_method'] !== 'qr' ||
        $order['payment_status'] !== 'pending'
    ) {
        $connection->rollback();
        $transactionStarted = false;

        header('Location: order_history.php?message=unchanged');
        exit;
    }

    // Mark the order paid inside the transaction.
    $updateStmt = $connection->prepare(
        "UPDATE orders
         SET payment_status = 'paid'
         WHERE order_id = ?
           AND payment_method = 'qr'
           AND payment_status = 'pending'"
    );

    $updateStmt->bind_param('i', $orderId);
    $updateStmt->execute();

    if ($updateStmt->affected_rows !== 1) {
        throw new RuntimeException(
            'Could not update QR payment status.'
        );
    }

    // Deduct ingredients using the same connection and transaction.
    $orderManager = new OrderManager($database);
    $orderManager->deductInventoryForOrder($orderId);

    $connection->commit();
    $transactionStarted = false;

    header('Location: order_history.php?message=verified');
    exit;

} catch (Throwable $e) {
    if ($transactionStarted && isset($connection)) {
        try {
            $connection->rollback();
        } catch (Throwable $rollbackError) {
            error_log(
                'Nook32 rollback error: ' .
                $rollbackError->getMessage()
            );
        }
    }

    error_log(
        'Nook32 QR verification error: ' . $e->getMessage()
    );

    // Show a different message for insufficient or missing ingredients.
    $message = str_contains(
        strtolower($e->getMessage()),
        'stock'
    ) || str_contains(
        strtolower($e->getMessage()),
        'recipe'
    ) || str_contains(
        strtolower($e->getMessage()),
        'ingredients'
    )
        ? 'stock_error'
        : 'error';

    header('Location: order_history.php?message=' . $message);
    exit;
}
?>
