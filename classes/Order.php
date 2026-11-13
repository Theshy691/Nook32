
<?php

class Order
{
    private int $orderId;
    private int $userId;
    private array $items;
    private string $paymentMethod;
    private float $totalAmount;
    private string $status;

    public function __construct(
        int $orderId,
        int $userId,
        string $paymentMethod = "Cash"
    ) {
        $this->orderId = $orderId;
        $this->userId = $userId;
        $this->items = [];
        $this->paymentMethod = $paymentMethod;
        $this->totalAmount = 0.00;
        $this->status = "Pending";
    }

    public function addItem(string $productName, float $price, int $quantity): void
    {
        if ($price < 0 || $quantity <= 0) {
            return;
        }

        $this->items[] = [
            "product_name" => $productName,
            "price" => $price,
            "quantity" => $quantity
        ];

        $this->calculateTotal();
    }

    public function calculateTotal(): float
    {
        $this->totalAmount = 0.00;

        foreach ($this->items as $item) {
            $this->totalAmount += $item["price"] * $item["quantity"];
        }

        return $this->totalAmount;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
