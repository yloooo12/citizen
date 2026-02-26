<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    exit('Unauthorized');
}

$conn = new mysqli("localhost", "root", "", "student_services");
$teacher_id = $_SESSION['id_number'];

if ($_POST && isset($_POST['students'])) {
    $students = explode(',', $_POST['students']);
    foreach ($students as $student) {
        list($student_id, $subject_code) = explode('|', $student);
        $conn->query("UPDATE student_subjects SET archived = 0 WHERE student_id = '$student_id' AND subject_code = '$subject_code' AND teacher_id = '$teacher_id'");
    }
    header("Location: teacher_archived.php");
}
$conn->close();
?>
