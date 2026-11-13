<?php

require_once __DIR__ . '/../config/database.php';

class Database
{
    private mysqli $connection;

    public function __construct()
    {
        global $conn;
        $this->connection = $conn;
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }
}
