<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/test_database.php';

class TestDatabase extends Database
{
    private mysqli $testConnection;

    public function __construct()
    {
        global $conn;

        $this->testConnection = $conn;
    }

    public function getConnection(): mysqli
    {
        return $this->testConnection;
    }
}