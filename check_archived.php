<?php
$conn = new mysqli("localhost", "root", "", "student_services");

echo "<h3>Checking archived column:</h3>";

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM student_subjects LIKE 'archived'");
if ($result->num_rows == 0) {
    echo "❌ Column 'archived' does NOT exist!<br>";
    echo "Adding column...<br>";
    $conn->query("ALTER TABLE student_subjects ADD COLUMN archived TINYINT DEFAULT 0");
    echo "✅ Column added!<br>";
} else {
    echo "✅ Column 'archived' exists<br>";
}

// Show sample data
echo "<h3>Sample data:</h3>";
$data = $conn->query("SELECT subject_code, section, semester, school_year, student_id, archived FROM student_subjects LIMIT 10");
echo "<table border='1'><tr><th>Subject</th><th>Section</th><th>Semester</th><th>School Year</th><th>Student ID</th><th>Archived</th></tr>";
while ($row = $data->fetch_assoc()) {
    echo "<tr><td>{$row['subject_code']}</td><td>{$row['section']}</td><td>{$row['semester']}</td><td>{$row['school_year']}</td><td>{$row['student_id']}</td><td>{$row['archived']}</td></tr>";
}
echo "</table>";

$conn->close();
?>
