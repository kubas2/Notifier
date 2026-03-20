<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

require_once "config.php";

$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['password'],
    $dbConfig['database']
);

$conn->set_charset('utf8mb4');

$titleValue = $_POST['title'] ?? '';
$descValue  = $_POST['description'] ?? '';
$search     = $_POST['search'] ?? '';

if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    $stmt = $conn->prepare("DELETE FROM notifications WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: dashboard.php?deleted=1");
    exit;
}

if (isset($_POST['make_admin'])) {
    $id = $_POST['make_admin'];

    $stmt = $conn->prepare("UPDATE users SET role='admin' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: dashboard.php?updated=1");
    exit;
}

if (isset($_POST['title']) && !isset($_POST['searchBtn'])) {
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

        header("Location: dashboard.php?success=1");
        exit;
    } else {
        header("Location: dashboard.php?error=1");
        exit;
    }
}

if ($search) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR surname LIKE ?");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $usersResult = $stmt->get_result();
} else {
    $usersResult = $conn->query("SELECT * FROM users");
}

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

<?php if (isset($_GET['success'])): ?><p class="msg">✔ Wysłano powiadomienie!</p><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><p class="msg">✔ Usunięto powiadomienie!</p><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><p class="msg">✔ Zmieniono rolę!</p><?php endif; ?>
<?php if (isset($_GET['error'])): ?><p class="msg">❌ Wybierz użytkowników!</p><?php endif; ?>

<hr>

<h3>Wyślij powiadomienie</h3>

<form method="POST">

<input name="title" placeholder="Tytuł" value="<?= htmlspecialchars($titleValue) ?>"><br>

<textarea name="description" placeholder="Opis"><?= htmlspecialchars($descValue) ?></textarea><br>

<input name="search" placeholder="Szukaj użytkownika..." value="<?= htmlspecialchars($search) ?>">
<button type="submit" name="searchBtn">Szukaj</button>

<h4 onclick="toggleUsers()" style="cursor:pointer;">
▶ Użytkownicy (kliknij aby rozwinąć)
</h4>

<button type="button" onclick="selectAll()">Zaznacz wszystkich</button>
<button type="button" onclick="clearAll()">Odznacz wszystkich</button>

<br><br>

<div id="usersBox" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; padding:10px;">

<?php while($u = $usersResult->fetch_assoc()): ?>
<label>
<input type="checkbox" name="users[]" value="<?= $u['id'] ?>" class="userBox">
<?= $u['name'] ?> <?= $u['surname'] ?>
</label><br>
<?php endwhile; ?>

</div>

<br>
<button type="submit">Wyślij</button>

</form>

<hr>

<h3>Powiadomienia</h3>

<table border="1">
<tr>
<th>ID</th>
<th>Tytuł</th>
<th>Nadawca</th>
<th>Data</th>
<th>Akcja</th>
</tr>

<?php while($n = $notifications->fetch_assoc()): ?>
<tr>
<td><?= $n['id'] ?></td>
<td><?= $n['title'] ?></td>
<td><?= $n['name'] ?> <?= $n['surname'] ?></td>
<td><?= $n['created_at'] ?></td>
<td>
<form method="POST">
<input type="hidden" name="delete_id" value="<?= $n['id'] ?>">
<button>Usuń</button>
</form>
</td>
</tr>
<?php endwhile; ?>

</table>

<hr>

<h3>Użytkownicy</h3>

<table border="1">
<tr>
<th>ID</th>
<th>Imię</th>
<th>Nazwisko</th>
<th>Rola</th>
<th>Akcja</th>
</tr>

<?php
$allUsers = $conn->query("SELECT * FROM users");
while($u = $allUsers->fetch_assoc()):
?>
<tr>
<td><?= $u['id'] ?></td>
<td><?= $u['name'] ?></td>
<td><?= $u['surname'] ?></td>
<td><?= $u['role'] ?></td>
<td>
<?php if ($u['role'] !== 'admin'): ?>
<form method="POST">
<input type="hidden" name="make_admin" value="<?= $u['id'] ?>">
<button>Zrób adminem</button>
</form>
<?php else: ?>
<span style="color:green;">Admin</span>
<?php endif; ?>
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

function toggleUsers() {
    const box = document.getElementById("usersBox");
    box.style.display = (box.style.display === "none") ? "block" : "none";
}

if (window.location.search) {
    window.history.replaceState(null, null, window.location.pathname);
}

setTimeout(() => {
    document.querySelectorAll(".msg").forEach(el => el.style.display = "none");
}, 3000);
</script>

</body>
</html>