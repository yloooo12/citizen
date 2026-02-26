<?php
// Check existing crediting alerts
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

echo "<h3>Academic Alerts (CREDITING type):</h3>";
$result = $conn->query("SELECT * FROM academic_alerts WHERE alert_type='CREDITING' AND is_resolved=0");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Student ID: " . $row['student_id'] . " - Course: " . $row['course'] . " - Status: " . $row['grade'] . "<br>";
    }
} else {
    echo "No CREDITING alerts found.<br>";
}

echo "<br><h3>Student Types:</h3>";
$result = $conn->query("SELECT id_number, first_name, student_type FROM users WHERE student_type IN ('Transferee', 'Shifter', 'Returnee')");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id_number'] . " - Name: " . $row['first_name'] . " - Type: " . $row['student_type'] . "<br>";
    }
} else {
    echo "No students with crediting-eligible types found.<br>";
}

$conn->close();
?>