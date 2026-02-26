<?php
$conn = new mysqli("localhost", "root", "", "student_services");

echo "<h3>Program Head Crediting Table Data:</h3>";
$result = $conn->query("SELECT * FROM program_head_crediting ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} - Student: {$row['student_name']} ({$row['student_id']}) - Status: {$row['status']}<br>";
    }
} else {
    echo "No data found in program_head_crediting table<br>";
}

echo "<br><h3>Creating test data...</h3>";
$conn->query("INSERT INTO program_head_crediting (student_id, student_name, student_type, subjects_to_credit, transcript_info, status) 
              VALUES ('2023-00001', 'John Doe', 'Transferee', 'Programming 1, Database Systems', 'Previous School: ABC University', 'pending')");

echo "Test data created!<br>";
echo "<a href='program_head_dashboard.php'>Go to Program Head Dashboard</a>";
?>