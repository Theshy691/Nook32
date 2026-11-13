<?php

require_once __DIR__ . '/Database.php';

class InventoryManager
{
    private mysqli $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }

    // Retrieve all inventory items
    public function getAllItems(): array
    {
        $sql = "SELECT
                    inventory_id,
                    item_name,
                    quantity,
                    unit,
                    low_stock_limit,
                    created_at,
                    updated_at
                FROM inventory
                ORDER BY item_name ASC";

        $result = $this->connection->query($sql);

        $items = [];

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        return $items;
    }

    // Retrieve one inventory item by its ID
    public function getItemById(int $inventoryId): ?array
    {
        $sql = "SELECT
                    inventory_id,
                    item_name,
                    quantity,
                    unit,
                    low_stock_limit,
                    created_at,
                    updated_at
                FROM inventory
                WHERE inventory_id = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('i', $inventoryId);
        $stmt->execute();

        $result = $stmt->get_result();
        $item = $result->fetch_assoc();

        return $item ?: null;
    }

    // Count all inventory items
    public function getTotalItems(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM inventory";

        $result = $this->connection->query($sql);
        $row = $result->fetch_assoc();

        return (int) $row['total'];
    }

    // Count items that have reached their low-stock limit
    public function getLowStockCount(): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM inventory
                WHERE quantity <= low_stock_limit";

        $result = $this->connection->query($sql);
        $row = $result->fetch_assoc();

        return (int) $row['total'];
    }
}
