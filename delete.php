<?php
session_start();
require 'db.php'; // 🔹 kết nối MySQL

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Xóa task trong DB
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // 🔹 Xóa task con trong session scheduler
    if (isset($_SESSION['task_schedule'])) {
        foreach ($_SESSION['task_schedule'] as $date => $dateTasks) {
            foreach ($dateTasks as $k => $taskPart) {
                if ($taskPart['id'] == $id) {
                    unset($_SESSION['task_schedule'][$date][$k]);
                }
            }
            $_SESSION['task_schedule'][$date] = array_values($_SESSION['task_schedule'][$date]);
        }
    }

    unset($_SESSION['schedule']);
}

header('Location: index.php');
exit();
