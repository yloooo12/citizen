<?php
session_start();
$teacher_id = $_SESSION['id_number'] ?? '';

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    die("Connection failed");
}

// Create a test notification
$test_message = "Test INC request from John Doe for IT 101 - Intermediate Programming";
$conn->query("INSERT INTO teacher_notifications (teacher_id, message, is_read) VALUES ('$teacher_id', '$test_message', 0)");

echo "Test notification created for teacher ID: $teacher_id<br>";

// Check notifications
$result = $conn->query("SELECT * FROM teacher_notifications WHERE teacher_id='$teacher_id'");
echo "Notifications for teacher $teacher_id:<br>";
while($row = $result->fetch_assoc()) {
    echo "- " . $row['message'] . " (Read: " . $row['is_read'] . ")<br>";
}

$conn->close();
?>
<a href="teacher_dashboard.php">Back to Dashboard</a>