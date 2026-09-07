-- Таблица пользователей (для администратора и гостей)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guest') NOT NULL DEFAULT 'guest'
);

-- Таблица номеров (категории и цены)
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('standard', 'studio', 'lux') NOT NULL,
    price_min INT NOT NULL,
    price_max INT NOT NULL,
    description TEXT
);

-- Таблица бронирований
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Заполняем номера согласно ТЗ
INSERT INTO rooms (category, price_min, price_max) VALUES 
('standard', 3000, 3800),
('studio', 4600, 6200),
('lux', 8500, 15000);

-- Создаем администратора (пароль: admin123 - в реальном проекте хэшируется через password_hash)
INSERT INTO users (username, password_hash, role) VALUES 
('admin', '$2y$10$...', 'admin');
