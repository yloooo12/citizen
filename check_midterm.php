<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

echo "Checking midterm grades for 0122-0348:\n";
$result = $conn->query("SELECT subject_code, column_name, grade FROM grades WHERE student_id='0122-0348' AND column_name LIKE '%midterm%'");
while($row = $result->fetch_assoc()) {
    echo "Subject: {$row['subject_code']}, Column: {$row['column_name']}, Grade: {$row['grade']}\n";
}

// Create test midterm grade below 75
$conn->query("INSERT INTO grades (student_id, subject_code, column_name, grade) VALUES ('0122-0348', 'ITEC103', 'midterm_total', '60') ON DUPLICATE KEY UPDATE grade='60'");
echo "\nTest midterm grade created: ITEC103 = 60\n";

$conn->close();
?>