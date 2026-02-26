<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$student_id = '2021-001'; // Change this to your student ID
$semester = '1st';
$year = '2024 - 2025';
$course = 'B. S. Information Technology';

echo "<h3>Debug Rankings for Student: $student_id</h3>";

// Check if student has grades
$grades_check = $conn->query("SELECT g.subject_code, g.grade, s.subject_name 
                              FROM grades g 
                              LEFT JOIN subjects s ON g.subject_code = s.subject_code
                              INNER JOIN student_subjects ss ON g.student_id = ss.student_id AND g.subject_code = ss.subject_code
                              WHERE g.student_id='$student_id' 
                              AND g.column_name='finals_total' 
                              AND ss.semester='$semester'
                              AND ss.school_year='$year'");

echo "<h4>Student's Grades:</h4>";
if ($grades_check && $grades_check->num_rows > 0) {
    while ($row = $grades_check->fetch_assoc()) {
        echo "- " . $row['subject_code'] . " (" . ($row['subject_name'] ?? 'No name') . "): " . $row['grade'] . "<br>";
    }
} else {
    echo "No grades found for this student in $semester $year<br>";
}

// Check all students in same program
echo "<h4>All Students in Same Program:</h4>";
$all_students = $conn->query("SELECT DISTINCT ss.student_id 
                              FROM student_subjects ss 
                              WHERE ss.semester='$semester' 
                              AND ss.school_year='$year'
                              AND ss.program='$course'");

if ($all_students) {
    while ($student = $all_students->fetch_assoc()) {
        echo "- " . $student['student_id'] . "<br>";
    }
}

$conn->close();
?>