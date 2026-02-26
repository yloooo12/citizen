<?php
// Fix phantom INC grade issue
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Check for INC grades in grades table
echo "Checking for INC grades...\n";
$result = $conn->query("SELECT student_id, subject_code, remarks FROM grades WHERE remarks='INC'");

if ($result && $result->num_rows > 0) {
    echo "Found INC grades:\n";
    while($row = $result->fetch_assoc()) {
        echo "Student: {$row['student_id']}, Subject: {$row['subject_code']}, Remarks: {$row['remarks']}\n";
    }
    
    // Remove phantom INC grades (uncomment to execute)
    // $conn->query("DELETE FROM grades WHERE remarks='INC' AND subject_code='ITEC103'");
    // echo "Removed phantom INC grade for ITEC103\n";
} else {
    echo "No INC grades found in grades table\n";
}

$conn->close();
?>