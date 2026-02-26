<?php
// Cleanup duplicate CREDITING alerts - keep only one per student
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Remove duplicate CREDITING alerts, keeping only the first one for each student
$cleanup_query = "
DELETE aa1 FROM academic_alerts aa1
INNER JOIN academic_alerts aa2 
WHERE aa1.id > aa2.id 
AND aa1.student_id = aa2.student_id 
AND aa1.alert_type = 'CREDITING' 
AND aa2.alert_type = 'CREDITING'
AND aa1.is_resolved = 0 
AND aa2.is_resolved = 0
";

if ($conn->query($cleanup_query)) {
    echo "Duplicate CREDITING alerts cleaned up successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>