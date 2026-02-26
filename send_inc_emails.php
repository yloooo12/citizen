<?php
require_once 'configure_email.php';
$conn = new mysqli("localhost", "root", "", "student_services");

$result = $conn->query("SELECT DISTINCT g.student_id, g.subject_code, s.subject_name, u.first_name, u.last_name, u.email, t.first_name as t_first, t.last_name as t_last
                        FROM grades g
                        LEFT JOIN subjects s ON g.subject_code = s.subject_code
                        LEFT JOIN users u ON g.student_id = u.id_number
                        LEFT JOIN student_subjects ss ON g.student_id = ss.student_id AND g.subject_code = ss.subject_code
                        LEFT JOIN users t ON ss.teacher_id = t.id_number
                        WHERE g.column_name = 'finals_Exam' AND g.grade = 0");

echo "<h2>Sending INC Emails...</h2>";

while ($row = $result->fetch_assoc()) {
    $student_id = $row['student_id'];
    $subject_code = $row['subject_code'];
    $subject_name = $row['subject_name'] ?? 'Subject';
    $student_name = $row['first_name'];
    $student_email = $row['email'];
    $teacher_name = ($row['t_last'] ?? 'Teacher') . ', ' . ($row['t_first'] ?? '');
    $course_title = $subject_code . ' (' . $subject_name . ')';
    
    $check = $conn->query("SELECT id FROM academic_alerts WHERE student_id='$student_id' AND course='$course_title' AND alert_type IN ('INC', 'EXAM') AND is_resolved=0");
    
    if (!$check || $check->num_rows == 0) {
        $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
                      VALUES ('$student_id', '$course_title', 'INC', 'B. S. Information Technology', 'INC', 'Contact instructor: $teacher_name', '$teacher_name', '2nd', '2025 - 2026', 'INC', 0)");
        echo "✓ Created INC alert for $student_name - $course_title<br>";
    }
    
    $subject = 'Academic Alert: Incomplete Grade';
    $message = "Dear $student_name,\n\nYou have an INCOMPLETE (INC) grade for $course_title due to missing final exam (score: 0).\n\nPlease contact your instructor: $teacher_name to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS";
    
    sendEmail($student_email, $subject, $message, $student_name);
    echo "✓ Email notification sent to $student_name ($student_email) - $course_title<br>";
    
    $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', 'Academic Alert: You have an INCOMPLETE (INC) grade for $course_title. Contact instructor: $teacher_name to complete requirements.', 'academic_alert', 0)");
}

echo "<br><strong>Done!</strong>";
$conn->close();
?>
