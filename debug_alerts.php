<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_services");
$student_id = $_SESSION["id_number"] ?? '2023-00001';

echo "<h3>Debug Academic Alerts for Student: $student_id</h3>";

// Check alerts query
$result = $conn->query("SELECT aa.*, 
                               cr.status as crediting_status,
                               phc.status as program_head_status
                        FROM academic_alerts aa
                        LEFT JOIN crediting_requests cr ON aa.student_id = cr.student_id AND aa.alert_type = 'CREDITING'
                        LEFT JOIN program_head_crediting phc ON aa.student_id = phc.student_id AND aa.alert_type = 'CREDITING'
                        WHERE aa.student_id='$student_id' AND aa.alert_type IN ('INC', 'EXAM', 'CREDITING') AND aa.is_resolved=0 
                        ORDER BY aa.created_at DESC");

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Alert ID: {$row['id']}<br>";
        echo "Type: {$row['alert_type']}<br>";
        echo "Course: {$row['course']}<br>";
        echo "Status: " . ($row['crediting_status'] ?? $row['program_head_status'] ?? 'pending') . "<br>";
        echo "Resolved: {$row['is_resolved']}<br><br>";
    }
} else {
    echo "No alerts found!<br>";
    
    // Check if student exists
    $student_check = $conn->query("SELECT student_type FROM users WHERE id_number='$student_id'");
    if ($student_check && $row = $student_check->fetch_assoc()) {
        echo "Student type: " . $row['student_type'] . "<br>";
    } else {
        echo "Student not found!<br>";
    }
}
?>