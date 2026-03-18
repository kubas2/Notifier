<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

if (!isset($_POST['secret']) || $_POST['secret'] !== $dbConfig['secret']) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}



$conn = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['password'], $dbConfig['database']);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed", "details" => $conn->connect_error]);
    exit;
}

$conn->set_charset('utf8mb4');


if ($_POST['action'] == "getNotifications") {
    $sql = "SELECT n.id,n.title,n.description,n.created_at,n.send_to,u.name AS sender_name,u.surname AS sender_surname,u.email AS sender_email FROM notifications n JOIN users u ON n.sender_id = u.id ORDER BY n.created_at DESC;";
    $result = $conn->query($sql);

    $notifications = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }

    $json = json_encode($notifications, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    if ($json === false) {
        echo json_encode(["error" => "JSON encode error", "details" => json_last_error_msg(), "data_sample" => array_slice($notifications, 0, 5)]);
    } else {
        echo $json;
    }
} elseif ($_POST['action'] == "checkLogin") {
    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Bad request"]);
        exit;
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    // przygotowanie zapytania
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // zwykłe porównanie plaintext
        if ($password == $user['pass']) {
            echo json_encode(["success" => true, "user" => $user]);
        } else {
            echo json_encode(["success" => false, "error" => "Invalid password"]);
        }

    } else {
        echo json_encode(["success" => false, "error" => "User not found"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Bad request"]);
    exit;
}


$conn->close();
