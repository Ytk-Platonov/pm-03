<?php
session_start();
require 'db.php';

// Защита страницы
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Обработка действий (Одобрить / Удалить)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $id = (int)$_POST['id'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
    }
    // Перенаправление, чтобы избежать повторной отправки формы
    header("Location: admin.php");
    exit;
}

// Вывод заявок из БД
$bookings = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC")->fetchAll();
?>
<!-- HTML разметка из admin.html, зацикленная через foreach -->
<?php foreach ($bookings as $booking): ?>
<div class="card">
    <div class="card-body">
        <h5>Фамилия: <?= htmlspecialchars($booking['last_name']) ?></h5>
        <h5>Имя: <?= htmlspecialchars($booking['first_name']) ?></h5>
        <h5>Телефон: <?= htmlspecialchars($booking['phone']) ?></h5>
        <ul class="list-group">
            <li class="list-group-item">Дата заезда: <?= $booking['check_in_date'] ?></li>
            <li class="list-group-item">Дата выезда: <?= $booking['check_out_date'] ?></li>
        </ul>
    </div>
    <div class="d-grid gap-2">
        <form method="post">
            <input type="hidden" name="id" value="<?= $booking['id'] ?>">
            <button name="action" value="approve" class="btn btn-success">Одобрить</button>
            <button name="action" value="delete" class="btn btn-danger">Удалить</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
