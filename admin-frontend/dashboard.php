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

    header("Location: dashboard.php?deleted=1");
    exit;
}


// ================= SEND =================
if (isset($_POST['title'])) {
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $users       = $_POST['users'] ?? [];

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


// ================= MAKE ADMIN =================
if (isset($_POST['make_admin'])) {
    $id = $_POST['make_admin'];

    $stmt = $conn->prepare("UPDATE users SET role='admin' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: dashboard.php?updated=1");
    exit;
}


// ================= SEARCH =================
$search = $_GET['search'] ?? '';

if ($search) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR surname LIKE ?");
    $like = "%$search%";

    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();

    $usersResult = $stmt->get_result();
} else {
    $usersResult = $conn->query("SELECT * FROM users");
}


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

<!-- 🔥 KOMUNIKATY -->
<?php if (isset($_GET['success'])): ?>
    <p style="color:green;">✔ Wysłano powiadomienie!</p>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <p style="color:red;">✔ Usunięto powiadomienie!</p>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <p style="color:blue;">✔ Zmieniono rolę!</p>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <p style="color:red;">❌ Wybierz użytkowników!</p>
<?php endif; ?>

<hr>


<!-- 🔍 SEARCH -->
<form method="GET">
    <input name="search" placeholder="Szukaj użytkownika..." value="<?= $search ?>">
    <button>Szukaj</button>
</form>

<hr>


<!-- 📤 SEND -->
<h3>Wyślij powiadomienie</h3>

<form method="POST">

    <input name="title" placeholder="Tytuł"><br>
    <textarea name="description" placeholder="Opis"></textarea><br>

    <h4 onclick="toggleUsers()" style="cursor:pointer;">
        ▶ Użytkownicy (kliknij aby rozwinąć)
    </h4>

    <button type="button" onclick="selectAll()">Zaznacz wszystkich</button>
    <button type="button" onclick="clearAll()">Odznacz wszystkich</button>

    <br><br>

    <div id="usersBox" style="display:none; border:1px solid #ccc; max-height:200px; overflow-y:auto; padding:10px;">

        <?php
        $usersResult->data_seek(0);
        while ($u = $usersResult->fetch_assoc()):
        ?>
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


<!-- 📋 NOTIFICATIONS -->
<h3>Powiadomienia</h3>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Tytuł</th>
        <th>Nadawca</th>
        <th>Data</th>
        <th>Akcja</th>
    </tr>

    <?php while ($n = $notifications->fetch_assoc()): ?>
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


<!-- 👥 USERS -->
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
    while ($u = $allUsers->fetch_assoc()):
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


<!-- 🔥 JS -->
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
</script>

</body>
</html>