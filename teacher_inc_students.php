<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
$teacher_id = $_SESSION['id_number'];

$inc_students = [];
$result = $conn->query("SELECT DISTINCT u.id_number, u.first_name, u.last_name, u.email, ss.subject_code, ss.subject_title, ss.section, ss.year_level, ss.semester, ss.school_year, g.grade as exam_grade
                        FROM users u 
                        INNER JOIN student_subjects ss ON u.id_number = ss.student_id 
                        INNER JOIN grades g ON u.id_number = g.student_id AND ss.subject_code = g.subject_code
                        WHERE ss.teacher_id = '$teacher_id' 
                        AND g.teacher_id = '$teacher_id'
                        AND g.column_name = 'finals_Exam' 
                        AND g.grade = 0
                        AND (ss.archived IS NULL OR ss.archived = 0)
                        ORDER BY ss.school_year DESC, ss.subject_code, u.last_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inc_students[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>INC Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .header { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header h1 { color: #2d3748; font-size: 2rem; }
        .alert { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #92400e; }
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f59e0b; color: white; padding: 1rem; text-align: left; font-weight: 600; }
        td { padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        tr:hover { background: #fef3c7; }
        .btn-back { padding: 0.75rem 1.5rem; background: #667eea; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; }
        .inc-badge { background: #f59e0b; color: white; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; }
    </style>
</head>
<body>
    <?php include 'teacher_navbar.php'; ?>
    
    <div class="main-container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fas fa-exclamation-triangle"></i> Students with INC Status (<?php echo count($inc_students); ?>)</h1>
                <div style="display: flex; gap: 1rem;">
                    <a href="teacher_set_exam_schedule.php" class="btn-back" style="background: #10b981;"><i class="fas fa-calendar-alt"></i> Set Exam Schedule</a>
                    <a href="teacher_students.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Active</a>
                </div>
            </div>
        </div>
        
        <div class="alert">
            <i class="fas fa-info-circle"></i> <strong>Note:</strong> These students have INCOMPLETE (INC) status due to missing Finals Exam. They cannot be archived until they complete their requirements.
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th>School Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inc_students)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #718096; padding: 2rem;">No students with INC status</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($inc_students as $s): ?>
                        <tr>
                            <td><?php echo $s['id_number']; ?></td>
                            <td><?php echo $s['last_name'] . ', ' . $s['first_name']; ?></td>
                            <td><?php echo $s['email']; ?></td>
                            <td><?php echo $s['subject_code'] . ' - ' . $s['subject_title']; ?></td>
                            <td><?php echo $s['section']; ?></td>
                            <td><?php echo $s['year_level']; ?></td>
                            <td><?php echo $s['semester']; ?></td>
                            <td><?php echo $s['school_year']; ?></td>
                            <td><span class="inc-badge">INC</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
