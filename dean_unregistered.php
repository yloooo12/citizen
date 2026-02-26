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

// Get unregistered students (invitations not used)
$unregistered = [];
$result = $conn->query("SELECT id_number, first_name, last_name, email, token, created_at, expires_at FROM student_invitations WHERE used=0 ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $unregistered[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unregistered Students - Dean Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .main-container.collapsed { margin-left: 70px; }
        h1 { color: #2d3748; margin-bottom: 1.5rem; font-size: 1.75rem; }
        .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card i { font-size: 2rem; color: #f59e0b; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #2d3748; }
        .stat-label { color: #718096; font-size: 0.9rem; }
        .students-list { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .student-item { padding: 1rem; background: #fffbeb; margin-bottom: 0.75rem; border-radius: 8px; border-left: 4px solid #f59e0b; }
        .student-item strong { color: #2d3748; font-size: 1.05rem; }
        .student-item small { display: block; color: #718096; margin-top: 0.25rem; }
        .student-info { color: #f59e0b; font-size: 0.85rem; margin-top: 0.5rem; }
        .copy-link { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer; margin-top: 0.5rem; }
        .copy-link:hover { background: #5568d3; }
    </style>
</head>
<body>
    <?php include 'dean_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <h1><i class="fas fa-user-clock"></i> Unregistered Students</h1>
        
        <div class="students-list">
            <?php if (!empty($unregistered)): ?>
                <?php foreach ($unregistered as $student): ?>
                    <div class="student-item">
                        <strong><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></strong> (<?php echo $student['id_number']; ?>)<br>
                        <small><?php echo $student['email']; ?></small>
                        <div class="student-info">
                            <i class="fas fa-hourglass-half"></i> Invited: <?php echo date('M d, Y', strtotime($student['created_at'])); ?> | Expires: <?php echo date('M d, Y', strtotime($student['expires_at'])); ?>
                        </div>
                        <button class="copy-link" onclick="copyLink('<?php echo $student['token']; ?>')">
                            <i class="fas fa-copy"></i> Copy Registration Link
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #718096; padding: 2rem;">No pending registrations.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function copyLink(token) {
        const link = 'http://localhost/citizenz/student_register.php?token=' + token;
        navigator.clipboard.writeText(link).then(() => {
            alert('Registration link copied to clipboard!');
        });
    }
    </script>
</body>
</html>
