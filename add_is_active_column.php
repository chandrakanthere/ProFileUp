<?php
require_once 'config.php';

try {
    $sql = file_get_contents('add_is_active_column.sql');
    $pdo->exec($sql);
    echo "is_active column added to users table successfully!";
} catch(PDOException $e) {
    echo "Error adding column: " . $e->getMessage();
}
?> 