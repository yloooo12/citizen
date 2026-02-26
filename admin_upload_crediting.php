<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) die("Connection failed");

$message = $_SESSION['upload_message'] ?? '';
$error = $_SESSION['upload_error'] ?? '';
unset($_SESSION['upload_message'], $_SESSION['upload_error']);

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
    require_once 'PHPMailer/src/Exception.php';
    
    $file = $_FILES['csv_file']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
    
    if ($ext == 'csv') {
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);
        $imported = 0;
        $notified = 0;
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            
            $student_name = isset($data[0]) ? $conn->real_escape_string(trim($data[0])) : '';
            $course = isset($data[1]) ? $conn->real_escape_string(trim($data[1])) : '';
            $program_section = isset($data[2]) ? $conn->real_escape_string(trim($data[2])) : '';
            $reason = isset($data[3]) ? $conn->real_escape_string(trim($data[3])) : 'Transferee/Shifter';
            $intervention = isset($data[4]) ? $conn->real_escape_string(trim($data[4])) : 'Submit crediting request';
            
            if (!$student_name || !$course) continue;
            
            // Match student
            if (strpos($student_name, ',') !== false) {
                $name_parts = explode(',', $student_name);
                $last_name = trim($name_parts[0]);
                $first_name = isset($name_parts[1]) ? trim(explode(' ', $name_parts[1])[0]) : '';
            } else {
                $name_parts = explode(' ', $student_name);
                $first_name = trim($name_parts[0]);
                $last_name = isset($name_parts[count($name_parts)-1]) ? trim($name_parts[count($name_parts)-1]) : '';
            }
            
            $user_query = $conn->query("SELECT id, email, first_name, last_name, id_number FROM users WHERE (LOWER(TRIM(first_name)) = LOWER('$first_name') AND LOWER(TRIM(last_name)) = LOWER('$last_name')) OR (LOWER(TRIM(first_name)) = LOWER('$last_name') AND LOWER(TRIM(last_name)) = LOWER('$first_name')) LIMIT 1");
            
            if ($user_query && $user_query->num_rows > 0) {
                $user = $user_query->fetch_assoc();
                $user_id = $user['id'];
                $user_first = $conn->real_escape_string($user['first_name']);
                $user_last = $conn->real_escape_string($user['last_name']);
                $user_id_num = $conn->real_escape_string($user['id_number']);
                
                // Check if eligibility already exists
                $check_eligibility = $conn->query("SELECT id FROM crediting_eligibility WHERE user_id=$user_id AND course='$course' AND is_submitted=0");
                if ($check_eligibility->num_rows == 0) {
                    // Insert into crediting_eligibility table
                    $eligibility_sql = "INSERT INTO crediting_eligibility (user_id, student_name, student_id, course, program_section, reason, intervention, is_submitted, created_at) VALUES ($user_id, '$user_first $user_last', '$user_id_num', '$course', '$program_section', '$reason', '$intervention', 0, NOW())";
                    if ($conn->query($eligibility_sql)) {
                        $imported++;
                        
                        // Also insert into academic_alerts for dashboard display
                        $check_alert = $conn->query("SELECT id FROM academic_alerts WHERE user_id=$user_id AND course='$course' AND alert_type='CREDITING' AND is_resolved=0");
                        if ($check_alert->num_rows == 0) {
                            $conn->query("INSERT INTO academic_alerts (user_id, first_name, last_name, id_number, alert_type, course, grade, program_section, reason, intervention, created_at) VALUES ($user_id, '$user_first', '$user_last', '$user_id_num', 'CREDITING', '$course', 'N/A', '$program_section', '$reason', '$intervention', NOW())");
                        }
                        
                        // Send portal notification
                        $notif_title = "Crediting Eligibility - $course";
                        $notif_msg = "You have subjects eligible for crediting: $course. Please submit a crediting request.";
                        $conn->query("INSERT INTO student_notifications (user_id, first_name, last_name, id_number, notification_type, title, message, course, created_at) VALUES ($user_id, '$user_first', '$user_last', '$user_id_num', 'crediting_alert', '$notif_title', '$notif_msg', '$course', NOW())");
                        
                        // Send email notification
                        try {
                            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'ludoviceylo26@gmail.com';
                            $mail->Password = 'xdnt znus npyg bxuq';
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = 587;
                            $mail->setFrom('ludoviceylo26@gmail.com', 'LSPU CCS');
                            $mail->addAddress($user['email'], $user_first . ' ' . $user_last);
                            $mail->Subject = "Crediting Eligibility - $course";
                            $mail->Body = "Dear $user_first,\n\nYou have subjects eligible for crediting: $course ($program_section).\nReason: $reason\nIntervention: $intervention\n\nPlease log in to the portal and submit a crediting request.\n\nBest regards,\nLSPU Computer Studies";
                            $mail->send();
                            $notified++;
                        } catch (Exception $e) {}
                    }
                }
            }
        }
        fclose($handle);
        
        if ($imported > 0) {
            $_SESSION['upload_message'] = "Successfully imported $imported crediting records!" . ($notified > 0 ? " Sent $notified notifications." : "");
        } else {
            $_SESSION['upload_error'] = "No records imported. Check if students exist in database.";
        }
        header("Location: admin_upload_crediting.php");
        exit;
    } else {
        $_SESSION['upload_error'] = "Please convert your Excel file to CSV format first. You can do this by opening the file in Excel and choosing 'Save As' > 'CSV (Comma delimited)'";
        header("Location: admin_upload_crediting.php");
        exit;
    }
}

