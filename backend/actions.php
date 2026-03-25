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
    header('Content-Type: application/json; charset=utf-8');
    
    $email = $_POST['email'] ?? '';

    // 1. Pobieramy ID użytkownika na podstawie maila
    $userStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $userStmt->bind_param("s", $email);
    $userStmt->execute();
    $userData = $userStmt->get_result()->fetch_assoc();

    if (!$userData) {
        echo json_encode(["error" => "User not found"]); 
        exit;
    }

    $currentUserId = (int)$userData['id'];

    // 2. Pobieramy WSZYSTKIE powiadomienia (tak jak w Twoim działającym teście)
    $sql = "SELECT n.id, n.title, n.description, n.created_at, n.send_to, 
                   u.name AS sender_name, u.surname AS sender_surname, u.email AS sender_email 
            FROM notifications n 
            JOIN users u ON n.sender_id = u.id 
            ORDER BY n.created_at DESC";

    $result = $conn->query($sql);
    $filteredNotifications = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Dekodujemy kolumnę send_to z JSON na tablicę PHP
            $sendToIds = json_decode($row['send_to'], true);

            // Sprawdzamy, czy dekodowanie się udało i czy nasze ID jest w tej tablicy
            if (is_array($sendToIds)) {
                // in_array sprawdzi zarówno liczby 2, jak i stringi "2"
                if (in_array($currentUserId, $sendToIds)) {
                    $filteredNotifications[] = $row;
                }
            }
        }
    }

    // 3. Wysyłamy tylko te, które pasują
    echo json_encode($filteredNotifications, JSON_UNESCAPED_UNICODE);
    exit;

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
} elseif ($_POST['action'] == "signup") {
    if (!isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['name']) || !isset($_POST['surname'])) {
        http_response_code(400);
        echo json_encode(["error" => "Bad request"]);
        exit;
    }

    $email = $_POST['email'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $surname = $_POST['surname'];

    // sprawdzenie czy email już istnieje
    $sql = "SELECT id from users where email = ? limit 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        echo json_encode(["success" => false, "error" => "Email already exists"]);
    } else {
        $sql = "INSERT INTO users (email, pass, name, surname) VALUES (?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $email, $password, $name, $surname);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "user_id" => $stmt->insert_id]);
        } else {
            echo json_encode(["success" => false, "error" => "Database error", "details" => $stmt->error]);
        }
    }
} 

else {
    http_response_code(400);
    echo json_encode(["error" => "Bad request"]);
    exit;
}


$conn->close();
