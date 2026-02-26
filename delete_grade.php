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

if ($_POST) {
    $teacher_id = $_SESSION['id_number'];
    
    if (isset($_POST['student_id']) && isset($_POST['column_name'])) {
        // Delete specific grade
        $student_id = $_POST['student_id'];
        $column_name = $_POST['column_name'];
        $stmt = $conn->prepare("DELETE FROM grades WHERE student_id = ? AND teacher_id = ? AND column_name = ?");
        $stmt->bind_param("sss", $student_id, $teacher_id, $column_name);
        $stmt->execute();
    } elseif (isset($_POST['student_id'])) {
        // Delete all grades for student
        $student_id = $_POST['student_id'];
        $stmt = $conn->prepare("DELETE FROM grades WHERE student_id = ? AND teacher_id = ?");
        $stmt->bind_param("ss", $student_id, $teacher_id);
        $stmt->execute();
    } elseif (isset($_POST['reset_all'])) {
        // Delete all grades for teacher
        $stmt = $conn->prepare("DELETE FROM grades WHERE teacher_id = ?");
        $stmt->bind_param("s", $teacher_id);
        $stmt->execute();
    }
    
    echo 'deleted';
}

$conn->close();
?>