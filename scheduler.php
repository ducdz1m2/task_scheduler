<?php
session_start();
// 🔹 Reset scheduler nếu bấm nút
// unset($_SESSION['task_schedule']);
// unset($_SESSION['schedule']);

if (isset($_POST['reset_schedule'])) {
    unset($_SESSION['task_schedule']);
    unset($_SESSION['schedule']);
    header('Location: scheduler.php');
    exit();
}
// Cấu hình
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
        $_SESSION['task_schedule'][$date][$task_index]['done_today'] = true;
    }
    header('Location: scheduler.php');
    exit();
}
// Build task_schedule nếu chưa có hoặc session schedule bị unset
if (!isset($_SESSION['task_schedule']) || !isset($_SESSION['schedule'])) {
    if (!file_exists('tasks.json')) die("Chưa có task nào.");

    $tasks = json_decode(file_get_contents('tasks.json'), true);

    // Cập nhật ID cho các task cũ nếu chưa có
    $updated = false;
    foreach ($tasks as &$t) {
        if (!isset($t['id'])) {
            $t['id'] = uniqid();
            $updated = true;
        }
    }
    unset($t);
    if ($updated) {
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // Sắp xếp task theo deadline
    usort($tasks, fn($a, $b) => strtotime($a['deadline']) - strtotime($b['deadline']));

    $schedule = [];
    $task_schedule = [];
    $today_ts = strtotime(date('Y-m-d'));

    foreach ($tasks as $taskIndex => $task) {
        $rem_stress = $task['stress'];
        $rem_hours = $task['hours'];
        $deadline_ts = strtotime($task['deadline']);
        $cur_ts = $today_ts;

        while ($rem_hours > 0 && $cur_ts <= $deadline_ts) {
            $date = date('Y-m-d', $cur_ts);
            if (!isset($schedule[$date])) $schedule[$date] = ['stress' => 0, 'hours' => 0];

            $days_left = ($deadline_ts - $cur_ts) / 86400 + 1;
            $max_task_hours = $days_left <= 2 ? $max_hours_near_deadline : $max_hours_per_task_per_day;

            $avail_stress = $stress_limit_per_day - $schedule[$date]['stress'];
            $avail_hours = $hours_limit_per_day - $schedule[$date]['hours'];

            $assigned_hours = min($rem_hours, $avail_hours, $max_task_hours);
            if ($assigned_hours <= 0 || $avail_stress <= 0) {
                $cur_ts = strtotime("+1 day", $cur_ts);
                continue;
            }

            $assigned_stress = round($rem_stress * ($assigned_hours / $rem_hours));
            $assigned_stress = min($assigned_stress, $avail_stress);
            $assigned_stress = max(1, $assigned_stress);

            $schedule[$date]['stress'] += $assigned_stress;
            $schedule[$date]['hours'] += $assigned_hours;

            $task_part = $task;
            $task_part['stress'] = $assigned_stress;
            $task_part['hours'] = $assigned_hours;
            $task_part['done_today'] = false;
            $task_part['task_index'] = $taskIndex;
            $task_part['id'] = $task['id'];
            $task_schedule[$date][] = $task_part;

            $rem_hours -= $assigned_hours;
            $rem_stress -= $assigned_stress;
            $cur_ts = strtotime("+1 day", $cur_ts);
        }
    }

    $_SESSION['task_schedule'] = $task_schedule;
    $_SESSION['schedule'] = $schedule;
} else {
    $task_schedule = $_SESSION['task_schedule'];
    $schedule = $_SESSION['schedule'];
}

$task_done = [];
foreach ($task_schedule as $date => $tasks) {
    foreach ($tasks as $t) {
        $task_id = $t['id'];
        if (!isset($task_done[$task_id])) $task_done[$task_id] = true;
        if (!$t['done_today']) $task_done[$task_id] = false;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Lịch học</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .day-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }

        .day-header {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .task {
            padding: 5px 10px;
            border-radius: 4px;
            margin-bottom: 5px;
        }

        .task-low {
            background: #d4edda;
        }

        .task-medium {
            background: #fff3cd;
        }

        .task-high {
            background: #f8d7da;
        }

        .task-completed {
            text-decoration: line-through;
            opacity: 0.6;
        }

        .task-note {
            font-size: 12px;
            color: #555;
            font-style: italic;
            margin-top: 2px;
        }

        .task-info {
            font-size: 12px;
            color: #333;
            margin-top: 2px;
        }

        .task-actions a {
            font-size: 12px;
            color: #007bff;
            text-decoration: none;
            margin-right: 5px;
        }

        .task-actions a:hover {
            text-decoration: underline;
        }

        .day-summary {
            font-weight: bold;
            margin-top: 10px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
        }

        canvas {
            background: #fff;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Lịch học sắp xếp thông minh</h1>
        <form method="post" style="margin-bottom: 20px;">
            <button type="submit" name="reset_schedule" style="padding: 8px 15px; background:#007bff; color:#fff; border:none; border-radius:4px; cursor:pointer;">
                Reset Lịch
            </button>
        </form>

        <h2>Biểu đồ tổng Stress & Giờ</h2>
        <canvas id="scheduleChart" width="100%" height="50"></canvas>

        <div class="schedule-grid">
            <?php foreach ($task_schedule as $date => $tasks): ?>
                <div class="day-card">
                    <div class="day-header"><?= $date ?></div>
                    <div class="tasks">
                        <?php foreach ($tasks as $tIndex => $t):
                            $done = $task_done[$t['id']] ?? false;
                        ?>
                            <div class="task <?= $done ? 'task-completed' : getTaskClass($t['stress']) ?>">
                                <div class="task-name"><?= htmlspecialchars($t['task']) ?></div>
                                <div class="task-info">Stress: <?= $t['stress'] ?> | Giờ: <?= $t['hours'] ?></div>
                                <?php if (!empty($t['note'])): ?><div class="task-note"><?= htmlspecialchars($t['note']) ?></div><?php endif; ?>
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
        </div>

        <div class="footer">
            <a class="btn" href="index.php">Quay lại trang chính</a>
        </div>
    </div>

    <script>
        const labels = <?= json_encode(array_keys($schedule)) ?>;
        const stressData = <?= json_encode(array_map(fn($v) => $v['stress'], $schedule)) ?>;
        const hoursData = <?= json_encode(array_map(fn($v) => $v['hours'], $schedule)) ?>;
        const ctx = document.getElementById('scheduleChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Stress',
                    data: stressData,
                    backgroundColor: 'rgba(255,99,132,0.6)'
                }, {
                    label: 'Giờ',
                    data: hoursData,
                    backgroundColor: 'rgba(54,162,235,0.6)'
                }]
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
</body>

</html>