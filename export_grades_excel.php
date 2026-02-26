<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    exit;
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$teacher_id = $_SESSION['id_number'] ?? '';
$subject = $_GET['subject'] ?? '';
$period = $_GET['period'] ?? 'midterm';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=grades_{$period}_{$subject}_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Get students
$students = [];
if ($teacher_id && $subject) {
    $result = $conn->query("SELECT u.id_number, u.first_name, u.last_name, ss.section FROM users u INNER JOIN student_subjects ss ON u.id_number = ss.student_id WHERE ss.teacher_id = '$teacher_id' AND ss.subject_code = '$subject' AND (ss.archived IS NULL OR ss.archived = 0) ORDER BY u.last_name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// Get grades
$grades = [];
$result = $conn->query("SELECT student_id, column_name, grade FROM grades WHERE subject_code = '$subject'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $grades[$row['student_id']][$row['column_name']] = $row['grade'];
    }
}

$conn->close();

// Output Excel
echo "<table border='1'>";
echo "<tr>";
echo "<th colspan='30' style='background: #667eea; color: white; font-size: 16px; padding: 10px;'>CLASS RECORD - " . strtoupper($period) . "</th>";
echo "</tr>";
echo "<tr>";
echo "<th colspan='30' style='background: #f0f4ff; padding: 8px;'>Subject: $subject | Teacher ID: $teacher_id</th>";
echo "</tr>";
echo "<tr style='background: #e0e7ff;'>";
echo "<th rowspan='3'>STUDENT NAME</th>";
echo "<th colspan='3'>CLASS ENGAGEMENT</th>";
echo "<th rowspan='3'>20%</th>";
echo "<th colspan='10'>LEARNING OUTPUTS</th>";
echo "<th rowspan='3'>20%</th>";
echo "<th colspan='8'>QUIZZES</th>";
echo "<th rowspan='3'>20%</th>";
echo "<th colspan='2'>EXAM</th>";
echo "<th rowspan='3'>40%</th>";
echo "<th rowspan='3'>TOTAL</th>";
echo "<th rowspan='3'>EQUIVALENT</th>";
echo "</tr>";
echo "<tr style='background: #f1f5f9;'>";
echo "<th>ATT</th><th>BEHAVIOR</th><th>REC/PAR</th>";
echo "<th>ACT 1</th><th>AVE</th><th>ACT 2</th><th>AVE</th><th>ACT 3</th><th>AVE</th><th>ACT 4</th><th>AVE</th><th>ACT 5</th><th>AVE</th>";
echo "<th>Q1</th><th>AVE</th><th>Q2</th><th>AVE</th><th>Q3</th><th>AVE</th><th>Q4</th><th>AVE</th>";
echo "<th>EXAM</th><th>AVE</th>";
echo "</tr>";
echo "<tr style='background: #f8fafc;'>";
echo "<th>100</th><th>100</th><th>100</th>";
echo "<th>100</th><th></th><th>100</th><th></th><th>100</th><th></th><th>100</th><th></th><th>100</th><th></th>";
echo "<th>100</th><th></th><th>100</th><th></th><th>100</th><th></th><th>100</th><th></th>";
echo "<th>100</th><th></th>";
echo "</tr>";

foreach ($students as $student) {
    $sid = $student['id_number'];
    $name = $student['last_name'] . ', ' . $student['first_name'];
    
    $att = $grades[$sid][$period . '_ATT'] ?? '';
    $behavior = $grades[$sid][$period . '_Behavior'] ?? '';
    $recpar = $grades[$sid][$period . '_RecPar'] ?? '';
    $act1 = $grades[$sid][$period . '_Act1'] ?? '';
    $act2 = $grades[$sid][$period . '_Act2'] ?? '';
    $act3 = $grades[$sid][$period . '_Act3'] ?? '';
    $act4 = $grades[$sid][$period . '_Act4'] ?? '';
    $act5 = $grades[$sid][$period . '_Act5'] ?? '';
    $q1 = $grades[$sid][$period . '_Q1'] ?? '';
    $q2 = $grades[$sid][$period . '_Q2'] ?? '';
    $q3 = $grades[$sid][$period . '_Q3'] ?? '';
    $q4 = $grades[$sid][$period . '_Q4'] ?? '';
    $exam = $grades[$sid][$period . '_Exam'] ?? '';
    $total = $grades[$sid][$period . '_total'] ?? '';
    
    // Calculate averages
    $ce20 = ($att && $behavior && $recpar) ? ((($att + $behavior + $recpar) / 3) / 100 * 20) : '';
    $act1Ave = $act1 ? (($act1/100)*50)+50 : '';
    $act2Ave = $act2 ? (($act2/100)*50)+50 : '';
    $act3Ave = $act3 ? (($act3/100)*50)+50 : '';
    $act4Ave = $act4 ? (($act4/100)*50)+50 : '';
    $act5Ave = $act5 ? (($act5/100)*50)+50 : '';
    $act20 = ($act1Ave && $act2Ave && $act3Ave && $act4Ave && $act5Ave) ? (($act1Ave + $act2Ave + $act3Ave + $act4Ave + $act5Ave) / 5 * 0.2) : '';
    $q1Ave = $q1 ? (($q1/100)*50)+50 : '';
    $q2Ave = $q2 ? (($q2/100)*50)+50 : '';
    $q3Ave = $q3 ? (($q3/100)*50)+50 : '';
    $q4Ave = $q4 ? (($q4/100)*50)+50 : '';
    $quiz20 = ($q1Ave && $q2Ave && $q3Ave && $q4Ave) ? (($q1Ave + $q2Ave + $q3Ave + $q4Ave) / 4 / 100 * 20) : '';
    $examAve = $exam ? (($exam/100)*50)+50 : '';
    $exam40 = $examAve ? ($examAve/100*40) : '';
    
    $equivalent = '';
    if ($total) {
        if ($total >= 99) $equivalent = '1.00';
        elseif ($total >= 96) $equivalent = '1.25';
        elseif ($total >= 93) $equivalent = '1.50';
        elseif ($total >= 90) $equivalent = '1.75';
        elseif ($total >= 87) $equivalent = '2.00';
        elseif ($total >= 84) $equivalent = '2.25';
        elseif ($total >= 81) $equivalent = '2.50';
        elseif ($total >= 78) $equivalent = '2.75';
        elseif ($total >= 75) $equivalent = '3.00';
        elseif ($total >= 70) $equivalent = '4.00';
        else $equivalent = '5.00';
    }
    
    echo "<tr>";
    echo "<td>$name ({$student['section']})</td>";
    echo "<td>$att</td><td>$behavior</td><td>$recpar</td>";
    echo "<td>" . ($ce20 ? number_format($ce20, 2) : '') . "</td>";
    echo "<td>$act1</td><td>" . ($act1Ave ? number_format($act1Ave, 2) : '') . "</td>";
    echo "<td>$act2</td><td>" . ($act2Ave ? number_format($act2Ave, 2) : '') . "</td>";
    echo "<td>$act3</td><td>" . ($act3Ave ? number_format($act3Ave, 2) : '') . "</td>";
    echo "<td>$act4</td><td>" . ($act4Ave ? number_format($act4Ave, 2) : '') . "</td>";
    echo "<td>$act5</td><td>" . ($act5Ave ? number_format($act5Ave, 2) : '') . "</td>";
    echo "<td>" . ($act20 ? number_format($act20, 2) : '') . "</td>";
    echo "<td>$q1</td><td>" . ($q1Ave ? number_format($q1Ave, 2) : '') . "</td>";
    echo "<td>$q2</td><td>" . ($q2Ave ? number_format($q2Ave, 2) : '') . "</td>";
    echo "<td>$q3</td><td>" . ($q3Ave ? number_format($q3Ave, 2) : '') . "</td>";
    echo "<td>$q4</td><td>" . ($q4Ave ? number_format($q4Ave, 2) : '') . "</td>";
    echo "<td>" . ($quiz20 ? number_format($quiz20, 2) : '') . "</td>";
    echo "<td>$exam</td><td>" . ($examAve ? number_format($examAve, 2) : '') . "</td>";
    echo "<td>" . ($exam40 ? number_format($exam40, 2) : '') . "</td>";
    echo "<td style='font-weight: bold;'>" . ($total ? number_format($total, 2) : '') . "</td>";
    echo "<td style='font-weight: bold;'>$equivalent</td>";
    echo "</tr>";
}

echo "</table>";
?>
