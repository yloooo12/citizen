<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// Get teacher's assigned course, section, and subjects
$teacher_id = $_SESSION['id_number'] ?? '';
$teacher_course = $_SESSION['assigned_course'] ?? '';
$teacher_section = $_SESSION['assigned_section'] ?? '';
$teacher_lecture = $_SESSION['assigned_lecture'] ?? '';
$teacher_lab = $_SESSION['assigned_lab'] ?? '';

// Get stats for teacher's assigned students only
$total_students = 0;
$my_classes = 0;
$assignments = 0;
$notifications = 0;

// Total students taught by this teacher
$result = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM student_subjects WHERE teacher_id='$teacher_id'");
if ($result) $total_students = $result->fetch_assoc()['count'];

// My classes (distinct subjects)
$result = $conn->query("SELECT COUNT(DISTINCT subject_code) as count FROM student_subjects WHERE teacher_id='$teacher_id'");
if ($result) $my_classes = $result->fetch_assoc()['count'];

// Assignments (grade columns created by teacher)
$result = $conn->query("SELECT COUNT(DISTINCT column_name) as count FROM grades WHERE teacher_id='$teacher_id'");
if ($result) $assignments = $result->fetch_assoc()['count'];

// Notifications (unread teacher notifications)
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_notifications WHERE user_id='$teacher_id' AND is_read=0");
if ($result) $notifications = $result->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        @media (max-width: 768px) {
            .main-container { margin-left: 0 !important; }
        }
        h1 { color: #2d3748; margin-bottom: 1.5rem; font-size: 1.75rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card i { font-size: 2rem; color: #48bb78; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #2d3748; }
        .stat-label { color: #718096; font-size: 0.9rem; }
        .welcome-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
        .welcome-card h2 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .welcome-card p { opacity: 0.9; }
    </style>
</head>
<body>
    <?php include 'teacher_navbar.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuItems = document.querySelectorAll('.sidebar .menu-item');
        menuItems.forEach(item => {
            if (item.getAttribute('href') === 'teacher_dashboard.php') {
                item.classList.add('active');
            }
        });
    });
    
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContainer = document.getElementById('mainContainer');
        
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            if (mainContainer) mainContainer.classList.toggle('collapsed');
        }
    }
    </script>
    
    <div class="main-container" id="mainContainer">
        <div class="welcome-card">
            <h2>Welcome, <?php echo $_SESSION['first_name']; ?>!</h2>
            <p>Teacher Portal<?php if ($teacher_course): ?> - <?php echo $teacher_course; ?><?php endif; ?><?php if ($teacher_section): ?> Section <?php echo $teacher_section; ?><?php endif; ?></p>
            <?php if ($teacher_lecture): ?><p>Lecture: <?php echo $teacher_lecture; ?><?php if ($teacher_lab && $teacher_lab != 'NA'): ?> | Lab: <?php echo $teacher_lab; ?><?php endif; ?></p><?php endif; ?>
            <!-- Debug: <?php echo json_encode($_SESSION); ?> -->
        </div>
        
        <h1><i class="fas fa-chart-bar"></i> Dashboard Overview</h1>
        
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-book"></i>
                <div class="stat-value"><?php echo $my_classes; ?></div>
                <div class="stat-label">My Classes</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clipboard-list"></i>
                <div class="stat-value"><?php echo $assignments; ?></div>
                <div class="stat-label">Assignments</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-bell"></i>
                <div class="stat-value"><?php echo $notifications; ?></div>
                <div class="stat-label">Notifications</div>
            </div>
        </div>
    </div>
</body>
</html>