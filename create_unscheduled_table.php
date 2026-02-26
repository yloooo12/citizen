<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Create unscheduled_requests table
$sql = "CREATE TABLE IF NOT EXISTS unscheduled_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100),
    student_id VARCHAR(50),
    student_email VARCHAR(100),
    user_id INT,
    subject_code VARCHAR(50),
    subject_name VARCHAR(255),
    reason TEXT,
    eval_file VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pending',
    dean_signature VARCHAR(255),
    dean_remarks TEXT,
    approved_at TIMESTAMP NULL,
    date_submitted TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Table created successfully";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
