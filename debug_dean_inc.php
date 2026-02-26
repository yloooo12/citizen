<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

echo "<h3>Debug Dean INC Requests</h3>";

// Check session
echo "<p><strong>Session Info:</strong></p>";
echo "is_admin: " . (isset($_SESSION["is_admin"]) ? ($_SESSION["is_admin"] ? 'true' : 'false') : 'not set') . "<br>";
echo "id_number: " . ($_SESSION["id_number"] ?? 'not set') . "<br>";

// Check dean_inc_requests data
echo "<p><strong>Dean INC Requests Data:</strong></p>";
$result = $conn->query("SELECT COUNT(*) as count FROM dean_inc_requests");
$count = $result->fetch_assoc()['count'];
echo "Total records: $count<br><br>";

if ($count > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Student</th><th>Subject</th><th>Professor</th><th>Status</th></tr>";
    $result = $conn->query("SELECT id, student_name, subject, professor, dean_approved FROM dean_inc_requests");
    while($row = $result->fetch_assoc()) {
        $status = $row['dean_approved'] ? 'Approved' : 'Pending';
        echo "<tr><td>{$row['id']}</td><td>{$row['student_name']}</td><td>{$row['subject']}</td><td>{$row['professor']}</td><td>$status</td></tr>";
    }
    echo "</table>";
} else {
    echo "No data found in dean_inc_requests table.";
}

$conn->close();
?>