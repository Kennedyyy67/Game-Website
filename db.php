<?php
// Database configuration for Game Deals Website
$servername = "localhost:4306";
$username = "root";
$password = "";
$dbname = "game_deals";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create tables if they don't exist
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
        store_id VARCHAR(50),
        target_price DECIMAL(10,2),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (game_id) REFERENCES games(game_id)
    )"
];

foreach ($createTables as $query) {
    try {
        $pdo->exec($query);
    } catch(PDOException $e) {
        // Table might already exist, continue
    }
}


//   Migration script to add store_id column to user_wishlist table
 
// require_once 'db.php';

// try {
//     global $pdo;
//     $pdo->exec('ALTER TABLE user_wishlist ADD COLUMN store_id VARCHAR(50) DEFAULT NULL');
//     echo "Successfully added store_id column to user_wishlist table\n";
// } catch (PDOException $e) {
//     if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
//         echo "store_id column already exists\n";
//     } else {
//         echo "Error: " . $e->getMessage() . "\n";
//     }
// } 
?>
