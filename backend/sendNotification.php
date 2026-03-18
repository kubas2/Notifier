<?php
header('Content-Type: application/json');
require_once "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['password'],
    $dbConfig['database']
);

$title = $data['title'];
$description = $data['description'];
$send_to = json_encode($data['send_to']);
$sender_id = 1;

$stmt = $conn->prepare("
    INSERT INTO notifications (title, description, send_to, sender_id, created_at)
    VALUES (?, ?, ?, ?, NOW())
");

$stmt->bind_param("sssi", $title, $description, $send_to, $sender_id);
$stmt->execute();

echo json_encode(["success" => true]);