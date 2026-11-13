
<?php

class Product
{
    private int $productId;
    private string $productName;
    private float $price;
    private string $categoryName;

    public function __construct(
        int $productId,
        string $productName,
        float $price,
        string $categoryName
    ) {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->price = $price;
        $this->categoryName = $categoryName;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function setPrice(float $price): void
    {
        if ($price >= 0) {
            $this->price = $price;
        }
    }
}
