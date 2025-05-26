<?php
$host = 'localhost';
$user = 'root';
$password = '';

try {
    $conn = new mysqli($host, $user, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS cowrywise_demo";
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully\n";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }

    // Select the database
    $conn->select_db('cowrywise_demo');

    // Read and execute the SQL schema
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
   
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "All tables created successfully!\n";
    } else {
        throw new Exception("Error creating tables: " . $conn->error);
    }

    $conn->close();
    echo "Database setup completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($conn)) {
        $conn->close();
    }
    exit(1);
}
?> 