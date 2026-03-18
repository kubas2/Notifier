<?php
header('Content-Type: application/json');
require_once "config.php";

$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['password'],
    $dbConfig['database']
);

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='admin'");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && $user['pass'] === $password) {
    echo json_encode(["success" => true, "id" => $user['id']]);
} else {
    echo json_encode(["success" => false]);
}