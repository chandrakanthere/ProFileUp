<?php
require_once 'config.php';

function executeSQLFile($filename) {
    global $pdo;
    
    // Read the SQL file
    $sql = file_get_contents($filename);
    if ($sql === false) {
        die("Error reading SQL file");
    }
    
    // Hash the password
    $password = '1234567890'; // Default password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Replace the placeholder hash with the actual hash
    $sql = str_replace(
        '$2y$10$8KzO8NxX5X5X5X5X5X5X5O8KzO8NxX5X5X5X5X5X5X5O8KzO8NxX5',
        $hashedPassword,
        $sql
    );
    
    try {
        // Execute the SQL commands
        $pdo->exec($sql);
        echo "SQL file executed successfully!\n";
        echo "Admin user created with:\n";
        echo "Email: chandrakant_236053@saitm.ac.in\n";
        echo "Password: " . $password . "\n";
    } catch(PDOException $e) {
        echo "Error executing SQL file: " . $e->getMessage();
    }
}

// Execute the SQL file
executeSQLFile('add_specific_admin.sql');
?> 