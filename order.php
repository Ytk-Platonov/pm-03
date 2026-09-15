<?php
require 'db.php';
$errors = [];

$room_id = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);

// Получаем информацию о номере
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    die("Номер не найден");
}

// Занятые периоды (только одобренные брони) — для передачи в JS
$stmt = $pdo->prepare("
    SELECT check_in_date, check_out_date 
    FROM bookings 
    WHERE room_id = ? AND status = 'approved'
");
$stmt->execute([$room_id]);
$busyPeriods = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['firstName'] ?? '');
    $last_name  = trim($_POST['lastName'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $check_in   = $_POST['checkIn'] ?? '';
    $check_out  = $_POST['checkOut'] ?? '';

    // Валидация
    if (mb_strlen($first_name) < 2) $errors[] = "Имя должно содержать минимум 2 символа";
    if (mb_strlen($last_name) < 2)  $errors[] = "Фамилия должна содержать минимум 2 символа";
    if (!preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) $errors[] = "Неверный формат телефона";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Неверный формат email";

    $today = date('Y-m-d');
    if ($check_in < $today) $errors[] = "Дата заезда не может быть в прошлом";
    if ($check_out <= $check_in) $errors[] = "Дата выезда должна быть позже даты заезда";

    // Проверка пересечения с одобренными бронями
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE room_id = ? 
              AND status = 'approved'
              AND NOT (check_out_date <= ? OR check_in_date >= ?)
        ");
        $stmt->execute([$room_id, $check_in, $check_out]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Выбранный период уже забронирован. Выберите другие даты.";
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO bookings 
            (room_id, first_name, last_name, phone, email, check_in_date, check_out_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$room_id, $first_name, $last_name, $phone, $email, $check_in, $check_out]);
        header("Location: order.php?room_id=$room_id&success=1");
        exit;
    }
}

// Преобразуем занятые периоды в JSON для JS
$busyJson = json_encode($busyPeriods, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Бронирование номера</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width: 700px; margin-top: 40px;">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="mb-3">Бронирование: <?= htmlspecialchars(ucfirst($room['category'])) ?></h3>
            <p class="text-muted">Цена: <?= $room['price_min'] ?> - <?= $room['price_max'] ?> ₽/сутки</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif (isset($_GET['success'])): ?>
                <div class="alert alert-success">Заявка успешно отправлена! Ожидайте подтверждения.</div>
            <?php endif; ?>

            <form method="post" id="orderForm">
                <input type="hidden" name="room_id" value="<?= $room_id ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Имя</label>
                        <input type="text" name="firstName" class="form-control" required
                               value="<?= htmlspecialchars($_POST['firstName'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Фамилия</label>
                        <input type="text" name="lastName" class="form-control" required
                               value="<?= htmlspecialchars($_POST['lastName'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Телефон</label>
                    <input type="text" name="phone" id="phone" class="form-control"
                           placeholder="+7(___)___-__-__" required
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Дата заезда</label>
                        <input type="date" name="checkIn" id="checkIn" class="form-control" required
                               min="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($_POST['checkIn'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Дата выезда</label>
                        <input type="date" name="checkOut" id="checkOut" class="form-control" required
                               value="<?= htmlspecialchars($_POST['checkOut'] ?? '') ?>">
                    </div>
                </div>

                <div id="dateWarning" class="alert alert-warning d-none"></div>

                <button type="submit" class="btn btn-success w-100">Отправить заявку</button>
            </form>

            <a href="index.php" class="btn btn-link mt-3">← Вернуться к номерам</a>
        </div>
    </div>
</div>

<script>
    // Занятые периоды (одобренные брони)
    const busyPeriods = <?= $busyJson ?>;

    // Маска телефона +7(999)999-99-99
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '');
        if (v.startsWith('8')) v = '7' + v.slice(1);
        if (!v.startsWith('7')) v = '7' + v;
        v = v.slice(0, 11);
        let res = '+7';
        if (v.length > 1) res += '(' + v.slice(1, 4);
        if (v.length >= 4) res += ')' + v.slice(4, 7);
        if (v.length >= 7) res += '-' + v.slice(7, 9);
        if (v.length >= 9) res += '-' + v.slice(9, 11);
        e.target.value = res;
    });

    // Проверка пересечения с занятыми периодами
    function checkBusy() {
        const inVal = document.getElementById('checkIn').value;
        const outVal = document.getElementById('checkOut').value;
        const warning = document.getElementById('dateWarning');
        warning.classList.add('d-none');

        if (!inVal || !outVal) return;
        if (outVal <= inVal) {
            warning.textContent = "Дата выезда должна быть позже даты заезда";
            warning.classList.remove('d-none');
            return;
        }

        const ci = new Date(inVal);
        const co = new Date(outVal);
        for (const p of busyPeriods) {
            const bi = new Date(p.check_in_date);
            const bo = new Date(p.check_out_date);
            // Пересечение: не (co <= bi или ci >= bo)
            if (!(co <= bi || ci >= bo)) {
                warning.textContent = "Выбранный период уже забронирован (" 
                    + p.check_in_date + " — " + p.check_out_date + "). Выберите другие даты.";
                warning.classList.remove('d-none');
                return;
            }
        }
    }

    document.getElementById('checkIn').addEventListener('change', function() {
        // Дата выезда — минимум следующий день после заезда
        const ci = this.value;
        if (ci) {
            const next = new Date(ci);
            next.setDate(next.getDate() + 1);
            document.getElementById('checkOut').min = next.toISOString().split('T')[0];
        }
        checkBusy();
    });
    document.getElementById('checkOut').addEventListener('change', checkBusy);
</script>
</body>
</html>
