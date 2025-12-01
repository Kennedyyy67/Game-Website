<?php
session_start();
require "../db.php";

if (isset($_SESSION['user_id'])) {
    header("Location: ../mainmenu.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Log In</title>
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

    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username=?");
    $stmt->bindParam(1, $username, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($result) === 1) {
        $row = $result[0];

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            header("Location: ../mainmenu.php");
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
