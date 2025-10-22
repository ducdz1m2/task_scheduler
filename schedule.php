<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = $_POST['task'];
    $stress = (int)$_POST['stress'];
    $hours = (int)$_POST['hours'];
    $start_date = $_POST['start_date'];
    $deadline = $_POST['deadline'];
    $note = $_POST['note'] ?? '';

    $user_id = $_SESSION['user_id']; // từ session khi user đăng nhập
    $stmt = $conn->prepare("
    INSERT INTO tasks (task, stress, hours, start_date, deadline, note, user_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
    $stmt->bind_param("siisssi", $task, $stress, $hours, $start_date, $deadline, $note, $user_id);

    if ($stmt->execute()) {
        $old_done = [];
        if (isset($_SESSION['task_schedule'])) {
            foreach ($_SESSION['task_schedule'] as $date => $tasks) {
                foreach ($tasks as $t) {
                    if (!empty($t['id']) && !empty($t['done_today'])) {
                        $old_done[$t['id']] = true;
                    }
                }
            }
        }

        // 🔹 Xóa session cũ
        unset($_SESSION['task_schedule']);
        unset($_SESSION['schedule']);

        // 🔹 Lưu trạng thái "done" vào session riêng để scheduler.php có thể dùng lại
        $_SESSION['old_done'] = $old_done;
        header('Location: index.php');
        exit();
    } else {
        echo "❌ Lỗi khi thêm task: " . $stmt->error;
    }

    $stmt->close();
}
