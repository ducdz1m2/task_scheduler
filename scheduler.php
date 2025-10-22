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

// Config scheduler
$stress_limit_per_day = 10;
$hours_limit_per_day = 8;
$max_hours_per_task_per_day = 2;
$max_hours_near_deadline = 5;

// Hàm màu task theo stress
function getTaskClass($stress)
{
    if ($stress <= 3) return 'task-low';
    if ($stress <= 6) return 'task-medium';
    return 'task-high';
}

// Hoàn thành task hôm nay
if (isset($_GET['complete_date']) && isset($_GET['complete_index'])) {
    $date = $_GET['complete_date'];
    $task_index = intval($_GET['complete_index']);

    if (isset($_SESSION['task_schedule'][$date][$task_index])) {
        $task_id = $_SESSION['task_schedule'][$date][$task_index]['id'];

        $stmt = $conn->prepare("
            INSERT INTO task_progress (task_id, date, done_today)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE done_today = 1
        ");
        $stmt->bind_param("ss", $task_id, $date);
        $stmt->execute();
        $stmt->close();

        // Cập nhật session hiển thị
        $_SESSION['task_schedule'][$date][$task_index]['done_today'] = true;
    }
    header('Location: scheduler.php');
    exit();
}

// Lấy task của user
$stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY deadline ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$tasks) $task_schedule = [];

// Đảm bảo mỗi task có id
foreach ($tasks as &$t) {
    if (!isset($t['id'])) $t['id'] = uniqid();
}
unset($t);

// Lấy trạng thái task đã hoàn thành của user
$stmt = $conn->prepare("
    SELECT tp.task_id, tp.date
    FROM task_progress tp
    JOIN tasks t ON tp.task_id = t.id
    WHERE tp.done_today = 1 AND t.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$done_map = [];
while ($row = $result->fetch_assoc()) {
    $done_map[$row['task_id']][$row['date']] = true;
}
$stmt->close();

// Build schedule
$schedule = [];
$task_schedule = [];
$today_ts = strtotime(date('Y-m-d'));
foreach ($tasks as $taskIndex => $task) {
    if (empty($task['deadline']) || !isset($task['stress']) || !isset($task['hours'])) continue;

    $start_ts = isset($task['start_date']) ? strtotime($task['start_date']) : $today_ts;
    $deadline_ts = strtotime($task['deadline']);

    $days_count = ($deadline_ts - $start_ts) / 86400 + 1; // số ngày khả dụng
    if ($days_count <= 0) continue;

    $hours_per_day = ceil($task['hours'] / $days_count);  // chia đều giờ
    $stress_per_day = ceil($task['stress'] / $days_count); // chia đều stress

    for ($i = 0; $i < $days_count; $i++) {
        $cur_ts = strtotime("+$i day", $start_ts);
        $date = date('Y-m-d', $cur_ts);

        if (!isset($schedule[$date])) $schedule[$date] = ['stress' => 0, 'hours' => 0];

        // giới hạn theo stress/hours tối đa
        $avail_hours = min($hours_limit_per_day - $schedule[$date]['hours'], $hours_per_day);
        $avail_stress = min($stress_limit_per_day - $schedule[$date]['stress'], $stress_per_day);

        if ($avail_hours <= 0 || $avail_stress <= 0) continue;

        $schedule[$date]['hours'] += $avail_hours;
        $schedule[$date]['stress'] += $avail_stress;

        $task_part = $task;
        $task_part['hours'] = $avail_hours;
        $task_part['stress'] = $avail_stress;
        $task_part['done_today'] = !empty($done_map[$task['id']][$date]);
        $task_part['task_index'] = $taskIndex;
        $task_part['id'] = $task['id'];

        $task_part['done_today'] = !empty($done_map[$task['id']][$date]);
        $task_schedule[$date][] = $task_part;
    }
}


// Lưu schedule vào session để dùng khi đánh dấu hoàn thành
$_SESSION['task_schedule'] = $task_schedule;
$_SESSION['schedule'] = $schedule;


?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Lịch học</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>
    <div class="container">
        <h1>Lịch học sắp xếp thông minh</h1>
        <div class="footer"><a class="btn" href="index.php">Quay lại trang chính</a></div>
        <h2>Biểu đồ tổng Stress & Giờ</h2>
        <?php if ($task_schedule): ?>
            <canvas id="scheduleChart" width="100%" height="50"></canvas>
        <?php else: ?>
            <p>Chưa có task nào để hiển thị biểu đồ.</p>
        <?php endif; ?>

        <div class="schedule-grid">
            <?php if ($task_schedule): ?>
                <?php foreach ($task_schedule as $date => $tasks): ?>
                    <div class="day-card">
                        <div class="day-header"><?= strftime("%A, %d/%m/%Y", strtotime($date)) ?></div>
                        <div class="tasks">
                            <?php foreach ($tasks as $tIndex => $t):
                                $done = $t['done_today'];
                            ?>
                                <div class="task <?= $done ? 'task-completed' : getTaskClass($t['stress']) ?>">
                                    <div class="task-name"><?= htmlspecialchars($t['task']) ?></div>
                                    <div class="task-info">Stress: <?= $t['stress'] ?> | Giờ: <?= $t['hours'] ?></div>
                                    <?php if (!empty($t['note'])): ?>
                                        <div class="task-note"><?= htmlspecialchars($t['note']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!$t['done_today']): ?>
                                        <div class="task-actions">
                                            <a href="scheduler.php?complete_date=<?= $date ?>&complete_index=<?= $tIndex ?>" onclick="return confirm('Hoàn thành task hôm nay?')">Hoàn thành</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="day-summary">
                            Tổng Stress: <?= $schedule[$date]['stress'] ?> | Tổng Giờ: <?= $schedule[$date]['hours'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-tasks">
                    <div class="no-tasks-icon">📋</div>
                    <h3>Chưa có task nào!</h3>
                    <p>Hãy thêm task đầu tiên để bắt đầu quản lý lịch học của bạn.</p>
                    <a href="index.php" class="btn">Thêm Task</a>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php if ($task_schedule): ?>
        <script>
            const labels = <?= json_encode(array_keys($schedule)) ?>;
            const stressData = <?= json_encode(array_map(fn($v) => $v['stress'], $schedule)) ?>;
            const hoursData = <?= json_encode(array_map(fn($v) => $v['hours'], $schedule)) ?>;
            const ctx = document.getElementById('scheduleChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                            label: 'Stress',
                            data: stressData,
                            backgroundColor: 'rgba(255,99,132,0.6)'
                        },
                        {
                            label: 'Giờ',
                            data: hoursData,
                            backgroundColor: 'rgba(54,162,235,0.6)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Tổng Stress & Giờ mỗi ngày'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>

</body>

</html>