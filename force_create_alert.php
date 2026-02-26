<?php
$conn = new mysqli("localhost", "root", "", "student_services");

// Force create alert for current student
$student_id = '2023-00001';

// Delete existing and create new
$conn->query("DELETE FROM academic_alerts WHERE student_id='$student_id' AND alert_type='CREDITING'");

$result = $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved, created_at) 
                       VALUES ('$student_id', 'Subject Crediting', 'PENDING', 'BSIT', 'Automatic crediting for Transferee', 'Submit required documents for crediting evaluation', 'Registrar Office', '2nd', '2025 - 2026', 'CREDITING', 0, NOW())");

if ($result) {
    echo "Alert created! ID: " . $conn->insert_id;
} else {
    echo "Error: " . $conn->error;
}

// Check if it exists
$check = $conn->query("SELECT * FROM academic_alerts WHERE student_id='$student_id' AND alert_type='CREDITING' AND is_resolved=0");
echo "<br>Found alerts: " . $check->num_rows;
?>