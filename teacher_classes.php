<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
$teacher_id = $_SESSION['id_number'];

$classes = [];
$result = $conn->query("SELECT DISTINCT ss.subject_code, ss.subject_title, ss.section, ss.semester, ss.school_year, ss.year_level, COUNT(DISTINCT ss.student_id) as student_count
                        FROM student_subjects ss
                        WHERE ss.teacher_id = '$teacher_id' AND (ss.archived IS NULL OR ss.archived = 0)
                        GROUP BY ss.subject_code, ss.section, ss.semester, ss.school_year
                        ORDER BY ss.school_year DESC, ss.semester, ss.subject_code");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Classes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; }
        .header { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header h1 { color: #2d3748; font-size: 2rem; }
        .classes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        .class-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 4px solid #667eea; transition: all 0.2s; }
        .class-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .class-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .class-code { font-size: 1.25rem; font-weight: 700; color: #667eea; }
        .class-badge { background: #e0e7ff; color: #4338ca; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; }
        .class-title { color: #2d3748; font-size: 1rem; margin-bottom: 1rem; font-weight: 500; }
        .class-info { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; }
        .info-item { display: flex; align-items: center; gap: 0.5rem; color: #718096; font-size: 0.9rem; }
        .info-item i { color: #667eea; width: 20px; }
        .class-actions { display: flex; gap: 0.5rem; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-secondary { background: #f7fafc; color: #4a5568; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #edf2f7; }
    </style>
</head>
<body>
    <?php include 'teacher_navbar.php'; ?>
    <div class="main-container" id="mainContainer">
        <div class="header">
            <h1><i class="fas fa-book"></i> My Classes (<?php echo count($classes); ?>)</h1>
        </div>
        
        <div class="classes-grid">
            <?php foreach ($classes as $class): ?>
            <div class="class-card">
                <div class="class-header">
                    <div class="class-code"><?php echo $class['subject_code']; ?></div>
                    <div class="class-badge"><?php echo $class['section']; ?></div>
                </div>
                <div class="class-title"><?php echo $class['subject_title']; ?></div>
                <div class="class-info">
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $class['student_count']; ?> Students</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo $class['semester']; ?> Semester</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span><?php echo $class['year_level']; ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span>S.Y. <?php echo $class['school_year']; ?></span>
                    </div>
                </div>
                <div class="class-actions">
                    <a href="teacher_grades.php?subject=<?php echo urlencode($class['subject_code']); ?>&section=<?php echo urlencode($class['section']); ?>&semester=<?php echo urlencode($class['semester']); ?>&school_year=<?php echo urlencode($class['school_year']); ?>" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> View Grades
                    </a>
                    <a href="teacher_upload_students.php" class="btn btn-secondary">
                        <i class="fas fa-upload"></i> Upload
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
