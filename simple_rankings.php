<?php
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$student_id = '0122-0348'; // Change to your student ID
$semester = '1st';
$year = '2025 - 2026';

$student_subject_ranks = [];

// Get subjects for this student
$subjects_result = $conn->query("SELECT DISTINCT ss.subject_code 
                                 FROM student_subjects ss 
                                 WHERE ss.student_id='$student_id' 
                                 AND ss.semester='$semester' 
                                 AND ss.school_year='$year'");

if ($subjects_result) {
    while ($subject = $subjects_result->fetch_assoc()) {
        $subject_code = $subject['subject_code'];
        
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
                if ($grade_row['student_id'] == $student_id && $rank <= 3) {
                    $student_subject_ranks[] = [
                        'subject' => $subject_code,
                        'rank' => $rank,
                        'grade' => $grade_row['grade']
                    ];
                    break;
                }
                $rank++;
            }
        }
    }
}

echo "<h3>Subject Rankings for $student_id:</h3>";
foreach ($student_subject_ranks as $subject_rank) {
    $rank_emoji = $subject_rank['rank'] == 1 ? '🥇' : ($subject_rank['rank'] == 2 ? '🥈' : '🥉');
    echo $rank_emoji . " " . $subject_rank['subject'] . " - Rank #" . $subject_rank['rank'] . " (Grade: " . $subject_rank['grade'] . ")<br>";
}

$conn->close();
?>