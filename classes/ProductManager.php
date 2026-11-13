
<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Product.php';

class ProductManager
{
    private mysqli $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }

    public function getAvailableProducts(): array
    {
        $sql = "SELECT p.product_id, p.product_name, p.price,
                       c.category_name
                FROM products p
                LEFT JOIN categories c
                    ON p.category_id = c.category_id
                WHERE p.availability = 'available'
                ORDER BY c.category_id, p.product_name";

        $result = $this->connection->query($sql);
        $products = [];

        while ($row = $result->fetch_assoc()) {
            $products[] = new Product(
                (int) $row['product_id'],
                $row['product_name'],
                (float) $row['price'],
                $row['category_name'] ?? 'Uncategorized'
            );
        }

        return $products;
    }
}
