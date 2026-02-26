<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$student_id = '0122-0348'; // Using actual student ID from data
$semester = '1st';
$year = '2025 - 2026';

echo "<h3>Rankings Test for Student: $student_id</h3>";

// Get subjects for this student
$subjects_result = $conn->query("SELECT DISTINCT ss.subject_code, s.subject_name 
                                 FROM student_subjects ss 
                                 LEFT JOIN subjects s ON ss.subject_code = s.subject_code
                                 WHERE ss.student_id='$student_id' 
                                 AND ss.semester='$semester' 
                                 AND ss.school_year='$year'");

if ($subjects_result) {
    while ($subject = $subjects_result->fetch_assoc()) {
        $subject_code = $subject['subject_code'];
        $subject_name = $subject['subject_name'] ?? $subject_code;
        
        echo "<h4>Subject: $subject_name ($subject_code)</h4>";
        
        // Get all students' grades for this subject
        $grades_result = $conn->query("SELECT g.student_id, g.grade 
                                       FROM grades g 
                                       INNER JOIN student_subjects ss ON g.student_id = ss.student_id AND g.subject_code = ss.subject_code
                                       WHERE g.subject_code='$subject_code' 
                                       AND g.column_name='finals_total' 
                                       AND ss.semester='$semester'
                                       AND ss.school_year='$year'
                                       ORDER BY g.grade DESC");
        
        $rank = 1;
        if ($grades_result) {
            while ($grade_row = $grades_result->fetch_assoc()) {
                $color = ($grade_row['student_id'] == $student_id) ? 'style="background: yellow;"' : '';
                echo "<div $color>Rank #$rank: Student " . $grade_row['student_id'] . " - Grade: " . $grade_row['grade'] . "</div>";
                $rank++;
            }
        }
        echo "<br>";
    }
}

$conn->close();
?>