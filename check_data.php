<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

echo "<h3>Available Data Check</h3>";

// Check what student IDs exist
echo "<h4>Available Student IDs:</h4>";
$students = $conn->query("SELECT DISTINCT student_id FROM student_subjects LIMIT 10");
if ($students) {
    while ($row = $students->fetch_assoc()) {
        echo "- " . $row['student_id'] . "<br>";
    }
}

// Check available semesters and years
echo "<h4>Available Semesters & Years:</h4>";
$periods = $conn->query("SELECT DISTINCT semester, school_year FROM student_subjects ORDER BY school_year DESC, semester");
if ($periods) {
    while ($row = $periods->fetch_assoc()) {
        echo "- " . $row['semester'] . " " . $row['school_year'] . "<br>";
    }
}

// Check if grades table has data
echo "<h4>Sample Grades Data:</h4>";
$grades = $conn->query("SELECT student_id, subject_code, column_name, grade FROM grades LIMIT 5");
if ($grades && $grades->num_rows > 0) {
    while ($row = $grades->fetch_assoc()) {
        echo "- Student: " . $row['student_id'] . ", Subject: " . $row['subject_code'] . ", Type: " . $row['column_name'] . ", Grade: " . $row['grade'] . "<br>";
    }
} else {
    echo "No grades data found<br>";
}

$conn->close();
?>