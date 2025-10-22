<?php
session_start(); // 🔹 cần start session

if (isset($_GET['index'])) {
    $index = intval($_GET['index']);
    $tasks = json_decode(file_get_contents('tasks.json'), true);

    if (isset($tasks[$index])) {
        $taskId = $tasks[$index]['id'] ?? null;

        // Xóa task cha khỏi JSON
        array_splice($tasks, $index, 1);
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 🔹 Xóa task con trong session scheduler
        if ($taskId && isset($_SESSION['task_schedule'])) {
            foreach ($_SESSION['task_schedule'] as $date => $dateTasks) {
                foreach ($dateTasks as $k => $taskPart) {
                    if ($taskPart['id'] === $taskId) {
                        unset($_SESSION['task_schedule'][$date][$k]);
                    }
                }
                // reset key liên tục
                $_SESSION['task_schedule'][$date] = array_values($_SESSION['task_schedule'][$date]);
            }
        }

        // 🔹 Xóa schedule tổng hợp để rebuild
        unset($_SESSION['schedule']);
    }
}

header('Location: index.php');
exit();
