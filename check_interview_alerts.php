<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_services");
$student_id = $_SESSION["id_number"] ?? '';

echo "<h3>Interview Requests:</h3>";
$result = $conn->query("SELECT * FROM admission_interviews WHERE student_id='$student_id'");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Status: {$row['status']}, Date: {$row['created_at']}<br>";
    }
} else {
    echo "No interview requests found<br>";
}

echo "<h3>Academic Alerts (INTERVIEW):</h3>";
$result = $conn->query("SELECT * FROM academic_alerts WHERE student_id='$student_id' AND alert_type='INTERVIEW'");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Course: {$row['course']}, Resolved: {$row['is_resolved']}<br>";
    }
} else {
    echo "No interview alerts found<br>";
}

$conn->close();
?>
