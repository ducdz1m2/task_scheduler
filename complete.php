<?php
if (!isset($_GET['date']) || !isset($_GET['task_index'])) exit("Không xác định task.");

$date = $_GET['date'];
$task_index = intval($_GET['task_index']);

// Lấy task_schedule từ JSON cache nếu có, hoặc từ PHP session
// Đơn giản: lưu task_schedule tạm trong session
session_start();
if (!isset($_SESSION['task_schedule'][$date][$task_index])) exit("Task không tồn tại");

$_SESSION['task_schedule'][$date][$task_index]['done_today'] = true;

header('Location: scheduler.php');
exit();
