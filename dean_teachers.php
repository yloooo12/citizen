<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get registered teachers
$teachers = [];
$result = $conn->query("SELECT id_number, first_name, last_name, email, created_at 
                        FROM users 
                        WHERE user_type='teacher' 
                        ORDER BY last_name ASC, first_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Get pending teacher invitations
$pending = [];
$result = $conn->query("SELECT id_number, first_name, last_name, email, expires_at, created_at 
                        FROM teacher_invitations 
                        WHERE used=0 AND expires_at > NOW() 
                        ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers - Dean Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        h1 { color: #2d3748; margin-bottom: 1.5rem; font-size: 1.75rem; }
        .teachers-list { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .teacher-card { padding: 1.5rem; background: #f7fafc; border-radius: 10px; border-left: 4px solid #667eea; margin-bottom: 1rem; }
        .teacher-card:hover { background: #edf2f7; }
        .filter-btn { padding: 0.5rem 1rem; border: 2px solid #667eea; background: white; color: #667eea; border-radius: 6px; cursor: pointer; margin-right: 0.5rem; font-weight: 600; }
        .filter-btn.active { background: #667eea; color: white; }
        .filter-btn:hover { background: #667eea; color: white; }
    </style>
</head>
<body>
    <?php include 'dean_navbar.php'; ?>
    
    <div class="main-container">
        <h1><i class="fas fa-chalkboard-teacher"></i> Teachers</h1>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem;">
            <input type="text" id="searchInput" placeholder="Search by name, ID, or email..." style="flex: 1; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;" onkeyup="searchTeachers()">
            <div>
                <button class="filter-btn active" onclick="showAll()">All</button>
                <button class="filter-btn" onclick="showPending()">Pending (<?php echo count($pending); ?>)</button>
                <button class="filter-btn" onclick="showRegistered()">Registered (<?php echo count($teachers); ?>)</button>
            </div>
        </div>
        
        <?php if (!empty($pending)): ?>
        <div id="pendingSection" style="background: #fff3cd; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #ffc107;">
            <h3 style="color: #856404; margin-bottom: 0.5rem;"><i class="fas fa-clock"></i> Pending Registrations (<?php echo count($pending); ?>)</h3>
            <?php foreach ($pending as $p): ?>
                <div class="searchable-item" data-search="<?php echo strtolower($p['first_name'] . ' ' . $p['last_name'] . ' ' . $p['id_number'] . ' ' . $p['email']); ?>" style="padding: 0.75rem; background: white; border-radius: 6px; margin-top: 0.5rem;">
                    <strong><?php echo $p['first_name'] . ' ' . $p['last_name']; ?></strong> - 
                    <?php echo $p['id_number']; ?> | <?php echo $p['email']; ?> | 
                    <span style="color: #dc3545;">Expires: <?php echo date('M d, Y', strtotime($p['expires_at'])); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div id="registeredSection">
        <h2 style="color: #2d3748; margin-bottom: 1rem; font-size: 1.3rem;"><i class="fas fa-check-circle"></i> Registered Teachers (<?php echo count($teachers); ?>)</h2>
        <div class="teachers-list">
            <?php if (!empty($teachers)): ?>
                <?php foreach ($teachers as $teacher): ?>
                    <div class="teacher-card searchable-item" data-search="<?php echo strtolower($teacher['first_name'] . ' ' . $teacher['last_name'] . ' ' . $teacher['id_number'] . ' ' . $teacher['email']); ?>">
                        <strong style="color: #2d3748; font-size: 1.2rem;"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></strong>
                        <div style="color: #718096; font-size: 0.9rem; margin-top: 0.5rem;">
                            <i class="fas fa-id-card"></i> <?php echo $teacher['id_number']; ?> | 
                            <i class="fas fa-envelope"></i> <?php echo $teacher['email']; ?> | 
                            <i class="fas fa-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($teacher['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #718096; padding: 3rem;">
                    <i class="fas fa-user-slash" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    No teachers registered yet.
                </p>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <script>
        function showAll() {
            document.getElementById('pendingSection').style.display = 'block';
            document.getElementById('registeredSection').style.display = 'block';
            setActive(0);
        }
        function showPending() {
            document.getElementById('pendingSection').style.display = 'block';
            document.getElementById('registeredSection').style.display = 'none';
            setActive(1);
        }
        function showRegistered() {
            document.getElementById('pendingSection').style.display = 'none';
            document.getElementById('registeredSection').style.display = 'block';
            setActive(2);
        }
        function setActive(index) {
            const btns = document.querySelectorAll('.filter-btn');
            btns.forEach((btn, i) => {
                btn.classList.toggle('active', i === index);
            });
        }
        function searchTeachers() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.searchable-item');
            items.forEach(item => {
                const text = item.getAttribute('data-search');
                item.style.display = text.includes(input) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
