<?php
$conn = new mysqli("localhost", "root", "", "student_services");

echo "<h3>Dean INC Requests Table Check</h3>";

// Check table structure
echo "<h4>Table Structure:</h4>";
$result = $conn->query("DESCRIBE dean_inc_requests");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Table doesn't exist or error: " . $conn->error;
}

// Check current data
echo "<h4>Current Data:</h4>";
$result = $conn->query("SELECT * FROM dean_inc_requests ORDER BY id DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Student</th><th>Subject</th><th>Professor</th><th>Dean Approved</th><th>Created</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['student_name'] . "</td>";
        echo "<td>" . $row['subject'] . "</td>";
        echo "<td>" . $row['professor'] . "</td>";
        echo "<td>" . ($row['dean_approved'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['created_at'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data found.";
}

$conn->close();
?>
<br><a href="admin_inc.php">Back to Admin INC</a>