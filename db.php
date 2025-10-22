<?php
$host = 'sql205.infinityfree.com';
$user = 'if0_40225360';
$pass = 'H31gP3Smfxh92eW';
$dbname = 'if0_40225360_task_scheduler';

// 🔹 Kết nối MySQL
$conn = new mysqli($host, $user, $pass);

// 🔹 Kiểm tra lỗi kết nối
if ($conn->connect_error) {
    die("❌ Kết nối thất bại: " . $conn->connect_error);
}

// 🔹 Tạo database nếu chưa có
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// 🔹 Chọn database
$conn->select_db($dbname);

// 🔹 Tạo bảng tasks nếu chưa tồn tại
$conn->query("
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task VARCHAR(255) NOT NULL,
    stress INT NOT NULL,
    hours INT NOT NULL,
    start_date DATE NOT NULL,
    deadline DATE NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 🔹 Thiết lập charset mặc định
$conn->set_charset("utf8mb4");
