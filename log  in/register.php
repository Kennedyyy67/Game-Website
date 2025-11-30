<?php
session_start();
require "config.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Account</title>
</head>
<body>

<div class="container">
    <h2>Create Account</h2>

    <form method="POST">
        <input type="text" class="input-box" name="username" placeholder="Username" required>
        <input type="password" class="input-box" name="password" placeholder="Password" required>

        <button type="submit" name="register" class="btn green">Create</button>
    </form>

<?php
if (isset($_POST['register'])) {

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username exists
    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    //If user already exists
    if ($result->num_rows > 0) {
        echo "<p style='color:red;'>Username already taken.</p>";
    } else {
        $insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $insert->bind_param("ss", $username, $password);
    
    //Successfully created acc
        if ($insert->execute()) {
        echo "<script>
        alert('Account created successfully!');
        window.location='index.php';
        </script>";
        exit;
    }

      else {
            echo "<p style='color:red;'>Failed to create account.</p>";
        }
    }
}
?>

</div>

</body>
</html>
