<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$sql = "ALTER TABLE unscheduled_requests 
ADD COLUMN subject_code VARCHAR(50) AFTER user_id,
ADD COLUMN subject_name VARCHAR(255) AFTER subject_code,
ADD COLUMN reason TEXT AFTER subject_name,
ADD COLUMN eval_file VARCHAR(255) AFTER reason,
ADD COLUMN dean_signature VARCHAR(255) AFTER status,
ADD COLUMN dean_remarks TEXT AFTER dean_signature,
ADD COLUMN approved_at TIMESTAMP NULL AFTER dean_remarks";

if ($conn->query($sql)) {
    echo "Columns added successfully";
} else {
    echo "Error: " . $conn->error;
}

// Also drop old columns if they exist
$conn->query("ALTER TABLE unscheduled_requests DROP COLUMN request_letter");
$conn->query("ALTER TABLE unscheduled_requests DROP COLUMN eval_grades");
$conn->query("ALTER TABLE unscheduled_requests DROP COLUMN created_at");

$conn->close();
?>
