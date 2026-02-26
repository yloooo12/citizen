<?php
session_start();
header('Content-Type: application/json');

$teacher_id = $_SESSION['id_number'] ?? '';
if (!$teacher_id) {
    echo json_encode([]);
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$result = $conn->query("SELECT message, is_read, created_at FROM teacher_notifications WHERE teacher_id='$teacher_id' ORDER BY created_at DESC LIMIT 10");
$notifications = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        $notifications[] = [
            'message' => $row['message'],
            'is_read' => $row['is_read'],
            'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
        ];
    }
}

$conn->close();
echo json_encode($notifications);
?>