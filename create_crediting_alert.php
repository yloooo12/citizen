<?php
// Manually create crediting alert for testing
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Get current student (assuming student ID 2023-00001)
$student_id = '2023-00001';

// Check if alert already exists
$existing = $conn->query("SELECT id FROM academic_alerts WHERE student_id='$student_id' AND alert_type='CREDITING' AND is_resolved=0");

if (!$existing || $existing->num_rows == 0) {
    // Create crediting alert
    $result = $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
                           VALUES ('$student_id', 'Subject Crediting', 'PENDING', 'BSIT', 'Automatic crediting for Transferee', 'Submit required documents for crediting evaluation', 'Registrar Office', '2nd', '2025 - 2026', 'CREDITING', 0)");
    
    if ($result) {
        echo "Crediting alert created successfully!";
    } else {
        echo "Error creating alert: " . $conn->error;
    }
} else {
    echo "Crediting alert already exists.";
}

$conn->close();
?>