<?php
require 'db.php';

$category = $_GET['category'] ?? '';
$allowed_categories = ['standard', 'studio', 'lux'];
if (!in_array($category, $allowed_categories)) {
    $category = '';
}

if ($category) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE category = ?");
    $stmt->execute([$category]);
} else {
    $stmt = $pdo->query("SELECT * FROM rooms");
}
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Бронирование номеров</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">Отель «Гранд»</span>
        <a href="login.php" class="btn btn-outline-light btn-sm">Вход для администратора</a>
    </div>
</nav>

<div class="container">
    <form method="GET" action="index.php" class="mb-4 d-flex gap-2">
        <select name="category" class="form-select" style="max-width: 250px;">
            <option value="">Все категории</option>
            <option value="standard" <?= $category === 'standard' ? 'selected' : '' ?>>Стандартный</option>
            <option value="studio"   <?= $category === 'studio'   ? 'selected' : '' ?>>Студия</option>
            <option value="lux"      <?= $category === 'lux'      ? 'selected' : '' ?>>Люкс</option>
        </select>
        <button type="submit" class="btn btn-primary">Применить</button>
        <a href="index.php" class="btn btn-outline-secondary">Сбросить</a>
    </form>

    <div class="row g-4">
        <?php foreach ($rooms as $room): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <img src="img/<?= htmlspecialchars($room['category']) ?>.png"
                         class="card-img-top" alt="<?= htmlspecialchars($room['category']) ?>"
                         onerror="this.style.display='none'">
                    <div class="card-body d-flex flex-column">
                        <h4>Категория: <?= htmlspecialchars(ucfirst($room['category'])) ?></h4>
                        <h6 class="text-muted"><?= $room['price_min'] ?> - <?= $room['price_max'] ?> ₽/сутки</h6>
                        <p class="small"><?= htmlspecialchars($room['description']) ?></p>
                        <a href="order.php?room_id=<?= $room['id'] ?>" class="btn btn-success mt-auto">Забронировать</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
