<?php
require 'db.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['firstName'] ?? '');
    $last_name = trim($_POST['lastName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $check_in = $_POST['checkIn'] ?? '';
    $check_out = $_POST['checkOut'] ?? '';
    $room_id = (int)($_POST['room_id'] ?? 0);

    // Валидация (серверная)
    if (mb_strlen($first_name) < 2) $errors[] = "Имя должно содержать минимум 2 символа";
    if (mb_strlen($last_name) < 2) $errors[] = "Фамилия должна содержать минимум 2 символа";
    
    // Валидация телефона (маска +7(999)999-99-99)
    if (!preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) $errors[] = "Неверный формат телефона";

    // Валидация email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Неверный формат email";

    // Валидация дат
    $today = date('Y-m-d');
    if ($check_in < $today) $errors[] = "Дата заезда не может быть в прошлом";
    if ($check_out <= $check_in) $errors[] = "Дата выезда должна быть позже даты заезда";

    if (empty($errors)) {
        // Защита от SQL-инъекций (Prepared Statement)
        $stmt = $pdo->prepare("INSERT INTO bookings (room_id, first_name, last_name, phone, email, check_in_date, check_out_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$room_id, $first_name, $last_name, $phone, $email, $check_in, $check_out]);
        
        // Перенаправление на страницу с сообщением об успехе
        header("Location: order.php?success=1");
        exit;
    }
}
?>
<!-- HTML форма + вывод ошибок -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php elseif (isset($_GET['success'])): ?>
    <div class="alert alert-success">Заявка успешно отправлена! Ожидайте подтверждения.</div>
<?php endif; ?>
