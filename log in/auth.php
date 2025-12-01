<?php
session_start();

// REGISTER
if (isset($_POST['register'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Check if username already exists
    $data = file($file, FILE_IGNORE_NEW_LINES);
    foreach ($data as $line) {
        list($u,) = explode(":", $line);
        if ($u == $user) {
            $_SESSION['msg'] = "Username already taken.";
            header("Location: register.php");
            exit;
        }
    }

    // Save new account
    file_put_contents($file, "$user:$pass\n", FILE_APPEND);

    $_SESSION['msg'] = "Account created! You can now log in.";
    header("Location: main.php");
    exit;
}

// LOGIN
if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $data = file($file, FILE_IGNORE_NEW_LINES);
    foreach ($data as $line) {
        list($u, $p) = explode(":", $line);
        if ($u == $user && $p == $pass) {
            $_SESSION['user'] = $user;
            header("Location: home.php");
            exit;
        }
    }

    $_SESSION['msg'] = "Incorrect username or password.";
    header("Location: main.php");
    exit;
}

?>
