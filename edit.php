<?php
if (!isset($_GET['index'])) exit("Không xác định task.");

$index = intval($_GET['index']);
$tasks = json_decode(file_get_contents('tasks.json'), true);
$task = $tasks[$index];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tasks[$index]['task'] = $_POST['task'];
    $tasks[$index]['stress'] = intval($_POST['stress']);
    $tasks[$index]['deadline'] = $_POST['deadline'];
    $tasks[$index]['hours'] = intval($_POST['hours']);
    $tasks[$index]['note'] = $_POST['note'];

    file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT));
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa task</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <div class="card form-card" style="max-width:500px; margin:auto; margin-top:50px;">
            <h1>Chỉnh sửa task</h1>
            <form method="post">
                <label>Tên task:</label>
                <input type="text" name="task" value="<?php echo htmlspecialchars($task['task']); ?>" required>

                <label>Stress (1-10):</label>
                <input type="number" name="stress" min="1" max="10" value="<?php echo $task['stress']; ?>" required>

                <label>Số giờ để hoàn thành:</label>
                <input type="number" name="hours" min="1" value="<?php echo $task['hours']; ?>" required>

                <label>Deadline:</label>
                <input type="date" name="deadline" value="<?php echo $task['deadline']; ?>" required>
                <label>Ghi chú:</label>
                <textarea name="note" rows="3"><?php echo htmlspecialchars($task['note'] ?? ''); ?></textarea>


                <input type="submit" value="Lưu thay đổi">
            </form>
            <br>
            <a class="btn" href="index.php">Quay lại trang chính</a>
        </div>
    </div>
</body>

</html>