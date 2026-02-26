<?php
$conn = new mysqli("localhost", "root", "", "citizenproj");

echo "<h2>Updating notifications Table</h2>";

$queries = [
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS id_number VARCHAR(50) DEFAULT NULL"
];

foreach ($queries as $query) {
    if ($conn->query($query)) {
        echo "<p style='color:green'>✓ " . htmlspecialchars($query) . "</p>";
    } else {
        echo "<p style='color:red'>✗ Error: " . $conn->error . "</p>";
    }
}

echo "<h3>Updated Table Structure:</h3>";
$result = $conn->query("DESCRIBE notifications");
echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

$conn->close();
echo "<p><a href='admin_upload_grades.php'>Back to Upload Grades</a></p>";
?>
