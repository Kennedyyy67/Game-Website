<?php
session_start();
require "db.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Main Menu</title>
</head>
<body>

<div class="container">
    <h2>Welcome to GG Deals</h2>

    <form method="POST">
        <input type="text" class="input-box" name="username" placeholder="Username" required>
        <input type="password" class="input-box" name="password" placeholder="Password" required>

        <button type="submit" name="login" class="btn green">Log In</button>
        <button type="button" onclick="window.location='register.php'" class="btn green">Sign Up</button>
    </form>

<?php
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            header("Location: home.php");
            exit;
        } else {
            echo "<p style='color:red;'>Incorrect password.</p>";
        }
    } else {
        echo "<p style='color:red;'>User not found.</p>";
    }
}
?>

</div>

</body>
</html>
