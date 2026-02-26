<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    exit('Unauthorized');
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$teacher_id = $_SESSION['id_number'];
$subject_code = $_GET['subject_code'] ?? '';
$result = $conn->query("SELECT student_id, column_name, grade FROM grades WHERE teacher_id='$teacher_id' AND subject_code='$subject_code'");

$grades = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $grades[$row['student_id']][$row['column_name']] = $row['grade'];
    }
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo json_encode($grades);

$conn->close();
?>