// Get statistics
$total_crediting = $conn->query("SELECT COUNT(*) as count FROM academic_alerts WHERE alert_type='CREDITING' AND is_resolved=0")->fetch_assoc()['count'];
$students_need_submit = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM academic_alerts WHERE alert_type='CREDITING' AND is_resolved=0")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Crediting - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; overflow-x: hidden; }
        .header { background: #667eea; padding: 1rem 2rem; z-index: 100; position: fixed; top: 0; left: 0; right: 0; box-shadow: 0 2px 15px rgba(102, 126, 234, 0.25); }
        .nav-container { display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; gap: 1rem; }
        .logo-img { width: 45px; height: 45px; border-radius: 10px; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 2px solid rgba(255,255,255,0.2); }
        .logo-text h1 { font-size: 1.1rem; font-weight: 700; color: white; line-height: 1.2; }
        .logo-text p { font-size: 0.75rem; color: rgba(255,255,255,0.9); }
        .user-section { display: flex; align-items: center; gap: 1rem; }
        .welcome-text { font-weight: 600; color: white; font-size: 0.9rem; }
        .logout-btn { background: rgba(255,255,255,0.15); color: white; border: 1.5px solid rgba(255,255,255,0.3); padding: 0.5rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 0.85rem; text-decoration: none; display: inline-block; }
        .logout-btn:hover { background: rgba(255,255,255,0.25); transform: translateY(-1px); }
        .toggle-btn { background: rgba(255,255,255,0.15); border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.6rem; margin-right: 1rem; border-radius: 8px; }
        .sidebar { position: fixed; left: 0; top: 65px; width: 260px; height: calc(100vh - 65px); background: white; box-shadow: 4px 0 25px rgba(0,0,0,0.06); z-index: 99; overflow-y: auto; transition: all 0.3s ease; border-right: 1px solid #e8ecf4; }
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .sidebar-menu a span { display: none; }
        .sidebar.collapsed .sidebar-menu a { justify-content: center; padding: 1rem; }
        .sidebar.collapsed:hover { width: 260px; }
        .sidebar.collapsed:hover .sidebar-menu a span { display: inline; }
        .sidebar.collapsed:hover .sidebar-menu a { justify-content: flex-start; padding: 1rem 1.5rem; }
        .sidebar-menu { padding: 1.5rem 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; margin: 0.25rem 0.75rem; color: #4a5568; text-decoration: none; transition: all 0.3s ease; font-size: 0.95rem; font-weight: 500; border-radius: 12px; }
        .sidebar-menu a:hover { background: #f0f4ff; color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); }
        .sidebar-menu a.active { background: #667eea; color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); font-weight: 600; }
        .sidebar-menu a i { width: 22px; text-align: center; font-size: 1.1rem; }
        .admin-content { margin-left: 260px; margin-top: 85px; padding: 2rem; transition: margin-left 0.3s ease; }
        .admin-content.collapsed { margin-left: 70px; }
        .content-card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .page-header h2 { font-size: 1.5rem; font-weight: 700; color: #2d3748; margin-bottom: 0.5rem; }
        .page-header p { color: #718096; font-size: 0.95rem; margin-bottom: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #f59e0b; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #2d3748; margin-bottom: 0.5rem; }
        .stat-label { font-size: 0.9rem; color: #718096; }
        .upload-area { border: 2px dashed #f59e0b; border-radius: 12px; padding: 3rem; text-align: center; background: #fffbeb; margin-bottom: 2rem; }
        .upload-area i { font-size: 3rem; color: #f59e0b; margin-bottom: 1rem; }
        .upload-area h3 { font-size: 1.25rem; color: #2d3748; margin-bottom: 0.5rem; }
        .upload-area p { color: #718096; margin-bottom: 1.5rem; }
        .file-input { display: none; }
        .upload-btn { background: #f59e0b; color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.95rem; }
        .upload-btn:hover { background: #d97706; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .template-link { color: #f59e0b; text-decoration: none; font-weight: 600; }
        .template-link:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            * { max-width: 100%; }
            body { overflow-x: hidden; width: 100vw; }
            .header { padding: 0.5rem 0.75rem; }
            .logo-img { width: 30px; height: 30px; }
            .logo-text h1 { font-size: 0.75rem; }
            .logo-text p { display: none; }
            .toggle-btn { margin-right: 0.25rem; padding: 0.4rem; font-size: 1rem; }
            .welcome-text { display: none; }
            .logout-btn { padding: 0.4rem 0.6rem; font-size: 0.75rem; }
            .logout-btn span { display: none; }
            .sidebar { transform: translateX(-100%); width: 250px; top: 55px; height: calc(100vh - 55px); }
            .sidebar.show { transform: translateX(0); }
            .admin-content { padding: 1rem; margin-left: 0; margin-top: 55px; }
            .content-card { padding: 1rem; border-radius: 12px; }
            .page-header h2 { font-size: 1.25rem; }
            .page-header p { font-size: 0.85rem; }
            .stats-grid { grid-template-columns: 1fr; gap: 1rem; }
            .stat-card { padding: 1rem; }
            .stat-value { font-size: 1.5rem; }
            .stat-label { font-size: 0.8rem; }
            .upload-area { padding: 2rem 1rem; }
            .upload-area i { font-size: 2.5rem; }
            .upload-area h3 { font-size: 1.1rem; }
            .upload-area p { font-size: 0.85rem; }
            .upload-btn { padding: 0.65rem 1.5rem; font-size: 0.9rem; }
            .alert { padding: 0.875rem; font-size: 0.85rem; }
        }

        @media (max-width: 480px) {
            .header { padding: 0.4rem 0.5rem; }
            .logo-img { width: 28px; height: 28px; }
            .logo-text h1 { font-size: 0.65rem; }
            .toggle-btn { padding: 0.3rem; font-size: 0.9rem; margin-right: 0.2rem; }
            .admin-content { padding: 0.75rem; margin-top: 55px; }
            .content-card { padding: 0.875rem; }
            .page-header h2 { font-size: 1.1rem; }
            .page-header p { font-size: 0.8rem; }
            .stats-grid { grid-template-columns: 1fr; gap: 0.75rem; }
            .stat-card { padding: 0.875rem; }
            .stat-value { font-size: 1.35rem; }
            .stat-label { font-size: 0.75rem; }
            .upload-area { padding: 1.5rem 0.75rem; }
            .upload-area i { font-size: 2rem; }
            .upload-area h3 { font-size: 1rem; }
            .upload-area p { font-size: 0.8rem; }
            .upload-btn { padding: 0.6rem 1.25rem; font-size: 0.85rem; }
            .alert { padding: 0.75rem; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <?php $page_title = 'Upload Crediting'; include 'admin_header.php'; ?>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="admin_inc.php"><i class="fas fa-file-alt"></i><span>INC Requests</span></a>
            <a href="admin_crediting.php"><i class="fas fa-graduation-cap"></i><span>Crediting Requests</span></a>
            <a href="admin_students.php"><i class="fas fa-users"></i><span>Students</span></a>
            <a href="admin_notification.php"><i class="fas fa-bell"></i><span>Notifications</span></a>
            <a href="post_announcement.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
            <a href="admin_upload_grades.php"><i class="fas fa-upload"></i><span>Upload INC Grades</span></a>
            <a href="admin_upload_crediting.php" class="active"><i class="fas fa-file-upload"></i><span>Upload Crediting</span></a>
        </nav>
    </aside>

    <div class="admin-content" id="mainContainer">
        <div class="content-card">
            <div class="page-header">
                <h2><i class="fas fa-file-upload"></i> Upload Crediting Data</h2>
                <p>Import student crediting eligibility from CSV file (for transferees, shifters, returnees)</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_crediting; ?></div>
                    <div class="stat-label">Total Crediting Alerts</div>
                </div>
                <div class="stat-card" style="border-left-color: #667eea;">
                    <div class="stat-value"><?php echo $students_need_submit; ?></div>
                    <div class="stat-label">Students Need to Submit</div>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="upload-area">
                    <i class="fas fa-file-csv"></i>
                    <h3>Upload CSV File</h3>
                    <p style="font-size:0.8rem; color:#ef4444; margin-top:0.5rem;"><i class="fas fa-info-circle"></i> Para sa Excel files (.xlsx), i-convert muna to CSV: Open sa Excel > Save As > CSV (Comma delimited)</p>
                    <p>File format: STUDENT NAME | COURSE | PROGRAM & SECTION | REASON | INTERVENTION</p>
                    <p style="font-size:0.85rem; color:#f59e0b; margin-bottom:1rem;">
                        <a href="crediting_sample_template.csv" class="template-link" download><i class="fas fa-download"></i> Download Sample Template</a>
                    </p>
                    <input type="file" name="csv_file" id="csvFile" class="file-input" accept=".csv" required onchange="this.form.submit()">
                    <label for="csvFile" class="upload-btn"><i class="fas fa-upload"></i> Choose File</label>
                </div>
            </form>

            <div style="background:#f9fafb; padding:1.5rem; border-radius:8px; border-left:4px solid #f59e0b;">
                <h4 style="margin-bottom:1rem; color:#2d3748;"><i class="fas fa-info-circle"></i> CSV Format:</h4>
                <ul style="list-style:none; padding:0;">
                    <li style="margin-bottom:0.5rem; color:#4a5568;"><i class="fas fa-check" style="color:#f59e0b; margin-right:0.5rem;"></i> Column A: STUDENT NAME (Last Name, First Name)</li>
                    <li style="margin-bottom:0.5rem; color:#4a5568;"><i class="fas fa-check" style="color:#f59e0b; margin-right:0.5rem;"></i> Column B: COURSE/SUBJECT</li>
                    <li style="margin-bottom:0.5rem; color:#4a5568;"><i class="fas fa-check" style="color:#f59e0b; margin-right:0.5rem;"></i> Column C: PROGRAM & SECTION</li>
                    <li style="margin-bottom:0.5rem; color:#4a5568;"><i class="fas fa-check" style="color:#f59e0b; margin-right:0.5rem;"></i> Column D: REASON (e.g., Transferee from XYZ University)</li>
                    <li style="margin-bottom:0.5rem; color:#4a5568;"><i class="fas fa-check" style="color:#f59e0b; margin-right:0.5rem;"></i> Column E: INTERVENTION (e.g., Submit crediting request)</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
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

        const sidebar = document.getElementById('sidebar');
        const mainContainer = document.getElementById('mainContainer');
        if (sidebar && mainContainer) {
            sidebar.addEventListener('mouseenter', function() {
                if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
                    mainContainer.style.marginLeft = '260px';
                }
            });
            sidebar.addEventListener('mouseleave', function() {
                if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
                    mainContainer.style.marginLeft = '70px';
                }
            });
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
<?php include 'admin_logout_modal.php'; ?></body>
</html>
