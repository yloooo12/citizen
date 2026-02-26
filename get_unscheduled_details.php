<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed']);
    exit;
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM unscheduled_requests WHERE id=$id");

if ($result && $row = $result->fetch_assoc()) {
    $row['date_submitted'] = date('M d, Y h:i A', strtotime($row['date_submitted']));
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Request not found']);
}

$conn->close();
?>
