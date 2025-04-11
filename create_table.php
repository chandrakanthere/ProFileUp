<?php
require_once 'config.php';

try {
    $sql = file_get_contents('create_templates_table.sql');
    $pdo->exec($sql);
    echo "Templates table created successfully!";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?> 