<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = $_POST['task'];
    $stress = intval($_POST['stress']);
    $deadline = $_POST['deadline'];
    $hours = intval($_POST['hours']);
    $note = $_POST['note'];

    $new_task = [
        'id' => uniqid(),  // thêm ID duy nhất
        'task' => $task,
        'stress' => $stress,
        'hours' => $hours,
        'deadline' => $deadline,
        'note' => $note
    ];

    // Đọc file hiện có
    $tasks = [];
    if (file_exists('tasks.json')) {
        $tasks = json_decode(file_get_contents('tasks.json'), true);
    }

    // Thêm task mới
    $tasks[] = $new_task;

    // Ghi lại file JSON
    file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // 🔹 Xóa session cũ để scheduler tự rebuild
    unset($_SESSION['task_schedule']);
    unset($_SESSION['schedule']);

    // 🔹 Chuyển đến scheduler.php (xem lịch)
    header('Location: scheduler.php');
    exit();
} else {
    // Nếu không phải POST thì quay về trang chính
    header('Location: index.php');
    exit();
}
