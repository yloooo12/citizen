<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
$teacher_id = $_SESSION['id_number'];

// Get filter values
$filter_subject = $_GET['subject'] ?? '';
$filter_section = $_GET['section'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_semester = $_GET['semester'] ?? '';
$filter_sy = $_GET['sy'] ?? '';

// Build query
$where = "ss.teacher_id = '$teacher_id' AND ss.archived = 1";
if ($filter_subject) $where .= " AND ss.subject_code = '$filter_subject'";
if ($filter_section) $where .= " AND ss.section = '$filter_section'";
if ($filter_year) $where .= " AND ss.year_level = '$filter_year'";
if ($filter_semester) $where .= " AND ss.semester = '$filter_semester'";
if ($filter_sy) $where .= " AND ss.school_year = '$filter_sy'";

$archived = [];
$result = $conn->query("SELECT DISTINCT u.id_number, u.first_name, u.last_name, ss.subject_code, ss.subject_title, ss.section, ss.year_level, ss.semester, ss.school_year 
                        FROM users u 
                        INNER JOIN student_subjects ss ON u.id_number = ss.student_id 
                        WHERE $where
                        ORDER BY ss.school_year DESC, ss.subject_code, u.last_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $archived[] = $row;
    }
}

// Get filter options
$subjects = [];
$result = $conn->query("SELECT DISTINCT subject_code, subject_title FROM student_subjects WHERE teacher_id='$teacher_id' AND archived=1 ORDER BY subject_code");
if ($result) while ($row = $result->fetch_assoc()) $subjects[] = $row;

$sections = [];
$result = $conn->query("SELECT DISTINCT section FROM student_subjects WHERE teacher_id='$teacher_id' AND archived=1 ORDER BY section");
if ($result) while ($row = $result->fetch_assoc()) $sections[] = $row['section'];

$years = [];
$result = $conn->query("SELECT DISTINCT year_level FROM student_subjects WHERE teacher_id='$teacher_id' AND archived=1 ORDER BY year_level");
if ($result) while ($row = $result->fetch_assoc()) $years[] = $row['year_level'];

$semesters = [];
$result = $conn->query("SELECT DISTINCT semester FROM student_subjects WHERE teacher_id='$teacher_id' AND archived=1 ORDER BY semester");
if ($result) while ($row = $result->fetch_assoc()) $semesters[] = $row['semester'];

$school_years = [];
$result = $conn->query("SELECT DISTINCT school_year FROM student_subjects WHERE teacher_id='$teacher_id' AND archived=1 ORDER BY school_year DESC");
if ($result) while ($row = $result->fetch_assoc()) $school_years[] = $row['school_year'];

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Archived Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .header { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header h1 { color: #2d3748; font-size: 2rem; }
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #718096; color: white; padding: 1rem; text-align: left; font-weight: 600; }
        td { padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        tr:hover { background: #f7fafc; }
        .btn-back { padding: 0.75rem 1.5rem; background: #667eea; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; }
        .filters { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .filters select { padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; margin-right: 0.5rem; }
        .btn-filter { padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-unarchive { padding: 0.75rem 1.5rem; background: #48bb78; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        input[type=checkbox] { width: 18px; height: 18px; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'teacher_navbar.php'; ?>
    
    <div class="main-container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fas fa-archive"></i> Archived Students (<?php echo count($archived); ?>)</h1>
                <div>
                    <?php if (!empty($archived)): ?>
                    <button type="button" class="btn-unarchive" onclick="unarchiveSelected()">
                        <i class="fas fa-undo"></i> Unarchive Selected
                    </button>
                    <?php endif; ?>
                    <a href="teacher_students.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Active</a>
                </div>
            </div>
        </div>
        
        <div class="filters">
            <form method="get" style="display: flex; gap: 0.5rem; align-items: center;">
                <select name="subject">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?php echo $s['subject_code']; ?>" <?php echo $filter_subject == $s['subject_code'] ? 'selected' : ''; ?>>
                        <?php echo $s['subject_code'] . ' - ' . $s['subject_title']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="section">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                    <option value="<?php echo $sec; ?>" <?php echo $filter_section == $sec ? 'selected' : ''; ?>><?php echo $sec; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="semester">
                    <option value="">All Semesters</option>
                    <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem; ?>" <?php echo $filter_semester == $sem ? 'selected' : ''; ?>><?php echo $sem; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="sy">
                    <option value="">All School Years</option>
                    <?php foreach ($school_years as $sy): ?>
                    <option value="<?php echo $sy; ?>" <?php echo $filter_sy == $sy ? 'selected' : ''; ?>><?php echo $sy; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
                <a href="teacher_archived.php" style="padding: 0.5rem 1rem; background: #718096; color: white; border-radius: 6px; text-decoration: none; font-weight: 600;"><i class="fas fa-times"></i> Clear</a>
            </form>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th>School Year</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($archived)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #718096; padding: 2rem;">No archived students</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($archived as $s): ?>
                        <tr>
                            <td><input type="checkbox" class="student-check" data-student="<?php echo $s['id_number']; ?>" data-subject="<?php echo $s['subject_code']; ?>"></td>
                            <td><?php echo $s['id_number']; ?></td>
                            <td><?php echo $s['last_name'] . ', ' . $s['first_name']; ?></td>
                            <td><?php echo $s['subject_code'] . ' - ' . $s['subject_title']; ?></td>
                            <td><?php echo $s['section']; ?></td>
                            <td><?php echo $s['year_level']; ?></td>
                            <td><?php echo $s['semester']; ?></td>
                            <td><?php echo $s['school_year']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <form id="unarchiveForm" method="post" action="unarchive_student.php" style="display: none;">
        <input type="hidden" name="students" id="studentsInput">
    </form>
    
    <script>
    function toggleAll(checkbox) {
        document.querySelectorAll('.student-check').forEach(cb => cb.checked = checkbox.checked);
    }
    
    function unarchiveSelected() {
        const checked = document.querySelectorAll('.student-check:checked');
        if (checked.length === 0) {
            alert('Please select at least one student');
            return;
        }
        
        if (!confirm('Unarchive ' + checked.length + ' selected student(s)?')) return;
        
        const students = [];
        checked.forEach(cb => {
            students.push(cb.dataset.student + '|' + cb.dataset.subject);
        });
        
        document.getElementById('studentsInput').value = students.join(',');
        document.getElementById('unarchiveForm').submit();
    }
    </script>
</body>
</html>
