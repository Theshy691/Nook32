<?php

require_once __DIR__ . '/Database.php';

class OrderManager
{
    private mysqli $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }

    public function saveOrder(
        ?int $userId,
        array $items,
        string $paymentMethod,
        float $discountAmount = 0.00,
        ?string $paymentReference = null,
        string $paymentStatus = 'pending',
        ?float $cashReceived = null,
        bool $confirmCashPayment = false
    ): int {
        if (empty($items)) {
            throw new InvalidArgumentException("The cart cannot be empty.");
        }

        if (!in_array($paymentMethod, ['cash', 'qr'], true)) {
            throw new InvalidArgumentException("Invalid payment method.");
        }

        if (!in_array($paymentStatus, ['pending', 'paid', 'failed'], true)) {
            throw new InvalidArgumentException("Invalid payment status.");
        }

        $subtotal = 0.00;

        foreach ($items as $item) {
            if (!isset(
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['unit_price']
            )) {
                throw new InvalidArgumentException(
                    "An order item is missing required information."
                );
            }

            $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
            $price = filter_var($item['unit_price'], FILTER_VALIDATE_FLOAT);
            $productId = filter_var($item['product_id'], FILTER_VALIDATE_INT);

            if (
                $productId === false || $productId <= 0 ||
                $quantity === false || $quantity <= 0 || $quantity > 100 ||
                $price === false || !is_finite((float) $price) || $price < 0
            ) {
                throw new InvalidArgumentException(
                    "Invalid product ID, quantity, or price."
                );
            }

            $subtotal += round((float) $price * $quantity, 2);
        }

        $subtotal = round($subtotal, 2);
        $discountAmount = round($discountAmount, 2);

        if (
            !is_finite($discountAmount) ||
            $discountAmount < 0 ||
            $discountAmount > $subtotal
        ) {
            throw new InvalidArgumentException("Invalid discount amount.");
        }

        $totalAmount = round($subtotal - $discountAmount, 2);
        $cashReceivedToSave = null;
        $changeAmount = null;

        if ($paymentMethod === 'cash') {
            if ($paymentReference !== null) {
                throw new InvalidArgumentException(
                    "Cash payments should not have a QR reference."
                );
            }

            if ($confirmCashPayment) {
                if (
                    $cashReceived === null ||
                    !is_finite($cashReceived) ||
                    $cashReceived < $totalAmount
                ) {
                    throw new InvalidArgumentException(
                        "Cash received must cover the order total."
                    );
                }

                $cashReceivedToSave = round($cashReceived, 2);
                $changeAmount = round(
                    $cashReceivedToSave - $totalAmount,
                    2
                );
                $paymentStatus = 'paid';
            } else {
                if ($paymentStatus === 'paid') {
                    throw new InvalidArgumentException(
                        "Cash payment must be confirmed before marking it paid."
                    );
                }

                $paymentStatus = 'pending';
            }
        } else {
            if ($confirmCashPayment || $cashReceived !== null) {
                throw new InvalidArgumentException(
                    "Cash details cannot be used for QR payments."
                );
            }

            if ($paymentStatus === 'paid') {
                throw new InvalidArgumentException(
                    "QR payments must be verified separately."
                );
            }

            $paymentReference = $paymentReference !== null
                ? trim($paymentReference)
                : null;
        }

        $this->connection->begin_transaction();

        try {
            $orderSql = "INSERT INTO orders
                (
                    user_id, subtotal, discount_amount, total_amount,
                    cash_received, change_amount, payment_method,
                    payment_reference, payment_status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $orderStmt = $this->connection->prepare($orderSql);
            $orderStmt->bind_param(
                "idddddsss",
                $userId,
                $subtotal,
                $discountAmount,
                $totalAmount,
                $cashReceivedToSave,
                $changeAmount,
                $paymentMethod,
                $paymentReference,
                $paymentStatus
            );
            $orderStmt->execute();

            $orderId = (int) $this->connection->insert_id;

            $itemSql = "INSERT INTO order_items
                (
                    order_id, product_id, product_name,
                    quantity, unit_price, item_total
                )
                VALUES (?, ?, ?, ?, ?, ?)";

            $itemStmt = $this->connection->prepare($itemSql);

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $productName = trim((string) $item['product_name']);
                $quantity = (int) $item['quantity'];
                $unitPrice = round((float) $item['unit_price'], 2);
                $itemTotal = round($unitPrice * $quantity, 2);

                if ($productName === '' || strlen($productName) > 150) {
                    throw new InvalidArgumentException("Invalid product name.");
                }

                $itemStmt->bind_param(
                    "iisidd",
                    $orderId,
                    $productId,
                    $productName,
                    $quantity,
                    $unitPrice,
                    $itemTotal
                );
                $itemStmt->execute();
            }

            // Deduct inventory only for a confirmed, paid cash order.
            if ($paymentMethod === 'cash' && $paymentStatus === 'paid') {
                $this->deductInventoryForOrder($orderId);
            }

            $this->connection->commit();

            return $orderId;

        } catch (Throwable $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    /**
     * Deduct all recipe ingredients for an order.
     * Must be called inside an active database transaction.
     */
    public function deductInventoryForOrder(int $orderId): void
    {
        // Lock the order and confirm it is paid.
        $orderStmt = $this->connection->prepare(
            "SELECT payment_method, payment_status
             FROM orders
             WHERE order_id = ?
             FOR UPDATE"
        );
        $orderStmt->bind_param("i", $orderId);
        $orderStmt->execute();
        $order = $orderStmt->get_result()->fetch_assoc();

        if (
            !$order ||
            $order['payment_method'] === null ||
            $order['payment_status'] !== 'paid'
        ) {
            throw new RuntimeException("Order is not paid.");
        }

        // Prevent a second deduction for this order.
        $checkStmt = $this->connection->prepare(
            "SELECT order_id
             FROM order_inventory_deductions
             WHERE order_id = ?"
        );
        $checkStmt->bind_param("i", $orderId);
        $checkStmt->execute();

        if ($checkStmt->get_result()->fetch_assoc()) {
            throw new RuntimeException(
                "Inventory has already been deducted for this order."
            );
        }

        // Every ordered product must have a recipe with ingredients.
        $recipeStmt = $this->connection->prepare(
            "SELECT
                oi.product_name,
                pr.recipe_id,
                COUNT(ri.recipe_ingredient_id) AS ingredient_count
             FROM order_items oi
             LEFT JOIN product_recipes pr
                ON pr.product_id = oi.product_id
             LEFT JOIN recipe_ingredients ri
                ON ri.recipe_id = pr.recipe_id
             WHERE oi.order_id = ?
             GROUP BY oi.order_item_id, oi.product_name, pr.recipe_id"
        );
        $recipeStmt->bind_param("i", $orderId);
        $recipeStmt->execute();
        $recipeResult = $recipeStmt->get_result();

        if ($recipeResult->num_rows === 0) {
            throw new RuntimeException("The order has no items.");
        }

        while ($recipe = $recipeResult->fetch_assoc()) {
            if (
                $recipe['recipe_id'] === null ||
                (int) $recipe['ingredient_count'] === 0
            ) {
                throw new RuntimeException(
                    "Missing recipe or ingredients for product: " .
                    $recipe['product_name']
                );
            }
        }

        // Calculate total ingredient requirements for the whole order.
        $requirementsStmt = $this->connection->prepare(
            "SELECT
                ri.inventory_id,
                i.item_name,
                i.unit,
                SUM(ri.quantity_required * oi.quantity) AS required_quantity
             FROM order_items oi
             INNER JOIN product_recipes pr
                ON pr.product_id = oi.product_id
             INNER JOIN recipe_ingredients ri
                ON ri.recipe_id = pr.recipe_id
             INNER JOIN inventory i
                ON i.inventory_id = ri.inventory_id
             WHERE oi.order_id = ?
             GROUP BY ri.inventory_id, i.item_name, i.unit
             ORDER BY ri.inventory_id"
        );
        $requirementsStmt->bind_param("i", $orderId);
        $requirementsStmt->execute();
        $requirements = $requirementsStmt->get_result();

        $updateStmt = $this->connection->prepare(
            "UPDATE inventory
             SET quantity = quantity - ?
             WHERE inventory_id = ?
               AND quantity >= ?"
        );

        while ($ingredient = $requirements->fetch_assoc()) {
            $inventoryId = (int) $ingredient['inventory_id'];
            $required = (float) $ingredient['required_quantity'];

            if (!is_finite($required) || $required <= 0) {
                throw new RuntimeException(
                    "Invalid recipe quantity for " . $ingredient['item_name']
                );
            }

            // Lock this stock row before checking and updating it.
            $stockStmt = $this->connection->prepare(
                "SELECT quantity
                 FROM inventory
                 WHERE inventory_id = ?
                 FOR UPDATE"
            );
            $stockStmt->bind_param("i", $inventoryId);
            $stockStmt->execute();
            $stock = $stockStmt->get_result()->fetch_assoc();

            if (!$stock || (float) $stock['quantity'] < $required) {
                throw new RuntimeException(
                    "Insufficient stock for " . $ingredient['item_name'] .
                    ". Required: " . $required . " " . $ingredient['unit']
                );
            }

            $updateStmt->bind_param(
                "did",
                $required,
                $inventoryId,
                $required
            );
            $updateStmt->execute();

            if ($updateStmt->affected_rows !== 1) {
                throw new RuntimeException(
                    "Could not update stock for " . $ingredient['item_name']
                );
            }
        }

        // Record the deduction in the same transaction as the stock changes.
        $deductionStmt = $this->connection->prepare(
            "INSERT INTO order_inventory_deductions (order_id)
             VALUES (?)"
        );
        $deductionStmt->bind_param("i", $orderId);
        $deductionStmt->execute();
    }
}
