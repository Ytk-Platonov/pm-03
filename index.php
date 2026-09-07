<?php
require 'db.php';

// Получаем категорию для фильтра (белый список)
$category = $_GET['category'] ?? '';
$allowed_categories = ['standard', 'studio', 'lux'];
if (!in_array($category, $allowed_categories)) {
    $category = ''; // Если категория недопустима, показываем все
}

// Запрос с серверной фильтрацией
if ($category) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE category = ?");
    $stmt->execute([$category]);
} else {
    $stmt = $pdo->query("SELECT * FROM rooms");
}
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<!-- ... Подключение CSS и верстка из index.html ... -->
<form method="GET" action="index.php">
    <select name="category">
        <option value="">Все категории</option>
        <option value="standard" <?= $category === 'standard' ? 'selected' : '' ?>>Стандартный</option>
        <option value="studio" <?= $category === 'studio' ? 'selected' : '' ?>>Студия</option>
        <option value="lux" <?= $category === 'lux' ? 'selected' : '' ?>>Люкс</option>
    </select>
    <button type="submit">Применить</button>
    <a href="index.php" class="btn">Сбросить фильтр</a>
</form>

<!-- Динамический вывод карточек -->
<div class="d-flex justify-content-around flex-wrap align-items-center">
    <?php foreach ($rooms as $room): ?>
        <div class="card">
            <!-- Картинка подставляется по категории из img/ -->
            <img src="img/<?= $room['category'] ?>.png" class="card-img-top" alt="...">
            <div class="card-body">
                <h3>Категория: <?= htmlspecialchars(ucfirst($room['category'])) ?></h3>
                <h5>Цена: <?= $room['price_min'] ?> - <?= $room['price_max'] ?> ₽/сутки</h5>
                <!-- Характеристики можно вывести из description -->
                <a href="order.php?room_id=<?= $room['id'] ?>" class="btn btn-success">Забронировать</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
