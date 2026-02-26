<?php
// Clean up duplicate crediting alerts
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Cleaning up duplicate crediting alerts...<br>";

// Delete duplicate CREDITING alerts, keep only the first one for each student
$cleanup_sql = "DELETE aa1 FROM academic_alerts aa1
                INNER JOIN academic_alerts aa2 
                WHERE aa1.id > aa2.id 
                AND aa1.student_id = aa2.student_id 
                AND aa1.alert_type = 'CREDITING' 
                AND aa2.alert_type = 'CREDITING'";

$result = $conn->query($cleanup_sql);

if ($result) {
    echo "✅ Duplicate crediting alerts cleaned up successfully!<br>";
    echo "Affected rows: " . $conn->affected_rows . "<br>";
} else {
    echo "❌ Error cleaning up duplicates: " . $conn->error . "<br>";
}

// Show remaining crediting alerts
echo "<br><strong>Remaining crediting alerts:</strong><br>";
$remaining = $conn->query("SELECT student_id, course, created_at FROM academic_alerts WHERE alert_type='CREDITING' ORDER BY student_id, created_at");
if ($remaining && $remaining->num_rows > 0) {
    while($row = $remaining->fetch_assoc()) {
        echo "- Student: {$row['student_id']}, Course: {$row['course']}, Created: {$row['created_at']}<br>";
    }
} else {
    echo "No crediting alerts found.<br>";
}

$conn->close();
?>