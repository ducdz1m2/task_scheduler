<?php
require 'db.php'; // 🔹 kết nối DB

if (!isset($_GET['id'])) exit("Không xác định task.");

$id = intval($_GET['id']);

// Lấy task từ DB
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    exit("Không tìm thấy task.");
}

// Nếu người dùng bấm "Lưu thay đổi"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE tasks SET task=?, stress=?, hours=?, start_date=?, deadline=?, note=? WHERE id=?");
    $stmt->bind_param(
        "sissssi",
        $_POST['task'],       // s
        $_POST['stress'],     // i
        $_POST['hours'],      // i
        $_POST['start_date'], // s
        $_POST['deadline'],   // s
        $_POST['note'],       // s
        $id                   // i
    );
    $stmt->execute();

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

                <label>Ngày bắt đầu:</label>
                <input type="date" name="start_date" value="<?php echo $task['start_date']; ?>" required>

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