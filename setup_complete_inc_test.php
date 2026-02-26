<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Step 1: Add INC request (student submission)
$sql1 = "INSERT INTO inc_requests (student_name, student_id, student_email, user_id, professor, subject, inc_reason, inc_semester) VALUES 
('Jaylo Ludovice', '0122-1132', 'ludoviceylo26@gmail.com', 308, 'Bernardino, Mark', 'ITEC103 (Intermediate Programming)', 'Missing Finals Exam', '2nd 2025 - 2026')";

if ($conn->query($sql1)) {
    $inc_request_id = $conn->insert_id;
    echo "Step 1: INC request added (ID: $inc_request_id)<br>";
    
    // Step 2: Admin sends to dean (with teacher signature)
    $teacher_signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    
    $sql2 = "INSERT INTO dean_inc_requests (inc_request_id, student_name, student_id, student_email, professor, subject, inc_reason, inc_semester, signature) VALUES 
    ($inc_request_id, 'Jaylo Ludovice', '0122-1132', 'ludoviceylo26@gmail.com', 'Bernardino, Mark', 'ITEC103 (Intermediate Programming)', 'Missing Finals Exam', '2nd 2025 - 2026', '$teacher_signature')";
    
    if ($conn->query($sql2)) {
        echo "Step 2: Request sent to dean successfully!<br>";
        echo "Now login as dean (ID: 246) and check dean_inc.php";
    } else {
        echo "Error in step 2: " . $conn->error;
    }
} else {
    echo "Error in step 1: " . $conn->error;
}

$conn->close();
?>