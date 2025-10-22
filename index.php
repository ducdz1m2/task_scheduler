<?php
session_start();
require 'db.php';
setlocale(LC_TIME, 'vi_VN.UTF-8');

// Kiểm tra login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách task của user
$stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY deadline ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lấy trạng thái hoàn thành từng ngày
$task_done_map = [];
$stmt = $conn->prepare("
    SELECT tp.task_id, tp.date, tp.done_today
    FROM task_progress tp
    JOIN tasks t ON tp.task_id = t.id
    WHERE t.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (!isset($task_done_map[$row['task_id']])) $task_done_map[$row['task_id']] = [];
    $task_done_map[$row['task_id']][$row['date']] = (bool)$row['done_today'];
}
$stmt->close();
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
            <div class="header-actions">
                <a href="logout.php" class="btn-logout">Đăng xuất</a>
                <a href="scheduler.php" class="btn-view-schedule">Xem lịch sắp xếp</a>
            </div>
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

                    <label>Ngày bắt đầu:</label>
                    <input type="date" name="start_date" required>

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
                <?php if ($tasks): ?>
                    <div class="task-list">
                        <?php foreach ($tasks as $t):
                            if (empty($t['task']) || !isset($t['stress']) || !isset($t['hours']) || empty($t['deadline'])) continue;

                            // Kiểm tra tất cả ngày xem task đã hoàn thành hết chưa
                            // Tạo danh sách tất cả ngày của task
                            $start_ts = strtotime($t['start_date']);
                            $deadline_ts = strtotime($t['deadline']);
                            $all_days = [];
                            for ($ts = $start_ts; $ts <= $deadline_ts; $ts += 86400) {
                                $all_days[date('Y-m-d', $ts)] = false;
                            }

                            // Gán trạng thái đã hoàn thành nếu có trong task_progress
                            if (isset($task_done_map[$t['id']])) {
                                foreach ($task_done_map[$t['id']] as $date => $done_today) {
                                    if (isset($all_days[$date])) {
                                        $all_days[$date] = $done_today;
                                    }
                                }
                            }

                            // Kiểm tra tất cả ngày đã hoàn thành chưa
                            $done = !in_array(false, $all_days, true);


                            $deadline = strftime("%A, %d/%m/%Y", strtotime($t['deadline']));
                            $start_date = strftime("%A, %d/%m/%Y", strtotime($t['start_date']));
                        ?>
                            <div class="task-card <?= $done ? 'task-completed' : '' ?>">
                                <h3><?= htmlspecialchars($t['task']) ?></h3>
                                <p>
                                    Stress: <?= htmlspecialchars($t['stress']) ?><br>
                                    Giờ: <?= htmlspecialchars($t['hours']) ?><br>
                                    Ngày bắt đầu: <?= htmlspecialchars($start_date) ?><br>
                                    Deadline: <?= htmlspecialchars($deadline) ?>
                                </p>
                                <?php if (!empty($t['note'])): ?>
                                    <p class="task-note"><?= htmlspecialchars($t['note']) ?></p>
                                <?php endif; ?>
                                <div class="task-actions">
                                    <?php if (!$done): ?>
                                        <a href="complete.php?id=<?= $t['id'] ?>" onclick="return confirm('Đánh dấu hoàn thành?')">Hoàn thành</a>
                                    <?php endif; ?>
                                    <a href="edit.php?id=<?= $t['id'] ?>">Sửa</a>
                                    <a href="delete.php?id=<?= $t['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Chưa có task nào.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</body>

</html>