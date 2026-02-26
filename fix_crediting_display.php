<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Check current alerts
echo "<h3>Current Academic Alerts:</h3>";
$result = $conn->query("SELECT * FROM academic_alerts WHERE is_resolved=0 ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} - Student: {$row['student_id']} - Type: {$row['alert_type']} - Course: {$row['course']}<br>";
    }
} else {
    echo "No alerts found.<br>";
}

// Force create crediting alert for student 2023-00001
$student_id = '2023-00001';
$conn->query("DELETE FROM academic_alerts WHERE student_id='$student_id' AND alert_type='CREDITING'");
$result = $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved, created_at) 
                       VALUES ('$student_id', 'Subject Crediting', 'PENDING', 'BSIT', 'Automatic crediting for Transferee', 'Submit required documents for crediting evaluation', 'Registrar Office', '2nd', '2025 - 2026', 'CREDITING', 0, NOW())");

if ($result) {
    echo "<br><strong>Crediting alert recreated successfully!</strong>";
} else {
    echo "<br>Error: " . $conn->error;
}

$conn->close();
?>