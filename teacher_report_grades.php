<?php
session_start();

if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$teacher_id = $_SESSION['id_number'] ?? '';
$subject = $_GET['subject'] ?? $_SESSION['assigned_lecture'] ?? '';
$year_filter = $_GET['year'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$section_filter = $_GET['section'] ?? '';

// Get students with grades
$query = "SELECT u.id_number, u.first_name, u.last_name, ss.section, ss.year_level, ss.semester
    FROM users u
    INNER JOIN student_subjects ss ON u.id_number = ss.student_id
    WHERE ss.teacher_id = '$teacher_id' AND ss.subject_code = '$subject'";

if ($year_filter) $query .= " AND ss.year_level = '$year_filter'";
if ($semester_filter) $query .= " AND ss.semester = '$semester_filter'";
if ($section_filter) $query .= " AND ss.section = '$section_filter'";

$query .= " ORDER BY u.last_name, u.first_name";

$students_query = $conn->query($query);
if (!$students_query) {
    die("Query failed: " . $conn->error . "<br>Query: " . $query);
}

$students = [];
while($row = $students_query->fetch_assoc()) {
    $student_id = $row['id_number'];
    
    // Get grades from grades table
    $grade_query = $conn->query("SELECT column_name, grade FROM grades WHERE student_id = '$student_id' AND subject_code = '$subject' AND teacher_id = '$teacher_id'");
    $midterm = 0;
    $finals = 0;
    $finals_exam = 0;
    
    if ($grade_query) {
        while($g = $grade_query->fetch_assoc()) {
            if ($g['column_name'] == 'midterm_total') $midterm = $g['grade'];
            if ($g['column_name'] == 'finals_total') $finals = $g['grade'];
            if ($g['column_name'] == 'finals_Exam') $finals_exam = $g['grade'];
        }
    }
    
    $final = ($midterm + $finals) / 2;
    $rounded = round($final);
    
    // Calculate equivalent and remarks
    if ($finals_exam == 0 && $finals > 0) {
        $eqv = 'INC';
        $remarks = 'INCOMPLETE';
    } elseif ($final >= 99) { $eqv = '1.00'; $remarks = 'PASSED'; }
    elseif ($final >= 96) { $eqv = '1.25'; $remarks = 'PASSED'; }
    elseif ($final >= 93) { $eqv = '1.50'; $remarks = 'PASSED'; }
    elseif ($final >= 90) { $eqv = '1.75'; $remarks = 'PASSED'; }
    elseif ($final >= 87) { $eqv = '2.00'; $remarks = 'PASSED'; }
    elseif ($final >= 84) { $eqv = '2.25'; $remarks = 'PASSED'; }
    elseif ($final >= 81) { $eqv = '2.50'; $remarks = 'PASSED'; }
    elseif ($final >= 78) { $eqv = '2.75'; $remarks = 'PASSED'; }
    elseif ($final >= 75) { $eqv = '3.00'; $remarks = 'PASSED'; }
    elseif ($final >= 70) { $eqv = '4.00'; $remarks = 'CONDITIONAL'; }
    else { $eqv = '5.00'; $remarks = 'FAILED'; }
    
    // Save to report_grades table
    $conn->query("INSERT INTO report_grades (student_id, subject_code, midterm, finals, final_grade, rounded_grade, equivalent, remarks) 
        VALUES ('$student_id', '$subject', $midterm, $finals, $final, $rounded, '$eqv', '$remarks')
        ON DUPLICATE KEY UPDATE midterm=$midterm, finals=$finals, final_grade=$final, rounded_grade=$rounded, equivalent='$eqv', remarks='$remarks'");
    
    // Send notification if INC (only once)
    if ($eqv == 'INC') {
        $check = $conn->query("SELECT id FROM notifications WHERE user_id='$student_id' AND message LIKE '%$subject%INC%' LIMIT 1");
        if ($check && $check->num_rows == 0) {
            $student_info = $conn->query("SELECT u.first_name, u.last_name, u.email, ss.subject_title 
                                          FROM users u 
                                          INNER JOIN student_subjects ss ON u.id_number = ss.student_id 
                                          WHERE u.id_number = '$student_id' AND ss.subject_code = '$subject' LIMIT 1");
            
            if ($student_info && $sinfo = $student_info->fetch_assoc()) {
                $name = $sinfo['first_name'] . ' ' . $sinfo['last_name'];
                $email = $sinfo['email'];
                $subject_title = $sinfo['subject_title'];
                
                $message = "Your grade in $subject ($subject_title) is marked as INCOMPLETE (INC). Reason: Missing Finals Exam. Please contact your instructor.";
                $conn->query("INSERT INTO notifications (user_id, message, type, created_at) 
                              VALUES ('$student_id', '$message', 'warning', NOW())");
                
                $to = $email;
                $email_subject = "Grade Status: $subject - Incomplete (INC)";
                $body = "Dear $name,\n\nYour grade in $subject ($subject_title) is marked as INCOMPLETE (INC).\n\nReason: Missing Finals Exam\n\nPlease contact your instructor to complete the requirements.\n\nBest regards,\nLSPU CCS";
                $headers = "From: noreply@lspu.edu.ph";
                
                @mail($to, $email_subject, $body, $headers);
            }
        }
    }
    
    $row['midterm'] = $midterm;
    $row['finals'] = $finals;
    $row['final_grade'] = number_format($final, 2);
    $row['eqv'] = $eqv;
    $row['remarks'] = $remarks;
    $row['rounded'] = $rounded;
    $students[] = $row;
}

// Calculate ranks
usort($students, function($a, $b) { return $b['final_grade'] <=> $a['final_grade']; });
$rank = 1;
$prev_grade = null;
foreach($students as $i => &$student) {
    if ($prev_grade !== null && $student['final_grade'] < $prev_grade) {
        $rank = $i + 1;
    }
    $student['rank'] = $rank;
    $prev_grade = $student['final_grade'];
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report of Grades</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; min-height: 100vh; padding: 2rem; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        
        .no-print { margin-bottom: 2rem; display: flex; gap: 1rem; justify-content: center; }
        .btn { padding: 0.9rem 2rem; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 1rem; display: inline-flex; align-items: center; gap: 0.7rem; transition: all 0.3s; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3); }
        .btn:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-close { background: #ef4444; }
        .btn-close:hover { background: #dc2626; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); }
        
        .header { text-align: center; margin-bottom: 3rem; padding: 2rem; background: #667eea; border-radius: 8px; color: white; }
        .header h2 { font-size: 1.8rem; margin: 0.3rem 0; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .header h3 { font-size: 1.4rem; margin-top: 1rem; font-weight: 600; background: rgba(255,255,255,0.2); padding: 0.5rem 1.5rem; border-radius: 8px; display: inline-block; }
        
        .content-wrapper { display: grid; grid-template-columns: 300px 1fr; gap: 2rem; margin-bottom: 2rem; }
        
        .legend { background: #f8f9fa; padding: 2rem; border-radius: 8px; border: 2px solid #e2e8f0; }
        .legend-title { font-weight: 800; font-size: 1.1rem; color: #2d3748; margin-bottom: 1.5rem; text-align: center; text-transform: uppercase; letter-spacing: 1px; padding-bottom: 0.8rem; border-bottom: 3px solid #667eea; }
        .legend-item { display: flex; justify-content: space-between; padding: 0.6rem 0.8rem; font-size: 0.9rem; color: #2d3748; background: white; margin-bottom: 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0; }
        .legend-item span:first-child { font-weight: 700; color: #667eea; }
        .legend-item span:last-child { font-size: 0.85rem; color: #4a5568; }
        
        .table-wrapper { overflow-x: auto; background: white; border-radius: 8px; border: 2px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 0.8rem; text-align: center; font-size: 0.95rem; border: 1px solid #e2e8f0; }
        th { background: #667eea; color: white; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
        td { background: white; }
        tbody tr { transition: all 0.2s; }
        tbody tr:hover { background: #f7fafc !important; }
        tbody tr:nth-child(even) td { background: #fafbfc; }
        

        
        .student-name { text-align: left; font-weight: 600; color: #2d3748; }
        .passed { color: #10b981; font-weight: 700; }
        .conditional { color: #f59e0b; font-weight: 700; }
        .failed { color: #ef4444; font-weight: 700; }
        
        .rank-1 { background: #fef3c7 !important; }
        .rank-1:hover { background: #fde68a !important; }
        .rank-2 { background: #e5e7eb !important; }
        .rank-2:hover { background: #d1d5db !important; }
        .rank-3 { background: #fed7aa !important; }
        .rank-3:hover { background: #fdba74 !important; }
        
        .rank-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 6px; font-weight: 700; font-size: 0.85rem; }
        .rank-1 .rank-badge { background: #fbbf24; color: #78350f; }
        .rank-2 .rank-badge { background: #9ca3af; color: #1f2937; }
        .rank-3 .rank-badge { background: #fb923c; color: #7c2d12; }
        
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; }
            .container { box-shadow: none; padding: 1.5rem; }
            .content-wrapper { display: block; }
            .legend { margin-bottom: 1.5rem; }
            tbody tr:hover { transform: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
            <button class="btn btn-close" onclick="window.close()"><i class="fas fa-times"></i> Close</button>
        </div>

        <div class="header">
            <h2>COLLEGE OF COMPUTER STUDIES</h2>
            <h2>REPORT OF GRADES</h2>
            <h3><?php echo htmlspecialchars($subject); ?></h3>
            <?php if ($year_filter || $semester_filter || $section_filter): ?>
            <div style="margin-top: 1rem; font-size: 0.95rem; opacity: 0.95;">
                <?php if ($year_filter) echo "Year: $year_filter"; ?>
                <?php if ($semester_filter) echo ($year_filter ? ' | ' : '') . "Semester: $semester_filter"; ?>
                <?php if ($section_filter) echo (($year_filter || $semester_filter) ? ' | ' : '') . "Section: BSIT $section_filter"; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="content-wrapper">
            <div class="legend">
                <div class="legend-title">GRADING SYSTEM</div>
                <div class="legend-item"><span>1.00</span><span>99-100 (Excellent)</span></div>
                <div class="legend-item"><span>1.25</span><span>96-98</span></div>
                <div class="legend-item"><span>1.50</span><span>93-95</span></div>
                <div class="legend-item"><span>1.75</span><span>90-92 (Very Satisfactory)</span></div>
                <div class="legend-item"><span>2.00</span><span>87-89</span></div>
                <div class="legend-item"><span>2.25</span><span>84-86 (Satisfactory)</span></div>
                <div class="legend-item"><span>2.50</span><span>81-83</span></div>
                <div class="legend-item"><span>2.75</span><span>78-80 (Fairly Satisfactory)</span></div>
                <div class="legend-item"><span>3.00</span><span>75-77</span></div>
                <div class="legend-item"><span>4.00</span><span>70-74 (Conditional)</span></div>
                <div class="legend-item"><span>5.00</span><span>69 & below (Failed)</span></div>
                <div class="legend-item"><span>INC</span><span>Incomplete</span></div>
                <div class="legend-item"><span>OD</span><span>Officially Dropped</span></div>
                <div class="legend-item"><span>UD</span><span>Unofficially Dropped</span></div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>STUDENT'S NAME</th>
                            <th>MIDTERM</th>
                            <th>FINALS</th>
                            <th>FINAL GRADE</th>
                            <th>ROUNDED</th>
                            <th>EQV</th>
                            <th>REMARKS</th>
                            <th>RANK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $i => $s): 
                            $rankClass = '';
                            if ($s['rank'] == 1) $rankClass = 'rank-1';
                            elseif ($s['rank'] == 2) $rankClass = 'rank-2';
                            elseif ($s['rank'] == 3) $rankClass = 'rank-3';
                        ?>
                        <tr class="<?php echo $rankClass; ?>">
                            <td><?php echo $i + 1; ?></td>
                            <td class="student-name"><?php echo htmlspecialchars($s['last_name'] . ', ' . $s['first_name']); ?></td>
                            <td><?php echo number_format($s['midterm'], 2); ?></td>
                            <td><?php echo number_format($s['finals'], 2); ?></td>
                            <td><strong><?php echo $s['final_grade']; ?></strong></td>
                            <td><?php echo $s['rounded']; ?></td>
                            <td><strong><?php echo $s['eqv']; ?></strong></td>
                            <td class="<?php echo $s['remarks'] == 'PASSED' ? 'passed' : ($s['remarks'] == 'CONDITIONAL' ? 'conditional' : 'failed'); ?>">
                                <strong><?php echo $s['remarks']; ?></strong>
                            </td>
                            <td><span class="rank-badge"><?php echo $s['rank']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</body>
</html>
