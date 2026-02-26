<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? '';
$last_name = $_SESSION["last_name"] ?? '';

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$student_id = $_SESSION['id_number'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Get enrolled subjects from student_subjects
$subjects = [];
$student_program = '';
$result = $conn->query("SELECT ss.subject_code, ss.teacher_id, ss.semester, ss.program, ss.school_year, u.first_name, u.last_name, s.subject_name, s.units
                        FROM student_subjects ss
                        INNER JOIN users u ON ss.teacher_id = u.id_number
                        LEFT JOIN subjects s ON ss.subject_code = s.subject_code
                        WHERE ss.student_id='$student_id'
                        ORDER BY ss.subject_code ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
        if (empty($student_program) && !empty($row['program'])) {
            $student_program = $row['program'];
        }
    }
}

// Get grades for this student
$grades = [];
$grades_by_subject = [];
$result = $conn->query("SELECT teacher_id, subject_code, column_name, grade, remarks, equivalent FROM grades WHERE student_id='$student_id'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $key = $row['teacher_id'] . '_' . $row['subject_code'];
        $grades[$key][$row['column_name']] = $row['grade'];
        $grades[$key]['subject_code'] = $row['subject_code'];
        $grades[$key]['teacher_id'] = $row['teacher_id'];
        $grades_by_subject[$row['subject_code']][$row['column_name']] = $row['grade'];
        if ($row['remarks']) $grades[$key]['remarks'] = $row['remarks'];
        if ($row['equivalent']) $grades[$key]['equivalent'] = $row['equivalent'];
    }
}

// Get report grades for INC status
$report_grades = [];
$result = $conn->query("SELECT subject_code, equivalent, remarks FROM report_grades WHERE student_id='$student_id'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $report_grades[$row['subject_code']] = $row;
    }
}

// Get default semester and school year
$default_semester = '1st';
$default_school_year = '';
if (!empty($subjects)) {
    $years = [];
    foreach ($subjects as $s) {
        if (!empty($s['school_year']) && !in_array($s['school_year'], $years)) {
            $years[] = $s['school_year'];
        }
    }
    rsort($years);
    $default_school_year = $years[0] ?? '';
}

$selected_semester = $_GET['semester'] ?? $default_semester;
$selected_school_year = $_GET['school_year'] ?? $default_school_year;



