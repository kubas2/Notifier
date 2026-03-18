<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

require_once "../backend/config.php";

$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['password'],
    $dbConfig['database']
);

$conn->set_charset('utf8mb4');

// ================= DELETE =================
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    $stmt = $conn->prepare("DELETE FROM notifications WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// ================= SEND =================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['title'])) {

    $title = $_POST['title'];
    $description = $_POST['description'];
    $users = $_POST['users'] ?? [];

    if (!empty($users)) {
        $send_to = json_encode($users);

        $stmt = $conn->prepare("
            INSERT INTO notifications (title, description, send_to, sender_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param("sssi", $title, $description, $send_to, $_SESSION['admin']);
        $stmt->execute();

        echo "<p style='color:green;'>Wysłano powiadomienie!</p>";
    }
}

// ================= USERS =================
$usersResult = $conn->query("SELECT id, name, surname FROM users");

// ================= NOTIFICATIONS =================
$notifications = $conn->query("
SELECT n.*, u.name, u.surname 
FROM notifications n 
JOIN users u ON n.sender_id = u.id
ORDER BY n.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Panel admina</title>
</head>
<body>

<h2>Panel admina</h2>

<a href="logout.php">Wyloguj</a>

<hr>

<h3>Wyślij powiadomienie</h3>

<form method="POST">

<input name="title" placeholder="Tytuł"><br>
<textarea name="description" placeholder="Opis"></textarea><br>

<button type="button" onclick="selectAll()">Zaznacz wszystkich</button>
<button type="button" onclick="clearAll()">Odznacz wszystkich</button>

<br><br>

<?php while($u = $usersResult->fetch_assoc()): ?>
    <label>
        <input type="checkbox" name="users[]" value="<?= $u['id'] ?>" class="userBox">
        <?= $u['name'] ?> <?= $u['surname'] ?>
    </label><br>
<?php endwhile; ?>

<br>
<button type="submit">Wyślij</button>

</form>

<hr>

<h3>Wszystkie powiadomienia</h3>

<table border="1" cellpadding="5">
<tr>
    <th>ID</th>
    <th>Tytuł</th>
    <th>Opis</th>
    <th>Nadawca</th>
    <th>Data</th>
    <th>Akcja</th>
</tr>

<?php while($n = $notifications->fetch_assoc()): ?>
<tr>
    <td><?= $n['id'] ?></td>
    <td><?= $n['title'] ?></td>
    <td><?= $n['description'] ?></td>
    <td><?= $n['name'] ?> <?= $n['surname'] ?></td>
    <td><?= $n['created_at'] ?></td>
    <td>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="delete_id" value="<?= $n['id'] ?>">
            <button onclick="return confirm('Usunąć?')">Usuń</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>

</table>

<script>
function selectAll() {
    document.querySelectorAll('.userBox').forEach(cb => cb.checked = true);
}

function clearAll() {
    document.querySelectorAll('.userBox').forEach(cb => cb.checked = false);
}
</script>

</body>
</html>