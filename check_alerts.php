<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

echo "Academic alerts for 0122-0348:\n";
$result = $conn->query("SELECT * FROM academic_alerts WHERE student_id='0122-0348'");
while($row = $result->fetch_assoc()) {
    echo "Course: {$row['course']}, Type: {$row['alert_type']}, Resolved: {$row['is_resolved']}\n";
}

echo "\nGrades for 0122-0348:\n";
$result = $conn->query("SELECT subject_code, column_name, grade FROM grades WHERE student_id='0122-0348' AND column_name='finals_Exam'");
while($row = $result->fetch_assoc()) {
    echo "Subject: {$row['subject_code']}, Column: {$row['column_name']}, Grade: {$row['grade']}\n";
}

$conn->close();
?>