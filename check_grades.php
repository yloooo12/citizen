<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

echo "Checking grades table structure and data:\n\n";

// Check table structure
$result = $conn->query("DESCRIBE grades");
echo "Grades table columns:\n";
while($row = $result->fetch_assoc()) {
    echo "- {$row['Field']} ({$row['Type']})\n";
}

echo "\nSample grades data:\n";
$result = $conn->query("SELECT student_id, subject_code, column_name, grade FROM grades LIMIT 10");
while($row = $result->fetch_assoc()) {
    echo "Student: {$row['student_id']}, Subject: {$row['subject_code']}, Column: {$row['column_name']}, Grade: {$row['grade']}\n";
}

echo "\nLooking for exam columns:\n";
$result = $conn->query("SELECT DISTINCT column_name FROM grades WHERE column_name LIKE '%exam%' OR column_name LIKE '%EXAM%'");
while($row = $result->fetch_assoc()) {
    echo "- {$row['column_name']}\n";
}

$conn->close();
?>