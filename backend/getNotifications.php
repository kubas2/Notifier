<?php
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
} else {
    if (isset($_POST['secret']) && $_POST['secret'] == $dbConfig['secret']) {
        // Secret is correct, continue processing
        $conn = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['password'], $dbConfig['database']);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT n.id,n.title,n.description,n.created_at,n.send_to,u.name AS sender_name,u.surname AS sender_surname,u.email AS sender_email FROM notifications n JOIN users u ON n.sender_id = u.id ORDER BY n.created_at DESC;";
        $result = $conn->query($sql);
        $notifications = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
        }
        echo json_encode($notifications);
        $conn->close();
       
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden"]);
        exit;
    }
}