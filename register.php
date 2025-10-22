<?php
session_start();
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    if ($password !== $password2) {
        $message = "Mật khẩu không trùng khớp!";
    } else {
        // Kiểm tra username đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Tên người dùng đã tồn tại!";
        } else {
            // Chưa tồn tại -> hash password và insert
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $insertStmt->bind_param("ss", $username, $hash);

            if ($insertStmt->execute()) {
                $message = "Đăng ký thành công! Bạn có thể đăng nhập.";
            } else {
                $message = "Có lỗi xảy ra, thử lại sau.";
            }
            $insertStmt->close();
        }

        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="auth-container">
        <h1>Đăng ký</h1>
        <?php if ($message) echo "<p>$message</p>"; ?>
        <form method="post">
            <label>Username:</label>
            <input type="text" name="username" required>
            <label>Password:</label>
            <input type="password" name="password" required>
            <label>Nhập lại Password:</label>
            <input type="password" name="password2" required>
            <input type="submit" value="Đăng ký">
        </form>
        <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
    </div>
</body>

</html>