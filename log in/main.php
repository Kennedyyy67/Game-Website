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
  
    <link rel="stylesheet" href="../Important/login.css">
</head>
<body>





<div class="wrapper">
<div class="loginform">
    <div class="logo">
    <img src="../Important/gglogo.png" alt="GG Deals Logo" class="logoimg">
    <h1>Deals</h1>
    </div>


    <h2>Welcome!</h2>

    <form method="POST">
        <input type="text" class="input-box" name="username" placeholder="Username" required>
        <input type="password" class="input-box" name="password" placeholder="Password" required>
        <div class="buttons">
        <button type="submit" name="login" class="submitbtn">Log In</button>
        <button type="button" onclick="window.location='register.php'" class="regbtn">Sign Up</button>
        </div>
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
</div>

</body>
</html>
