<?php
session_start();
require_once "../backend/config.php";

$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['password'],
    $dbConfig['database']
);

$conn->set_charset('utf8mb4');

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $user['pass'] === $password) {
        $_SESSION['admin'] = $user['id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Błędne dane logowania";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="style.css">
<title>Login</title>
</head>
<body class="login-page">

<div class="login-container">

<h2>Login admina</h2>

<form method="POST">
    <input name="email" placeholder="Email">
    <input name="password" type="password" placeholder="Hasło">
    <button>Zaloguj</button>
</form>

<p class="error"><?= $error ?></p>

</div>

</body> 
</html>