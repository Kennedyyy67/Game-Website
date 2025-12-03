<?php
session_start();
require "../db.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Account</title>
    <link rel="stylesheet" href="../Important/reg.css">
</head>
<body>


<div class="wrapper">
<div class="container">
    <h2>Sign Up</h2>

    <form method="POST">
        <input type="text" class="input-box" name="username" placeholder="Username" required>
        <input type="email" class="input-box" name="email" placeholder="Email" required>
        <input type="password" class="input-box" name="password" placeholder="Password" required>

        <button type="submit" name="register" class="reg">Create</button>
    </form>


<?php
if (isset($_POST['register'])) {

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username exists
    $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
    $check->bindParam(1, $username, PDO::PARAM_STR);
    $check->execute();

    //If user already exists
    if ($check->rowCount() > 0) {
        echo "<p style='color:red;'>Username already taken.</p>";
    } else {
        $insert = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $insert->bindParam(1, $username, PDO::PARAM_STR);
        $insert->bindParam(2, $email, PDO::PARAM_STR);
        $insert->bindParam(3, $password, PDO::PARAM_STR);

        //Successfully created acc
        if ($insert->execute()) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            header("Location: ../mainmenu.php");
            exit;
        } else {
            echo "<p style='color:red;'>Failed to create account.</p>";
        }
    }
}
?>

</div>
</div>

</body>
</html>
