<?php
/**
 * Database Setup Script for Game Deals Website
 * Creates the game_deals database and all required tables
 */

$servername = "localhost:4306";
$username = "root";
$password = "";

try {
    // Connect to MySQL server (without specifying database)
    $pdo = new PDO("mysql:host=$servername", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS game_deals");
    echo "Database 'game_deals' created successfully.<br>";

    // Switch to the new database
    $pdo->exec("USE game_deals");

    // Create tables
    $createTables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id VARCHAR(100) UNIQUE NOT NULL,
            title VARCHAR(255) NOT NULL,
            normal_price DECIMAL(10,2),
            sale_price DECIMAL(10,2),
            savings DECIMAL(5,2),
            steam_rating INT,
            thumb VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS deals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id VARCHAR(100) NOT NULL,
            store_id VARCHAR(50),
            deal_id VARCHAR(100) UNIQUE NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            retail_price DECIMAL(10,2),
            savings DECIMAL(5,2),
            is_on_sale BOOLEAN DEFAULT FALSE,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (game_id) REFERENCES games(game_id)
        )",

        "CREATE TABLE IF NOT EXISTS user_wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            game_id VARCHAR(100) NOT NULL,
            target_price DECIMAL(10,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (game_id) REFERENCES games(game_id)
        )"
    ];

    foreach ($createTables as $query) {
        $pdo->exec($query);
        echo "Table created successfully.<br>";
    }

    echo "<br><strong>Database setup completed successfully!</strong>";
    echo "<br>The 'game_deals' database is now ready.";

} catch(PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
