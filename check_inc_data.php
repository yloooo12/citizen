<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

echo "INC Requests Table:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM inc_requests");
$count = $result->fetch_assoc()['count'];
echo "Total inc_requests: $count\n\n";

if ($count > 0) {
    $result = $conn->query("SELECT id, student_name, subject, dean_approved FROM inc_requests LIMIT 5");
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Student: {$row['student_name']}, Subject: {$row['subject']}, Dean Approved: {$row['dean_approved']}\n";
    }
}

echo "\n\nDean INC Requests Table:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM dean_inc_requests");
$count = $result->fetch_assoc()['count'];
echo "Total dean_inc_requests: $count\n\n";

if ($count > 0) {
    $result = $conn->query("SELECT id, student_name, subject, dean_approved FROM dean_inc_requests LIMIT 5");
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Student: {$row['student_name']}, Subject: {$row['subject']}, Dean Approved: {$row['dean_approved']}\n";
    }
}

$conn->close();
?>