DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;

-- Таблица пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guest') NOT NULL DEFAULT 'guest'
);

-- Таблица номеров
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
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);

-- Номера
INSERT INTO rooms (category, price_min, price_max, description) VALUES 
('standard', 3000, 3800, 'Уютный стандартный номер с двуспальной кроватью, Wi-Fi, TV.'),
('studio', 4600, 6200, 'Студия с кухонной зоной и панорамным окном.'),
('lux', 8500, 15000, 'Люкс с гостиной, джакузи и видом на море.');

-- Администратор: логин hotel123, пароль adminHotel
-- Хэш получен через password_hash('adminHotel', PASSWORD_DEFAULT)
INSERT INTO users (username, password_hash, role) VALUES 
('hotel123', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlWj1T0aB1cK2rVtYb2mFzO6yT8y8u', 'admin');
