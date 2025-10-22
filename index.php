<?php
session_start();
$task_schedule = $_SESSION['task_schedule'] ?? [];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Task Scheduler</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>Task Scheduler</h1>
        </header>

        <div class="main-grid">
            <!-- Form thêm task -->
            <section class="card form-card">
                <h2>Thêm task mới</h2>
                <form action="schedule.php" method="post">
                    <label>Tên task:</label>
                    <input type="text" name="task" required>

                    <label>Điểm stress (1-10):</label>
                    <input type="number" name="stress" min="1" max="10" required>

                    <label>Số giờ để hoàn thành:</label>
                    <input type="number" name="hours" min="1" required>

                    <label>Ngày tới hạn:</label>
                    <input type="date" name="deadline" required>
                    <label>Ghi chú:</label>
                    <textarea name="note" rows="3" placeholder="Nhập ghi chú..."></textarea>

                    <input type="submit" value="Thêm task">
                </form>
            </section>

            <!-- Danh sách task -->
            <section class="card task-list-card">
                <h2>Danh sách task</h2>
                <?php
                if (file_exists('tasks.json')) {
                    $tasks = json_decode(file_get_contents('tasks.json'), true);
                    if ($tasks) {

                        echo '<div class="task-list">';
                        foreach ($tasks as $index => $t) {
                            $done = true; // mặc định hoàn thành
                            foreach ($task_schedule as $dateTasks) {
                                foreach ($dateTasks as $taskPart) {
                                    if ($taskPart['id'] === $t['id'] && !$taskPart['done_today']) {
                                        $done = false; // còn task con chưa hoàn thành
                                        break 2;
                                    }
                                }
                            }

                            // foreach ($task_schedule as $dateTasks) {
                            //     foreach ($dateTasks as $taskPart) {
                            //         if ($taskPart['task_index'] === $index && !$taskPart['done_today']) {
                            //             $done = false; // còn task con chưa hoàn thành
                            //             break 2;
                            //         }
                            //     }
                            // }

                            // thêm class task-completed nếu hoàn thành
                            echo '<div class="task-card ' . ($done ? 'task-completed' : '') . '">';
                            echo "<h3>" . htmlspecialchars($t['task']) . "</h3>";
                            echo "<p>Stress: " . $t['stress'] . " | Giờ: " . $t['hours'] . " | Deadline: " . $t['deadline'] . "</p>";
                            if (!empty($t['note'])) {
                                echo "<p class='task-note'>" . htmlspecialchars($t['note']) . "</p>";
                            }
                            echo "<div class='task-actions'>
            <a href='edit.php?index=$index'>Sửa</a>
            <a href='delete.php?index=$index' onclick=\"return confirm('Bạn có chắc muốn xóa?')\">Xóa</a>
          </div>";
                            echo '</div>';
                        }
                    }
                }
                ?>
            </section>
        </div>

        <footer>
            <a class="view-schedule-btn" href="scheduler.php">Xem lịch sắp xếp</a>
        </footer>
    </div>
</body>

</html>