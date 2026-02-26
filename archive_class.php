<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    exit('Unauthorized');
}

$conn = new mysqli("localhost", "root", "", "student_services");
$teacher_id = $_SESSION['id_number'];

if ($_POST['subject_code'] && $_POST['section'] && $_POST['semester'] && $_POST['school_year']) {
    $subject_code = $_POST['subject_code'];
    $section = $_POST['section'];
    $semester = $_POST['semester'];
    $school_year = $_POST['school_year'];
    
    // Get students with INC status
    $inc_students = [];
    $inc_query = $conn->query("SELECT DISTINCT g.student_id 
                               FROM grades g 
                               WHERE g.subject_code = '$subject_code' 
                               AND g.teacher_id = '$teacher_id' 
                               AND g.column_name = 'finals_Exam' 
                               AND g.grade = 0");
    if ($inc_query) {
        while ($row = $inc_query->fetch_assoc()) {
            $inc_students[] = $row['student_id'];
        }
    }
    
    // Archive all students in this class EXCEPT those with INC
    if (count($inc_students) > 0) {
        $inc_list = "'" . implode("','", $inc_students) . "'";
        $conn->query("UPDATE student_subjects 
                      SET archived = 1 
                      WHERE teacher_id = '$teacher_id' 
                      AND subject_code = '$subject_code' 
                      AND section = '$section' 
                      AND semester = '$semester' 
                      AND school_year = '$school_year'
                      AND student_id NOT IN ($inc_list)");
    } else {
        $conn->query("UPDATE student_subjects 
                      SET archived = 1 
                      WHERE teacher_id = '$teacher_id' 
                      AND subject_code = '$subject_code' 
                      AND section = '$section' 
                      AND semester = '$semester' 
                      AND school_year = '$school_year'");
    }
    
    echo json_encode(['success' => true, 'inc_count' => count($inc_students)]);
} else {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
}

$conn->close();
?>
