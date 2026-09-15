<?php
session_start();
require 'db.php';

// Доступ только после входа
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
    header("Location: admin.php");
    exit;
}

// Список заявок
$bookings = $pdo->query("
    SELECT b.*, r.category AS room_category 
    FROM bookings b
    LEFT JOIN rooms r ON r.id = b.room_id
    ORDER BY b.created_at DESC
")->fetchAll();

// Функция для класса статуса
function statusBadge($status) {
    return match($status) {
        'approved' => '<span class="badge bg-success">Одобрена</span>',
        'rejected' => '<span class="badge bg-secondary">Отклонена</span>',
        default    => '<span class="badge bg-warning text-dark">На рассмотрении</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">Панель управления отелем</span>
        <div>
            <span class="text-light me-3"><?= htmlspecialchars($_SESSION['admin_username']) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Выйти</a>
        </div>
    </div>
</nav>

<div class="container">
    <h3 class="mb-4">Заявки на бронирование</h3>

    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">Заявок пока нет.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($bookings as $b): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-1">
                                    <?= htmlspecialchars($b['last_name'] . ' ' . $b['first_name']) ?>
                                </h5>
                                <?= statusBadge($b['status']) ?>
                            </div>
                            <p class="mb-1"><strong>Номер:</strong> <?= htmlspecialchars(ucfirst($b['room_category'] ?? '—')) ?></p>
                            <p class="mb-1"><strong>Телефон:</strong> <?= htmlspecialchars($b['phone']) ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($b['email']) ?></p>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item px-0">Заезд: <?= htmlspecialchars($b['check_in_date']) ?></li>
                                <li class="list-group-item px-0">Выезд: <?= htmlspecialchars($b['check_out_date']) ?></li>
                                <li class="list-group-item px-0 text-muted small">
                                    Создана: <?= htmlspecialchars($b['created_at']) ?>
                                </li>
                            </ul>

                            <form method="post" class="d-flex gap-2 flex-wrap">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <?php if ($b['status'] !== 'approved'): ?>
                                    <button name="action" value="approve" class="btn btn-success btn-sm">Одобрить</button>
                                <?php endif; ?>
                                <?php if ($b['status'] !== 'rejected'): ?>
                                    <button name="action" value="reject" class="btn btn-secondary btn-sm">Отклонить</button>
                                <?php endif; ?>
                                <button name="action" value="delete" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Удалить заявку?');">Удалить</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
