<?php
try {
    require __DIR__ . "/config/test_database.php";

    $result = $conn->query("SELECT DATABASE() AS db_name");
    $row = $result->fetch_assoc();

    if ($row["db_name"] === "test") {
        echo "Success! Connected to the TEST database only.";
    } else {
        echo "Warning: Unexpected database connection.";
    }

    $conn->close();

} catch (Throwable $e) {
    echo "Connection test failed. Please check your test database settings.";
}
?>