$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - Student Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; transition: background 0.3s ease; color: #e2e8f0; }
        body.light-mode { background: #f5f7fa; color: #2d3748; }
        body.light-mode .header-info { background: white; border-color: #e2e8f0; }
        body.light-mode .header-info h1 { color: #2d3748; }
        body.light-mode .header-actions select { background: #f3f4f6; color: #2d3748; border-color: #e2e8f0; }
        body.light-mode .header-actions select:hover { background: #e5e7eb; }
        body.light-mode .header-actions label { color: #4a5568; }
        body.light-mode .grade-table { background: white; border-color: #e2e8f0; }
        body.light-mode thead tr { background: #f9fafb; }
        body.light-mode th { color: #2d3748; border-bottom-color: #e2e8f0; }
        body.light-mode td { color: #2d3748; border-bottom-color: #e2e8f0; }
        body.light-mode .no-data { color: #718096; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .header-info { background: #1e293b; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.3); display: flex; justify-content: space-between; align-items: center; border: 1px solid #334155; }
        .header-info h1 { font-size: 1.5rem; font-weight: 700; color: #f1f5f9; display: flex; align-items: center; gap: 0.5rem; }
        .header-actions { display: flex; gap: 1rem; align-items: center; }
        .header-actions select { padding: 0.75rem 1.5rem; border-radius: 8px; border: 1px solid #475569; font-weight: 600; background: #334155; color: #f1f5f9; cursor: pointer; }
        .header-actions select:hover { background: #475569; }
        .header-actions button { padding: 0.75rem 1.5rem; border-radius: 8px; border: none; background: #667eea; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; }
        .header-actions button:hover { background: #5568d3; }
        .header-actions label { font-weight: 600; color: #cbd5e1; margin-right: 0.5rem; }
        .filter-section { background: #667eea; padding: 1rem 1.5rem; border-radius: 8px; display: flex; gap: 1rem; align-items: center; }
        .gpa-badge { background: #10b981; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .grade-table { background: #1e293b; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.3); overflow: hidden; border: 1px solid #334155; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #334155; }
        th { padding: 1rem; text-align: left; font-weight: 700; color: #f1f5f9; border-bottom: 2px solid #475569; }
        th:nth-child(4), th:nth-child(5), th:nth-child(6), th:nth-child(7) { text-align: center; }
        td { padding: 1rem; color: #e2e8f0; border-bottom: 1px solid #334155; }
        td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7) { text-align: center; }
        .final-grade { color: #818cf8; font-weight: 700; font-size: 1.1rem; }
        .no-data { text-align: center; padding: 3rem; color: #94a3b8; }
        @media print {
            body { background: white; }
            .main-container { margin-left: 0; margin-top: 0; padding: 1rem; }
            .header-actions { display: none !important; }
            aside, nav, .sidebar, header, .header { display: none !important; }
            .header-info { box-shadow: none; border: 1px solid #e2e8f0; }
            .grade-table { box-shadow: none; border: 1px solid #e2e8f0; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="header-info">
            <h1><i class="fas fa-file-alt"></i> Report Card</h1>
            <div class="header-actions">
                <div class="gpa-badge" id="gpaBadge">
                    <i class="fas fa-chart-line"></i>
                    <span>GPA: <span id="gpaValue">N/A</span></span>
                </div>
                <div class="filter-section">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label style="color: white;">Academic Year:</label>
                        <select id="schoolYearSelect" onchange="applyFilters()">
                            <?php
                            $years = [];
                            foreach ($subjects as $s) {
                                if (!empty($s['school_year']) && !in_array($s['school_year'], $years)) {
                                    $years[] = $s['school_year'];
                                }
                            }
                            rsort($years);
                            foreach ($years as $y) {
                                $sel = ($y == $selected_school_year) ? 'selected' : '';
                                echo '<option value="' . $y . '" ' . $sel . '>' . $y . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label style="color: white;">Semester:</label>
                        <select id="semesterSelect" onchange="applyFilters()">
                            <option value="1st" <?php echo $selected_semester == '1st' ? 'selected' : ''; ?>>First (1st) Semester</option>
                            <option value="2nd" <?php echo $selected_semester == '2nd' ? 'selected' : ''; ?>>Second (2nd) Semester</option>
                        </select>
                    </div>
                </div>
                <button onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        
        <div class="grade-table">
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Descriptive Title</th>
                        <th>Instructor</th>
                        <th>Midterm</th>
                        <th>Finals</th>
                        <th>Remarks</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                                No subjects enrolled yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $subject): ?>
                            <tr data-semester="<?php echo $subject['semester']; ?>" data-schoolyear="<?php echo $subject['school_year'] ?? ''; ?>">
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_name'] ?? $subject['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($subject['last_name'] . ', ' . $subject['first_name']); ?></td>
                                <td class="final-grade">
                                    <?php 
                                    $teacher_id = $subject['teacher_id'];
                                    $subject_code = $subject['subject_code'];
                                    $key = $teacher_id . '_' . $subject_code;
                                    if (isset($grades[$key]['midterm_total'])) {
                                        $midterm_total = floatval($grades[$key]['midterm_total']);
                                        $color = $midterm_total < 75 ? '#ef4444' : '#818cf8';
                                        echo '<span style="color: ' . $color . ';">' . number_format($midterm_total, 2) . '</span>';
                                    } else {
                                        echo '<span style="color: #94a3b8; font-weight: 400;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td class="final-grade">
                                    <?php 
                                    if (isset($grades[$key]['finals_total'])) {
                                        $finals_total = floatval($grades[$key]['finals_total']);
                                        $color = $finals_total < 75 ? '#ef4444' : '#818cf8';
                                        echo '<span style="color: ' . $color . ';">' . number_format($finals_total, 2) . '</span>';
                                    } else {
                                        echo '<span style="color: #94a3b8; font-weight: 400;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $subject_code = $subject['subject_code'];
                                    
                                    // Check for INC: finals exam = 0 or not set
                                    $is_inc = false;
                                    if (isset($grades_by_subject[$subject_code]['finals_total']) && floatval($grades_by_subject[$subject_code]['finals_total']) > 0) {
                                        $finals_exam = isset($grades_by_subject[$subject_code]['finals_Exam']) ? floatval($grades_by_subject[$subject_code]['finals_Exam']) : 0;
                                        if ($finals_exam == 0) {
                                            $is_inc = true;
                                        }
                                    }
                                    
                                    if ($is_inc) {
                                        echo '<span style="color: #f59e0b; font-weight: 600;">INCOMPLETE</span>';
                                        echo '<br><span style="color: #94a3b8; font-size: 0.85rem;">(INC)</span>';
                                    } elseif (isset($grades[$key]['midterm_total']) && isset($grades[$key]['finals_total'])) {
                                        $midterm = floatval($grades[$key]['midterm_total']);
                                        $finals = floatval($grades[$key]['finals_total']);
                                        $final_avg = ($midterm + $finals) / 2;
                                        
                                        // Calculate equivalent and remarks based on average
                                        if ($final_avg >= 99) { $equivalent = '1.00'; $remarks = 'PASSED'; }
                                        elseif ($final_avg >= 96) { $equivalent = '1.25'; $remarks = 'PASSED'; }
                                        elseif ($final_avg >= 93) { $equivalent = '1.50'; $remarks = 'PASSED'; }
                                        elseif ($final_avg >= 90) { $equivalent = '1.75'; $remarks = 'PASSED'; }
                                        elseif ($final_avg >= 87) { $equivalent = '2.00'; $remarks = 'PASSED'; }
                                        elseif ($final_avg >= 84) { $equivalent = '2.25'; $remarks = 'PASSED'; }
                                        elseif ($final_avg >= 81) { $equivalent = '2.50'; $remarks = 'PASSED'; }
                                        elseif ($final_avg >= 78) { $equivalent = '2.75'; $remarks = 'PASSED'; }
                                        elseif ($final_avg >= 75) { $equivalent = '3.00'; $remarks = 'PASSED'; }
                                        elseif ($final_avg >= 70) { $equivalent = '4.00'; $remarks = 'FAILED'; }
                                        else { $equivalent = '5.00'; $remarks = 'FAILED'; }
                                        
                                        $color = $remarks === 'PASSED' ? '#48bb78' : '#ef4444';
                                        echo '<span style="color: ' . $color . '; font-weight: 600;">' . htmlspecialchars($remarks) . '</span>';
                                        echo '<br><span style="color: #94a3b8; font-size: 0.85rem;">(' . htmlspecialchars($equivalent) . ')</span>';
                                    } else {
                                        echo '<span style="color: #94a3b8;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td style="font-weight: 600;"><?php echo $subject['units'] ?? 3; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    // Apply dark mode immediately
    (function() {
        const darkMode = localStorage.getItem('darkMode');
        if (darkMode === 'enabled') {
            // Keep dark mode
            document.body.classList.remove('light-mode');
        } else {
            // Default to light mode
            document.body.classList.add('light-mode');
        }
    })();
    
    function applyFilters() {
        const semester = document.getElementById('semesterSelect').value;
        const schoolYear = document.getElementById('schoolYearSelect').value;
        const rows = document.querySelectorAll('tbody tr[data-semester]');
        
        let gpaTotal = 0;
        let gpaCount = 0;
        let hasIncOrFailed = false;
        
        rows.forEach(row => {
            const semesterMatch = row.dataset.semester === semester;
            const schoolYearMatch = row.dataset.schoolyear === schoolYear;
            
            if (semesterMatch && schoolYearMatch) {
                row.style.display = '';
                
                const remarksCell = row.cells[5].textContent.trim();
                if (remarksCell.includes('INCOMPLETE') || remarksCell.includes('FAILED')) {
                    hasIncOrFailed = true;
                }
                
                // Calculate GPA for visible rows
                const midtermCell = row.cells[3].textContent.trim();
                const finalsCell = row.cells[4].textContent.trim();
                
                if (midtermCell !== '-' && finalsCell !== '-') {
                    const midterm = parseFloat(midtermCell);
                    const finals = parseFloat(finalsCell);
                    const avg = (midterm + finals) / 2;
                    
                    if (avg >= 75) {
                        let equiv = 3.00;
                        if (avg >= 99) equiv = 1.00;
                        else if (avg >= 96) equiv = 1.25;
                        else if (avg >= 93) equiv = 1.50;
                        else if (avg >= 90) equiv = 1.75;
                        else if (avg >= 87) equiv = 2.00;
                        else if (avg >= 84) equiv = 2.25;
                        else if (avg >= 81) equiv = 2.50;
                        else if (avg >= 78) equiv = 2.75;
                        
                        gpaTotal += equiv;
                        gpaCount++;
                    }
                }
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update GPA display
        const gpaValue = document.getElementById('gpaValue');
        const gpaBadge = document.getElementById('gpaBadge');
        if (hasIncOrFailed) {
            gpaValue.textContent = '0.00';
            gpaBadge.style.background = '#ef4444';
        } else if (gpaCount > 0) {
            const gpa = gpaTotal / gpaCount;
            gpaValue.textContent = gpa.toFixed(2);
            gpaBadge.style.background = '#10b981';
        } else {
            gpaValue.textContent = 'N/A';
            gpaBadge.style.background = '#10b981';
        }
    }
    
    // Filter on page load
    window.addEventListener('DOMContentLoaded', applyFilters);
    </script>
</body>
</html>
