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
$descValue = $_POST['description'] ?? '';
$errorMsg = "";

/* ===== DELETE NOTIFICATION ===== */
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: dashboard.php?deleted=1");
    exit;
}

/* ===== MAKE ADMIN ===== */
if (isset($_POST['make_admin'])) {
    $id = $_POST['make_admin'];
    $stmt = $conn->prepare("UPDATE users SET role='admin' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: dashboard.php?updated=1");
    exit;
}

/* ===== SEND NOTIFICATION ===== */
if (isset($_POST['title'])) {
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
        $errorMsg = "❌ Wybierz użytkowników!";
    }
}

/* ===== DATA ===== */
$usersResult = $conn->query("SELECT * FROM users");

$notifications = $conn->query("
    SELECT n.*, u.name, u.surname
    FROM notifications n
    JOIN users u ON n.sender_id = u.id
    ORDER BY n.created_at DESC
");

$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalAdmins = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$totalNotifications = $conn->query("SELECT COUNT(*) as c FROM notifications")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="/style.css">
<title>Panel admina</title>
</head>
<body>

<h2>Panel admina</h2>
<a href="logout.php">Wyloguj</a>

<!-- ===== STATS ===== -->
<div class="stats">
    <div class="card">
        <h4>👤 Użytkownicy</h4>
        <p><?= $totalUsers ?></p>
    </div>

    <div class="card">
        <h4>🛡️ Admini</h4>
        <p><?= $totalAdmins ?></p>
    </div>

    <div class="card">
        <h4>🔔 Powiadomienia</h4>
        <p><?= $totalNotifications ?></p>
    </div>
</div>

<!-- ===== MESSAGES ===== -->
<?php if ($errorMsg): ?><p class="msg"><?= $errorMsg ?></p><?php endif; ?>
<?php if (isset($_GET['success'])): ?><p class="msg">✔ Wysłano powiadomienie!</p><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><p class="msg">✔ Usunięto powiadomienie!</p><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><p class="msg">✔ Zmieniono rolę!</p><?php endif; ?>

<hr>

<h3>Wyślij powiadomienie</h3>

<form method="POST">
<input name="title" placeholder="Tytuł" value="<?= htmlspecialchars($titleValue) ?>" required><br>

<textarea name="description" placeholder="Opis" required><?= htmlspecialchars($descValue) ?></textarea><br>

<input id="searchInput" placeholder="Szukaj użytkownika...">

<h4 onclick="toggleUsers()" style="cursor:pointer;">
<span id="arrow">▶</span> Użytkownicy
</h4>

<div id="counter">Wybrano: 0</div>

<button type="button" onclick="selectAll()">Zaznacz wszystkich</button>
<button type="button" onclick="clearAll()">Odznacz wszystkich</button>

<br><br>

<div id="usersBox" style="display:none;">
<?php while($u = $usersResult->fetch_assoc()): ?>
<label class="userItem">
<input type="checkbox" name="users[]" value="<?= $u['id'] ?>" class="userBox">
<span class="userName"><?= $u['name'] ?> <?= $u['surname'] ?></span>
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
<form method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć powiadomienie?')">
<input type="hidden" name="delete_id" value="<?= $n['id'] ?>">
<button type="submit">Usuń</button>
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
<form method="POST" onsubmit="return confirm('Czy na pewno chcesz nadać rolę admina?')">
<input type="hidden" name="make_admin" value="<?= $u['id'] ?>">
<button type="submit">Zrób adminem</button>
</form>
<?php else: ?>
<span style="color:green;">Admin</span>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</table>

<script>
function toggleUsers() {
    const box = document.getElementById("usersBox");
    const arrow = document.getElementById("arrow");

    if (box.style.display === "none") {
        box.style.display = "block";
        arrow.innerText = "▼";
    } else {
        box.style.display = "none";
        arrow.innerText = "▶";
    }
}

function selectAll() {
    document.querySelectorAll('.userBox').forEach(cb => cb.checked = true);
    updateCounter();
}

function clearAll() {
    document.querySelectorAll('.userBox').forEach(cb => cb.checked = false);
    updateCounter();
}

function updateCounter() {
    let count = document.querySelectorAll('.userBox:checked').length;
    document.getElementById("counter").innerText = "Wybrano: " + count;
}

document.querySelectorAll('.userBox').forEach(cb => {
    cb.addEventListener('change', updateCounter);
});

document.getElementById("searchInput").addEventListener("input", function() {
    let value = this.value.toLowerCase();

    document.querySelectorAll(".userItem").forEach(item => {
        let name = item.innerText.toLowerCase();
        item.style.display = name.includes(value) ? "block" : "none";
    });
});

setTimeout(() => {
    document.querySelectorAll(".msg").forEach(el => el.style.display = "none");
}, 3000);
</script>

</body>
</html>