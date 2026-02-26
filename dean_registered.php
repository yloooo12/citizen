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

$registered = [];
$result = $conn->query("SELECT id_number, first_name, last_name, email, course, section, year_level, semester, created_at FROM users WHERE user_type='student' ORDER BY year_level ASC, semester ASC, section ASC, last_name ASC, first_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $registered[] = $row;
    }
}

$grouped_students = [];
foreach ($registered as $student) {
    $year = $student['year_level'] ?? 'Unknown';
    $sem = $student['semester'] ?? 'Unknown';
    $section = $student['section'] ?? 'Unknown';
    
    if (!isset($grouped_students[$year])) $grouped_students[$year] = [];
    if (!isset($grouped_students[$year][$sem])) $grouped_students[$year][$sem] = [];
    if (!isset($grouped_students[$year][$sem][$section])) $grouped_students[$year][$sem][$section] = [];
    
    $grouped_students[$year][$sem][$section][] = $student;
}

$unregistered = [];
$expired_count = 0;
$result = $conn->query("SELECT id_number, first_name, last_name, email, token, created_at, expires_at FROM student_invitations WHERE used=0 ORDER BY last_name ASC, first_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['is_expired'] = strtotime($row['expires_at']) < time();
        if ($row['is_expired']) $expired_count++;
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
    <title>Registered Students - Dean Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .main-container.collapsed { margin-left: 70px; }
        h1 { color: #2d3748; margin-bottom: 1.5rem; font-size: 1.75rem; }
        .students-list { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .student-item { padding: 1rem; background: #f7fafc; margin-bottom: 0.75rem; border-radius: 8px; border-left: 4px solid #48bb78; }
        .student-item.pending { background: #fffbeb; border-left-color: #f59e0b; }
        .student-item.expired { background: #fee; border-left-color: #ef4444; }
        .student-item strong { color: #2d3748; font-size: 1.05rem; }
        .student-item small { display: block; color: #718096; margin-top: 0.25rem; }
        .student-info { color: #48bb78; font-size: 0.85rem; margin-top: 0.5rem; }
        .student-item.pending .student-info { color: #f59e0b; }
        .student-item.expired .student-info { color: #ef4444; }
        .send-link { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer; margin-top: 0.5rem; margin-right: 0.5rem; }
        .send-link:hover { background: #5568d3; }
        .send-link:disabled { background: #cbd5e0; cursor: not-allowed; }
        .view-link { background: #10b981; }
        .view-link:hover { background: #059669; }
        .section-title { color: #2d3748; font-size: 1.25rem; font-weight: 600; margin: 2rem 0 1rem 0; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }
        .filter-buttons { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        .filter-btn { padding: 0.75rem 1.5rem; border: 2px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .filter-btn:hover { border-color: #667eea; color: #667eea; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
        .student-section { display: none; }
        .student-section.active { display: block; }
    </style>
</head>
<body>
    <?php include 'dean_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <h1><i class="fas fa-users"></i> All Students</h1>
        
        <div style="margin-bottom: 1.5rem;">
            <input type="text" id="searchInput" placeholder="Search by name, ID, or email..." oninput="searchStudents()" style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
        </div>
        
        <div class="filter-buttons">
            <button class="filter-btn active" onclick="showSection('all')">All (<?php echo count($registered) + count($unregistered); ?>)</button>
            <button class="filter-btn" onclick="showSection('registered')">Registered (<?php echo count($registered); ?>)</button>
            <button class="filter-btn" onclick="showSection('pending')">Pending (<?php echo count($unregistered); ?>)</button>
        </div>
        
        <div class="students-list">
            <div class="student-section active" id="section-all">
            <h3 class="section-title"><i class="fas fa-check-circle" style="color: #48bb78;"></i> Registered Students (<?php echo count($registered); ?>)</h3>
            <?php if (!empty($grouped_students)): ?>
                <?php foreach ($grouped_students as $year => $semesters): ?>
                    <?php foreach ($semesters as $sem => $sections): ?>
                        <?php foreach ($sections as $section => $students): ?>
                            <h4 style="color: #667eea; font-size: 1.1rem; margin: 1.5rem 0 0.75rem 0; padding: 0.5rem; background: #edf2f7; border-radius: 6px;">
                                <i class="fas fa-users"></i> <?php echo $year; ?> - <?php echo $sem; ?> Semester - Section <?php echo $section; ?> (<?php echo count($students); ?> students)
                            </h4>
                            <?php foreach ($students as $student): ?>
                                <div class="student-item">
                                    <strong><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></strong> (<?php echo $student['id_number']; ?>)<br>
                                    <small><?php echo $student['email']; ?></small>
                                    <div class="student-info">
                                        <i class="fas fa-check-circle"></i> <?php echo $student['course']; ?> | Registered: <?php echo date('M d, Y', strtotime($student['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #718096; padding: 2rem;">No registered students yet.</p>
            <?php endif; ?>
            
            <h3 class="section-title"><i class="fas fa-clock" style="color: #f59e0b;"></i> Pending Registration (<?php echo count($unregistered); ?>)</h3>
            <?php if (!empty($unregistered)): ?>
                <?php foreach ($unregistered as $student): ?>
                    <div class="student-item <?php echo $student['is_expired'] ? 'expired' : 'pending'; ?>">
                        <strong><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></strong> (<?php echo $student['id_number']; ?>)<br>
                        <small><?php echo $student['email']; ?></small>
                        <div class="student-info">
                            <i class="fas fa-hourglass-half"></i> Invited: <?php echo date('M d, Y', strtotime($student['created_at'])); ?> | Expires: <?php echo date('M d, Y', strtotime($student['expires_at'])); ?>
                        </div>
                        <button class="send-link view-link" onclick="window.open('view_registration_link.php?token=<?php echo $student['token']; ?>', '_blank')">
                            <i class="fas fa-link"></i> View Link
                        </button>
                        <?php if ($student['is_expired']): ?>
                        <button class="send-link" onclick="sendEmail('<?php echo $student['email']; ?>', '<?php echo addslashes($student['first_name'] . ' ' . $student['last_name']); ?>', '<?php echo $student['id_number']; ?>', '<?php echo $student['token']; ?>', this)">
                            <i class="fas fa-paper-plane"></i> Send Email
                        </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #718096; padding: 2rem;">No pending registrations.</p>
            <?php endif; ?>
            </div>
            
            <div class="student-section" id="section-registered">
            <h3 class="section-title"><i class="fas fa-check-circle" style="color: #48bb78;"></i> Registered Students (<?php echo count($registered); ?>)</h3>
            <?php if (!empty($grouped_students)): ?>
                <?php foreach ($grouped_students as $year => $semesters): ?>
                    <?php foreach ($semesters as $sem => $sections): ?>
                        <?php foreach ($sections as $section => $students): ?>
                            <h4 style="color: #667eea; font-size: 1.1rem; margin: 1.5rem 0 0.75rem 0; padding: 0.5rem; background: #edf2f7; border-radius: 6px;">
                                <i class="fas fa-users"></i> <?php echo $year; ?> - <?php echo $sem; ?> Semester - Section <?php echo $section; ?> (<?php echo count($students); ?> students)
                            </h4>
                            <?php foreach ($students as $student): ?>
                                <div class="student-item">
                                    <strong><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></strong> (<?php echo $student['id_number']; ?>)<br>
                                    <small><?php echo $student['email']; ?></small>
                                    <div class="student-info">
                                        <i class="fas fa-check-circle"></i> <?php echo $student['course']; ?> | Registered: <?php echo date('M d, Y', strtotime($student['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #718096; padding: 2rem;">No registered students yet.</p>
            <?php endif; ?>
            </div>
            
            <div class="student-section" id="section-pending">
            <h3 class="section-title"><i class="fas fa-clock" style="color: #f59e0b;"></i> Pending Registration (<?php echo count($unregistered); ?>)</h3>
            <?php if (!empty($unregistered)): ?>
                <?php foreach ($unregistered as $student): ?>
                    <div class="student-item <?php echo $student['is_expired'] ? 'expired' : 'pending'; ?>">
                        <strong><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></strong> (<?php echo $student['id_number']; ?>)<br>
                        <small><?php echo $student['email']; ?></small>
                        <div class="student-info">
                            <i class="fas fa-hourglass-half"></i> Invited: <?php echo date('M d, Y', strtotime($student['created_at'])); ?> | Expires: <?php echo date('M d, Y', strtotime($student['expires_at'])); ?>
                        </div>
                        <button class="send-link view-link" onclick="window.open('view_registration_link.php?token=<?php echo $student['token']; ?>', '_blank')">
                            <i class="fas fa-link"></i> View Link
                        </button>
                        <?php if ($student['is_expired']): ?>
                        <button class="send-link" onclick="sendEmail('<?php echo $student['email']; ?>', '<?php echo addslashes($student['first_name'] . ' ' . $student['last_name']); ?>', '<?php echo $student['id_number']; ?>', '<?php echo $student['token']; ?>', this)">
                            <i class="fas fa-paper-plane"></i> Send Email
                        </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #718096; padding: 2rem;">No pending registrations.</p>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    function showSection(section) {
        document.querySelectorAll('.student-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        
        if (section === 'all') {
            document.getElementById('section-all').classList.add('active');
            document.querySelectorAll('.filter-btn')[0].classList.add('active');
        } else if (section === 'registered') {
            document.getElementById('section-registered').classList.add('active');
            document.querySelectorAll('.filter-btn')[1].classList.add('active');
        } else if (section === 'pending') {
            document.getElementById('section-pending').classList.add('active');
            document.querySelectorAll('.filter-btn')[2].classList.add('active');
        }
    }
    
    function sendEmail(email, name, id, token, btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        fetch('send_registration_email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, name, id, token })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check"></i> Sent!';
                setTimeout(() => location.reload(), 1500);
            } else {
                alert('Failed: ' + data.message);
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Email';
                btn.disabled = false;
            }
        })
        .catch(() => {
            alert('Error sending email');
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Email';
            btn.disabled = false;
        });
    }
    
    function searchStudents() {
        const query = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.student-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(query) ? 'block' : 'none';
        });
    }
    </script>
</body>
</html>
