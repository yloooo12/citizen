<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Create test INC grade
$conn->query("INSERT INTO grades (student_id, teacher_id, subject_code, column_name, grade) VALUES ('0122-0348', 'T001', 'ITEC103', 'finals_Exam', '0')");
echo "Test INC grade created for student 0122-0348 in ITEC103\n";

$conn->close();
?>