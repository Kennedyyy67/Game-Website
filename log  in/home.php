<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
</head>
<body>

<div class="container">
    <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>

    <button onclick="window.location='logout.php'" class="btn red">Logout</button>
</div>

</body>
</html>
