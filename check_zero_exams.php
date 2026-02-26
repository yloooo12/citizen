<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

echo "Checking for finals_Exam = 0:\n";
$result = $conn->query("SELECT student_id, subject_code, grade FROM grades WHERE column_name = 'finals_Exam' AND grade = '0'");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Student: {$row['student_id']}, Subject: {$row['subject_code']}, Grade: {$row['grade']}\n";
    }
} else {
    echo "No students with finals_Exam = 0 found\n";
}

echo "\nAll finals_Exam grades:\n";
$result = $conn->query("SELECT student_id, subject_code, grade FROM grades WHERE column_name = 'finals_Exam' LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo "Student: {$row['student_id']}, Subject: {$row['subject_code']}, Grade: {$row['grade']}\n";
}

$conn->close();
?>