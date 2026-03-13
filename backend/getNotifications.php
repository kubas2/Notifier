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

        $sql = "SELECT * FROM notifications ORDER BY created_at DESC";
        $result = $conn->query($sql);
        $notifications = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
        }
        $conn->close();
       
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden"]);
        exit;
    }
}