<?php
session_start();

$teacher_id = $_SESSION['id_number'] ?? '';
if (!$teacher_id) exit;

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) exit;

$conn->query("UPDATE teacher_notifications SET is_read=1 WHERE teacher_id='$teacher_id' AND is_read=0");
$conn->close();
?>