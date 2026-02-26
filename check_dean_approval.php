<?php
$conn = new mysqli("localhost", "root", "", "student_services");

echo "<h3>Check Dean Approval Status</h3>";

// Check inc_requests table
echo "<h4>INC Requests Table:</h4>";
$result = $conn->query("SELECT id, student_name, dean_approved FROM inc_requests ORDER BY id DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Student</th><th>Dean Approved</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['student_name'] . "</td>";
        echo "<td>" . ($row['dean_approved'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data found.";
}

// Check dean_inc_requests table
echo "<h4>Dean INC Requests Table:</h4>";
$result = $conn->query("SELECT id, student_name, dean_approved, inc_request_id FROM dean_inc_requests ORDER BY id DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Student</th><th>Dean Approved</th><th>Original ID</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['student_name'] . "</td>";
        echo "<td>" . ($row['dean_approved'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['inc_request_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data found.";
}

$conn->close();
?>
<br><a href="admin_inc.php">Back to Admin INC</a>