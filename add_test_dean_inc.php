<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Add test data to dean_inc_requests
$sql = "INSERT INTO dean_inc_requests (inc_request_id, student_name, student_id, student_email, professor, subject, inc_reason, inc_semester, signature) VALUES 
(1, 'Jaylo Ludovice', '0122-1132', 'ludoviceylo26@gmail.com', 'Bernardino, Mark', 'ITEC103 (Intermediate Programming)', 'Missing Finals Exam', '2nd 2025 - 2026', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='),
(2, 'Test Student', '0122-0001', 'test@example.com', 'Santos, Maria', 'ITEC204 (Data Structure)', 'Incomplete Requirements', '1st 2025 - 2026', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==')";

if ($conn->query($sql)) {
    echo "Test data added successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>