<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Require login without redirecting, because checkout uses JSON.
if (!isset($_SESSION['user_id'])) {
    respond([
        'success' => false,
        'message' => 'Your session has expired. Please log in again.'
    ], 401);
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/OrderManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'success' => false,
        'message' => 'Invalid request method.'
    ], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    respond([
        'success' => false,
        'message' => 'Invalid checkout data.'
    ], 400);
}

$items = $data['items'] ?? [];
$paymentMethod = $data['payment_method'] ?? '';
$paymentReference = $data['payment_reference'] ?? null;
$cashReceivedInput = $data['cash_received'] ?? null;

if (!is_array($items) || empty($items)) {
    respond([
        'success' => false,
        'message' => 'Your cart is empty.'
    ], 400);
}

if (!in_array($paymentMethod, ['cash', 'qr'], true)) {
    respond([
        'success' => false,
        'message' => 'Please select a valid payment method.'
    ], 400);
}

if ($paymentMethod === 'qr') {
    if (
        !is_string($paymentReference) ||
        trim($paymentReference) === ''
    ) {
        respond([
            'success' => false,
            'message' => 'Enter the QR payment reference.'
        ], 400);
    }

    $paymentReference = trim($paymentReference);

    if (strlen($paymentReference) > 150) {
        respond([
            'success' => false,
            'message' => 'The payment reference is too long.'
        ], 400);
    }
} else {
    $paymentReference = null;
}

$cashReceived = null;

if ($paymentMethod === 'cash') {
    if (
        (!is_string($cashReceivedInput) &&
         !is_int($cashReceivedInput) &&
         !is_float($cashReceivedInput)) ||
        !is_numeric($cashReceivedInput) ||
        !is_finite((float) $cashReceivedInput) ||
        (float) $cashReceivedInput < 0
    ) {
        respond([
            'success' => false,
            'message' => 'Enter a valid amount of cash received.'
        ], 400);
    }

    $cashReceived = round((float) $cashReceivedInput, 2);
} elseif ($cashReceivedInput !== null) {
    respond([
        'success' => false,
        'message' => 'Cash received must not be provided for QR payments.'
    ], 400);
}

try {
    $productStmt = $conn->prepare(
        "SELECT product_id, product_name, price
         FROM products
         WHERE product_id = ? AND availability = 'available'"
    );

    $validatedItems = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            respond([
                'success' => false,
                'message' => 'Invalid cart item.'
            ], 400);
        }

        $productId = filter_var(
            $item['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $item['quantity'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $productId === false || $productId <= 0 ||
            $quantity === false || $quantity <= 0 ||
            $quantity > 100
        ) {
            respond([
                'success' => false,
                'message' => 'A product or quantity is invalid.'
            ], 400);
        }

        $productStmt->bind_param('i', $productId);
        $productStmt->execute();

        $product = $productStmt->get_result()->fetch_assoc();

        if (!$product) {
            respond([
                'success' => false,
                'message' => 'A product is unavailable. Please refresh the menu.'
            ], 400);
        }

        $validatedItems[] = [
            'product_id' => (int) $product['product_id'],
            'product_name' => $product['product_name'],
            'quantity' => $quantity,
            'unit_price' => (float) $product['price']
        ];
    }

    // Calculate the total using database prices.
    $subtotal = 0.00;

    foreach ($validatedItems as $item) {
        $subtotal += round(
            $item['unit_price'] * $item['quantity'],
            2
        );
    }

    $subtotal = round($subtotal, 2);

    if (
        $paymentMethod === 'cash' &&
        $cashReceived < $subtotal
    ) {
        respond([
            'success' => false,
            'message' => 'Cash received is less than the order total.',
            'total' => number_format($subtotal, 2, '.', ''),
            'change' => '0.00'
        ], 400);
    }

    $database = new Database();
    $orderManager = new OrderManager($database);

    // Save the logged-in cashier's user ID with the order.
    $userId = (int) $_SESSION['user_id'];

    if ($paymentMethod === 'cash') {
        $orderId = $orderManager->saveOrder(
            $userId,
            $validatedItems,
            'cash',
            0.00,
            null,
            'pending',
            $cashReceived,
            true
        );

        $change = round($cashReceived - $subtotal, 2);

        respond([
            'success' => true,
            'message' => 'Cash payment recorded successfully.',
            'order_id' => $orderId,
            'payment_status' => 'paid',
            'total' => number_format($subtotal, 2, '.', ''),
            'cash_received' => number_format($cashReceived, 2, '.', ''),
            'change' => number_format($change, 2, '.', '')
        ]);
    }

    // QR orders remain pending until payment is verified.
    $orderId = $orderManager->saveOrder(
        $userId,
        $validatedItems,
        'qr',
        0.00,
        $paymentReference,
        'pending'
    );

    respond([
        'success' => true,
        'message' => 'QR order saved. Verify the payment before marking it paid.',
        'order_id' => $orderId,
        'payment_status' => 'pending',
        'total' => number_format($subtotal, 2, '.', '')
    ]);

} catch (InvalidArgumentException | RuntimeException $e) {
    respond([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
} catch (Throwable $e) {
    error_log('Nook32 checkout error: ' . $e->getMessage());

    respond([
        'success' => false,
        'message' => 'An unexpected server error occurred while processing the order. Please try again.'
    ], 500);
}
