<?php
require 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("UPDATE tasks SET done = 1 WHERE id = $id");
}

header('Location: index.php');
exit();
