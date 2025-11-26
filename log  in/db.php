<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "webgame";

$conn = new mysqli($host, $user, $pass, $db);

// Check DB connection
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>
