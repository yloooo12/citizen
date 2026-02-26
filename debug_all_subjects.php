<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$student_id = '0122-0348';
$semester = '1st';
$year = '2025 - 2026';

echo "<h3>All Subjects for Student $student_id:</h3>";

// Get ALL subjects for this student
$subjects_result = $conn->query("SELECT DISTINCT ss.subject_code 
                                 FROM student_subjects ss 
                                 WHERE ss.student_id='$student_id' 
                                 AND ss.semester='$semester' 
                                 AND ss.school_year='$year'");

if ($subjects_result) {
    while ($subject = $subjects_result->fetch_assoc()) {
        $subject_code = $subject['subject_code'];
        echo "<h4>Subject: $subject_code</h4>";
        
        // Check if student has finals_total grade
        $student_grade = $conn->query("SELECT grade FROM grades 
                                       WHERE student_id='$student_id' 
                                       AND subject_code='$subject_code' 
                                       AND column_name='finals_total'");
        
        if ($student_grade && $row = $student_grade->fetch_assoc()) {
            echo "Student's Grade: " . $row['grade'] . "<br>";
            
            // Get ALL students' grades for this subject
            $all_grades = $conn->query("SELECT g.student_id, g.grade 
                                       FROM grades g 
                                       INNER JOIN student_subjects ss ON g.student_id = ss.student_id AND g.subject_code = ss.subject_code
                                       WHERE g.subject_code='$subject_code' 
                                       AND g.column_name='finals_total' 
                                       AND ss.semester='$semester'
                                       AND ss.school_year='$year'
                                       ORDER BY g.grade DESC");
            
            echo "All Students in this subject:<br>";
            $rank = 1;
            if ($all_grades) {
                while ($grade_row = $all_grades->fetch_assoc()) {
                    $highlight = ($grade_row['student_id'] == $student_id) ? ' <-- YOU' : '';
                    echo "Rank #$rank: " . $grade_row['student_id'] . " - " . $grade_row['grade'] . $highlight . "<br>";
                    $rank++;
                }
            }
        } else {
            echo "No finals_total grade found for this subject<br>";
        }
        echo "<br>";
    }
}

$conn->close();
?>