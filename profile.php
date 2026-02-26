<?php
session_start();
if (!isset($_SESSION["user_id"])) {
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

$user_id = $_SESSION["user_id"];
$first_name = $_SESSION["first_name"];
$last_name = $_SESSION["last_name"] ?? '';
$email = '';
$contact_number = '';
$id_number = '';
$student_id_img = '';
$cor_file = '';
$profile_picture = '';
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Add profile_picture column if not exists
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS year_level VARCHAR(20) DEFAULT '1st Year'");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT '1st'");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS program VARCHAR(100) DEFAULT 'B. S. Information Technology'");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS school_year VARCHAR(20) DEFAULT '2024 - 2025'");

$stmt = $conn->prepare("SELECT first_name, last_name, email, contact_number, id_number, profile_picture FROM users WHERE id=?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $last_name, $email, $contact_number, $id_number, $profile_picture);
    $stmt->fetch();
    $stmt->close();
}

// Get additional student info
$student_id_number = $_SESSION['id_number'] ?? '';
$course = 'N/A';
$year_level = 'N/A';
$semester = 'N/A';
$school_year = 'N/A';
$status = 'Active';

// Get program, year_level, semester, school_year from student_subjects table (most recent)
if ($student_id_number) {
    $result = $conn->query("SELECT program, year_level, semester, school_year FROM student_subjects WHERE student_id='$student_id_number' ORDER BY id DESC LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $course = $row['program'] ?? 'N/A';
        $year_level = $row['year_level'] ?? 'N/A';
        $semester = $row['semester'] ?? 'N/A';
        $school_year = $row['school_year'] ?? 'N/A';
    }
}

// Get selected semester and school year for stats (default to current)
$selected_semester = $_GET['stats_semester'] ?? '1st';
$selected_school_year = $_GET['stats_year'] ?? '2025 - 2026';

// Get units enrolled from student_subjects table
$units_enrolled = 0;
$units_completed = 0;
$current_gpa = 0;
if ($student_id_number && $selected_semester != 'N/A' && $selected_school_year != 'N/A') {
    // Count enrolled subjects for selected semester and school year using units from subjects table
    $result = $conn->query("SELECT COALESCE(SUM(s.units), COUNT(ss.subject_code) * 3) as total_units 
                            FROM student_subjects ss 
                            LEFT JOIN subjects s ON ss.subject_code = s.subject_code 
                            WHERE ss.student_id='$student_id_number' AND ss.semester='$selected_semester' AND ss.school_year='$selected_school_year'");
    if ($result && $row = $result->fetch_assoc()) {
        $units_enrolled = $row['total_units'] ?? 0;
    }
    
    // Get completed units and calculate GPA based on passed subjects for selected semester only
    $result = $conn->query("SELECT g.subject_code, g.teacher_id, g.grade, g.equivalent, COALESCE(s.units, 3) as units
                            FROM grades g 
                            LEFT JOIN subjects s ON g.subject_code = s.subject_code
                            INNER JOIN student_subjects ss ON g.student_id = ss.student_id AND g.subject_code = ss.subject_code AND g.teacher_id = ss.teacher_id
                            WHERE g.student_id='$student_id_number' 
                            AND g.column_name='finals_total' 
                            AND g.grade >= 75
                            AND ss.semester='$selected_semester'
                            AND ss.school_year='$selected_school_year'");
    if ($result) {
        $total_equivalent = 0;
        $grade_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $finals = floatval($row['grade']);
            $equivalent = floatval($row['equivalent'] ?? 0);
            $subject_units = intval($row['units']);
            
            // If no equivalent stored, calculate it
            if ($equivalent == 0) {
                if ($finals >= 99) $equivalent = 1.00;
                elseif ($finals >= 96) $equivalent = 1.25;
                elseif ($finals >= 93) $equivalent = 1.50;
                elseif ($finals >= 90) $equivalent = 1.75;
                elseif ($finals >= 87) $equivalent = 2.00;
                elseif ($finals >= 84) $equivalent = 2.25;
                elseif ($finals >= 81) $equivalent = 2.50;
                elseif ($finals >= 78) $equivalent = 2.75;
                elseif ($finals >= 75) $equivalent = 3.00;
                else $equivalent = 3.00;
            }
            
            $total_equivalent += $equivalent;
            $grade_count++;
            $units_completed += $subject_units;
        }
        
        if ($grade_count > 0) {
            $current_gpa = $total_equivalent / $grade_count;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["logout"])) {
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : $email;
    $contact_number = isset($_POST["contact_number"]) ? trim($_POST["contact_number"]) : $contact_number;
    $password = isset($_POST["password"]) ? $_POST["password"] : '';
    $confirm_password = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : '';

    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) { 
        mkdir($upload_dir, 0777, true);
        chmod($upload_dir, 0777);
    }
    
    $new_student_id_img = $student_id_img;
    $new_cor_file = $cor_file;
    $new_profile_picture = $profile_picture;
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $allowed_img = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (in_array($_FILES['profile_picture']['type'], $allowed_img)) {
            $new_profile_picture = $upload_dir . 'profile_' . time() . '_' . basename($_FILES['profile_picture']['name']);
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $new_profile_picture)) {
                $stmt = $conn->prepare("UPDATE users SET profile_picture=? WHERE id=?");
                if ($stmt) {
                    $stmt->bind_param("si", $new_profile_picture, $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                header("Location: profile.php");
                exit;
            } else {
                $error = 'Failed to upload profile picture.';
            }
        } else {
            $error = 'Profile picture must be an image file.';
        }
    }
    
    if (!$error) {
        // Update other fields
        if ($email && $contact_number) {
            $year_level = $_POST['year_level'] ?? $year_level;
            $semester = $_POST['semester'] ?? $semester;
            $status = isset($_POST['status']) ? 'Active' : 'Inactive';
            
            if (!preg_match('/^\d{11}$/', $contact_number)) {
                $error = "Contact number must be exactly 11 digits.";
            } elseif (!empty($password) && $password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
                // Update student_subjects table with academic info
                if ($student_id_number) {
                    $conn->query("UPDATE student_subjects SET program='$new_program', year_level='$new_year_level', semester='$new_semester', school_year='$new_school_year' WHERE student_id='$student_id_number'");
                }
                
                if (!empty($password)) {
                    $stmt = $conn->prepare("UPDATE users SET email=?, contact_number=?, password=? WHERE id=?");
                    if ($stmt) {
                        $stmt->bind_param("sssi", $email, $contact_number, $password, $user_id);
                    }
                } else {
                    $stmt = $conn->prepare("UPDATE users SET email=?, contact_number=? WHERE id=?");
                    if ($stmt) {
                        $stmt->bind_param("ssi", $email, $contact_number, $user_id);
                    }
                }
                if ($stmt && $stmt->execute()) {
                    $success = "Profile updated successfully!";
                    $_SESSION["email"] = $email;
                    $stmt->close();
                    
                    // Check if student became 4th year 1st semester - trigger OJT eligibility check
                    if ($student_id_number) {
                        include 'check_ojt_eligibility.php';
                    }
                    
                    header("Location: profile.php");
                    exit;
                } else {
                    $error = "Failed to update profile: " . ($stmt ? $stmt->error : $conn->error);
                }
                if ($stmt) $stmt->close();
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #333;
            overflow: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        body.dark-mode {
            background: #1a202c;
            color: #e2e8f0;
        }

        body.dark-mode .profile-sidebar,
        body.dark-mode .profile-main,
        body.dark-mode .footer {
            background: #2d3748;
            border-color: #4a5568;
        }

        body.dark-mode .avatar-name,
        body.dark-mode .info-value,
        body.dark-mode .profile-header h1,
        body.dark-mode .form-group label {
            color: #e2e8f0;
        }

        body.dark-mode .avatar-id,
        body.dark-mode .info-label,
        body.dark-mode .profile-header p {
            color: #cbd5e0;
        }

        body.dark-mode .info-item {
            border-bottom-color: #4a5568;
        }

        body.dark-mode .info-item:hover {
            background: #374151;
        }

        body.dark-mode .profile-header {
            border-bottom-color: #4a5568;
        }

        body.dark-mode .tabs {
            border-bottom-color: #4a5568;
        }

        body.dark-mode .tab {
            color: #cbd5e0;
        }

        body.dark-mode .tab.active {
            color: #667eea;
        }

        body.dark-mode .tab:hover {
            background: #374151;
        }

        body.dark-mode .form-group input {
            background: #374151;
            border-color: #4a5568;
            color: #e2e8f0;
        }

        body.dark-mode .form-group input:hover {
            background: #4a5568;
        }

        body.dark-mode .file-upload-box {
            background: #374151;
            border-color: #4a5568;
        }

        body.dark-mode .file-upload-box:hover {
            background: #4a5568;
        }

        body.dark-mode .file-preview {
            background: #374151;
            border-color: #4a5568;
        }

        body.dark-mode .btn-secondary {
            background: #4a5568;
            color: #e2e8f0;
        }

        body.dark-mode .btn-secondary:hover {
            background: #374151;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .main-container {
            margin-left: 260px;
            margin-top: 65px;
            padding: 2rem 2.5rem;
            height: calc(100vh - 65px);
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .main-container.collapsed {
            margin-left: 70px;
        }

        .profile-wrapper {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            animation: fadeIn 0.5s ease;
        }

        .profile-sidebar {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: fit-content;
            animation: slideInLeft 0.6s ease;
            transition: all 0.3s ease;
        }

        .profile-sidebar:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            transform: translateY(-5px);
        }

        .avatar-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .avatar-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: white;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            transition: all 0.4s ease;
            position: relative;
            cursor: pointer;
            overflow: hidden;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-circle:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.5);
        }

        .avatar-upload-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.5rem;
            font-size: 0.7rem;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .avatar-circle:hover .avatar-upload-overlay {
            opacity: 1;
        }

        #profile_picture_input {
            display: none;
        }

        .avatar-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .avatar-id {
            font-size: 0.9rem;
            color: #718096;
        }

        .info-item {
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            padding-left: 0.5rem;
            background: #f9fafb;
            border-radius: 8px;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 0.8rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 0.95rem;
            color: #2d3748;
            font-weight: 500;
        }

        .profile-main {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            animation: slideInRight 0.6s ease;
        }

        .profile-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .profile-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .profile-header h1 i {
            color: #667eea;
            margin-right: 0.5rem;
        }

        .profile-header p {
            font-size: 0.95rem;
            color: #718096;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: fadeIn 0.5s ease;
        }

        .alert i {
            animation: pulse 2s ease infinite;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            color: #718096;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab:hover {
            color: #667eea;
            background: #f0f4ff;
            transform: translateY(-2px);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #f9fafb;
            transition: all 0.3s ease;
        }

        .form-group input:hover {
            border-color: #cbd5e0;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .file-upload-box {
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            background: #f9fafb;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-box:hover {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .file-upload-box:hover i {
            animation: pulse 1s ease infinite;
        }

        .file-upload-box i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .file-upload-box input {
            display: none;
        }

        .file-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            animation: fadeIn 0.4s ease;
        }

        .file-preview:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }

        .file-preview img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .file-preview:hover img {
            transform: scale(1.1);
        }

        .file-preview a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .file-preview a:hover {
            color: #5568d3;
            text-decoration: underline;
        }

        .btn-row {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .footer {
            text-align: center;
            padding: 1rem;
            color: #718096;
            font-size: 0.75rem;
            background: white;
            margin-left: 260px;
            transition: all 0.3s ease;
            border-top: 1px solid #e8ecf4;
        }

        .footer.collapsed {
            margin-left: 70px;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
                margin-top: 55px;
            }

            .footer {
                margin-left: 0;
            }

            .profile-wrapper {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="profile-wrapper">
            <aside class="profile-sidebar">
                <div class="avatar-section">
                    <label for="profile_picture_input" class="avatar-circle">
                        <?php if (!empty($profile_picture)): ?>
                            <img src="<?php echo htmlspecialchars($profile_picture) . '?t=' . time(); ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); ?>
                        <?php endif; ?>
                        <div class="avatar-upload-overlay">
                            <i class="fas fa-camera"></i> Change Photo
                        </div>
                    </label>
                    <input type="file" id="profile_picture_input" name="profile_picture" accept="image/*" onchange="uploadProfilePicture(this)">
                    <div class="avatar-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></div>
                    <div class="avatar-id"><?php echo htmlspecialchars($id_number); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Program</div>
                    <div class="info-value"><?php echo htmlspecialchars($course ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Year Level</div>
                    <div class="info-value"><?php echo htmlspecialchars($year_level); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Semester</div>
                    <div class="info-value"><?php echo htmlspecialchars($semester); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">School Year</div>
                    <div class="info-value"><?php echo htmlspecialchars($school_year); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="status" value="Active" form="profileForm" <?php echo ($status ?? 'Active') == 'Active' ? 'checked' : ''; ?> style="width: 40px; height: 20px; cursor: pointer;">
                            <span style="font-weight: 600; color: <?php echo ($status ?? 'Active') == 'Active' ? '#10b981' : '#ef4444'; ?>;" id="statusText">
                                <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 0.25rem;"></i> <?php echo ($status ?? 'Active') == 'Active' ? 'Active' : 'Inactive'; ?>
                            </span>
                        </label>
                    </div>
                </div>
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                    <div style="text-align: center; margin-bottom: 1rem;">
                        <div style="font-size: 0.8rem; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">Profile Completion</div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: #667eea;">85%</div>
                    </div>
                    <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                        <div style="width: 85%; height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); transition: width 0.5s ease;"></div>
                    </div>
                </div>
                <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center;">
                    <?php
                    // Get student's rank in section for selected semester
                    $student_rank = 'N/A';
                    $rank_icon = 'fas fa-user';
                    $rank_color = '#718096';
                    $student_subject_ranks = [];
                    
                    if ($student_id_number && $selected_semester != 'N/A' && $selected_school_year != 'N/A') {
                        // Get all students in same program/section with their GPAs
                        $conn3 = new mysqli($servername, $dbusername, $dbpassword, $dbname);
                        $rankings = [];
                        
                        // Get students from same program and calculate their GPAs
                        $students_result = $conn3->query("SELECT DISTINCT ss.student_id, ss.program, ss.section 
                                                         FROM student_subjects ss 
                                                         WHERE ss.semester='$selected_semester' 
                                                         AND ss.school_year='$selected_school_year'
                                                         AND ss.program='$course'
                                                         ORDER BY ss.student_id");
                        
                        if ($students_result) {
                            while ($student = $students_result->fetch_assoc()) {
                                $sid = $student['student_id'];
                                $section = $student['section'];
                                
                                // Calculate GPA for this student
                                $gpa_result = $conn3->query("SELECT g.subject_code, g.grade, g.equivalent
                                                            FROM grades g 
                                                            INNER JOIN student_subjects ss ON g.student_id = ss.student_id AND g.subject_code = ss.subject_code AND g.teacher_id = ss.teacher_id
                                                            WHERE g.student_id='$sid' 
                                                            AND g.column_name='finals_total' 
                                                            AND g.grade >= 75
                                                            AND ss.semester='$selected_semester'
                                                            AND ss.school_year='$selected_school_year'");
                                
                                $total_equiv = 0;
                                $count = 0;
                                
                                if ($gpa_result) {
                                    while ($grade = $gpa_result->fetch_assoc()) {
                                        $finals = floatval($grade['grade']);
                                        $equiv = floatval($grade['equivalent'] ?? 0);
                                        
                                        if ($equiv == 0) {
                                            if ($finals >= 99) $equiv = 1.00;
                                            elseif ($finals >= 96) $equiv = 1.25;
                                            elseif ($finals >= 93) $equiv = 1.50;
                                            elseif ($finals >= 90) $equiv = 1.75;
                                            elseif ($finals >= 87) $equiv = 2.00;
                                            elseif ($finals >= 84) $equiv = 2.25;
                                            elseif ($finals >= 81) $equiv = 2.50;
                                            elseif ($finals >= 78) $equiv = 2.75;
                                            elseif ($finals >= 75) $equiv = 3.00;
                                        }
                                        
                                        $total_equiv += $equiv;
                                        $count++;
                                    }
                                }
                                
                                if ($count > 0) {
                                    $gpa = $total_equiv / $count;
                                    $rankings[] = ['student_id' => $sid, 'gpa' => $gpa, 'section' => $section];
                                }
                            }
                        }
                        
                        // Sort by GPA (lower is better)
                        usort($rankings, function($a, $b) {
                            return $a['gpa'] <=> $b['gpa'];
                        });
                        
                        // Find current student's rank
                        foreach ($rankings as $index => $ranking) {
                            if ($ranking['student_id'] == $student_id_number) {
                                $rank = $index + 1;
                                $student_rank = $rank;
                                
                                if ($rank == 1) {
                                    $rank_icon = 'fas fa-crown';
                                    $rank_color = '#f59e0b';
                                    $student_rank = '🥇 Rank #1';
                                } elseif ($rank == 2) {
                                    $rank_icon = 'fas fa-medal';
                                    $rank_color = '#94a3b8';
                                    $student_rank = '🥈 Rank #2';
                                } elseif ($rank == 3) {
                                    $rank_icon = 'fas fa-award';
                                    $rank_color = '#cd7c2f';
                                    $student_rank = '🥉 Rank #3';
                                } else {
                                    $rank_icon = 'fas fa-hashtag';
                                    $rank_color = '#667eea';
                                    $student_rank = 'Rank #' . $rank;
                                }
                                break;
                            }
                        }
                        
                        // Get subjects for current student and calculate rankings
                        $subjects_result = $conn3->query("SELECT DISTINCT ss.subject_code 
                                                         FROM student_subjects ss 
                                                         WHERE ss.student_id='$student_id_number' 
                                                         AND ss.semester='$selected_semester' 
                                                         AND ss.school_year='$selected_school_year'");
                        
                        if ($subjects_result) {
                            while ($subject = $subjects_result->fetch_assoc()) {
                                $subject_code = $subject['subject_code'];
                                
                                // Get all students' grades for this subject
                                $grades_result = $conn3->query("SELECT g.student_id, g.grade 
                                                               FROM grades g 
                                                               INNER JOIN student_subjects ss ON g.student_id = ss.student_id AND g.subject_code = ss.subject_code
                                                               WHERE g.subject_code='$subject_code' 
                                                               AND g.column_name='finals_total' 
                                                               AND ss.semester='$selected_semester'
                                                               AND ss.school_year='$selected_school_year'
                                                               ORDER BY g.grade DESC");
                                
                                $rank = 1;
                                if ($grades_result) {
                                    while ($grade_row = $grades_result->fetch_assoc()) {
                                        if ($grade_row['student_id'] == $student_id_number && $rank <= 3) {
                                            $student_subject_ranks[] = [
                                                'subject' => $subject_code,
                                                'rank' => $rank,
                                                'grade' => $grade_row['grade']
                                            ];
                                            break;
                                        }
                                        $rank++;
                                    }
                                }
                            }
                        }
                        
                        $conn3->close();
                    }
                    

                    ?>
                    <?php foreach ($student_subject_ranks as $subject_rank): ?>
                        <?php 
                        $rank_emoji = $subject_rank['rank'] == 1 ? '🥇' : ($subject_rank['rank'] == 2 ? '🥈' : '🥉');
                        $rank_bg = $subject_rank['rank'] == 1 ? '#fef3c7' : ($subject_rank['rank'] == 2 ? '#f1f5f9' : '#fef3c7');
                        $rank_text = $subject_rank['rank'] == 1 ? '#d97706' : ($subject_rank['rank'] == 2 ? '#64748b' : '#d97706');
                        ?>
                        <span style="background: <?php echo $rank_bg; ?>; color: <?php echo $rank_text; ?>; padding: 0.4rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem; display: inline-block; width: 100%;" title="Grade: <?php echo $subject_rank['grade']; ?>">
                            <?php echo $rank_emoji; ?> <?php echo $subject_rank['subject']; ?> - Rank #<?php echo $subject_rank['rank']; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </aside>

            <div class="profile-main">
                <div class="profile-header">
                    <h1><i class="fas fa-user-cog"></i>Account Settings</h1>
                    <p>Manage your profile information and security settings</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <div class="tabs">
                    <button class="tab active" onclick="switchTab('personal')">
                        <i class="fas fa-user"></i> Personal Info
                    </button>
                    <button class="tab" onclick="switchTab('documents')">
                        <i class="fas fa-file-alt"></i> Documents
                    </button>
                    <button class="tab" onclick="switchTab('security')">
                        <i class="fas fa-lock"></i> Security
                    </button>
                </div>

                <form method="post" enctype="multipart/form-data" id="profileForm">
                    <div id="personal-tab" class="tab-content active">
                        <div style="background: #667eea; padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; box-shadow: 0 2px 8px rgba(102,126,234,0.2);">
                            <i class="fas fa-filter" style="color: white; font-size: 1.1rem;"></i>
                            <label style="font-weight: 600; color: white; margin: 0;">View Stats For:</label>
                            <select onchange="window.location.href='profile.php?stats_semester=' + this.value + '&stats_year=' + document.getElementById('stats_year').value" style="padding: 0.5rem 1rem; border: none; border-radius: 6px; font-weight: 500; background: white; color: #2d3748; cursor: pointer;">
                                <option value="1st" <?php echo $selected_semester == '1st' ? 'selected' : ''; ?>>1st Semester</option>
                                <option value="2nd" <?php echo $selected_semester == '2nd' ? 'selected' : ''; ?>>2nd Semester</option>
                                <option value="Intersem" <?php echo $selected_semester == 'Intersem' ? 'selected' : ''; ?>>Intersem</option>
                            </select>
                            <select id="stats_year" onchange="window.location.href='profile.php?stats_semester=' + document.querySelector('select').value + '&stats_year=' + this.value" style="padding: 0.5rem 1rem; border: none; border-radius: 6px; font-weight: 500; background: white; color: #2d3748; cursor: pointer;">
                                <?php
                                $conn2 = new mysqli($servername, $dbusername, $dbpassword, $dbname);
                                $years_result = $conn2->query("SELECT DISTINCT school_year FROM student_subjects WHERE student_id='$student_id_number' ORDER BY school_year DESC");
                                if ($years_result) {
                                    while ($y = $years_result->fetch_assoc()) {
                                        $selected = $y['school_year'] == $selected_school_year ? 'selected' : '';
                                        echo '<option value="' . $y['school_year'] . '" ' . $selected . '>' . $y['school_year'] . '</option>';
                                    }
                                }
                                $conn2->close();
                                ?>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 12px; color: white; text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 20px rgba(102,126,234,0.3)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                                <i class="fas fa-book" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.9;"></i>
                                <div style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem;"><?php echo $units_enrolled; ?></div>
                                <div style="font-size: 0.85rem; opacity: 0.9;">Units Enrolled</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 1.5rem; border-radius: 12px; color: white; text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 20px rgba(16,185,129,0.3)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.9;"></i>
                                <div style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem;"><?php echo $units_completed; ?></div>
                                <div style="font-size: 0.85rem; opacity: 0.9;">Units Completed</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 1.5rem; border-radius: 12px; color: white; text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 20px rgba(245,158,11,0.3)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                                <i class="fas fa-chart-line" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.9;"></i>
                                <div style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem;"><?php echo $current_gpa > 0 ? number_format($current_gpa, 2) : 'N/A'; ?></div>
                                <div style="font-size: 0.85rem; opacity: 0.9;">Current GPA</div>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Contact Number *</label>
                                <input type="text" name="contact_number" value="<?php echo htmlspecialchars($contact_number); ?>" maxlength="11" required>
                            </div>
                        </div>
                    </div>

                    <div id="documents-tab" class="tab-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Student ID Image</label>
                                <label for="student_id_img" class="file-upload-box">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>Click to upload Student ID</div>
                                    <small style="color:#718096;">JPG, PNG, WEBP</small>
                                    <input type="file" id="student_id_img" name="student_id_img" accept="image/*">
                                </label>
                                <?php if (!empty($student_id_img)): ?>
                                    <div class="file-preview">
                                        <img src="<?php echo htmlspecialchars($student_id_img); ?>" alt="Student ID">
                                        <a href="<?php echo htmlspecialchars($student_id_img); ?>" target="_blank">View Current ID</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Certificate of Registration</label>
                                <label for="cor_file" class="file-upload-box">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>Click to upload COR</div>
                                    <small style="color:#718096;">PDF, JPG, PNG, WEBP</small>
                                    <input type="file" id="cor_file" name="cor_file" accept="application/pdf,image/*">
                                </label>
                                <?php if (!empty($cor_file)): ?>
                                    <div class="file-preview">
                                        <?php $ext = strtolower(pathinfo($cor_file, PATHINFO_EXTENSION)); ?>
                                        <?php if (in_array($ext, ['jpg','jpeg','png','webp'])): ?>
                                            <img src="<?php echo htmlspecialchars($cor_file); ?>" alt="COR">
                                        <?php else: ?>
                                            <i class="fas fa-file-pdf" style="font-size:3rem;color:#667eea;"></i>
                                        <?php endif; ?>
                                        <a href="<?php echo htmlspecialchars($cor_file); ?>" target="_blank">View Current COR</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="security-tab" class="tab-content">
                        <div style="margin-bottom: 2rem; padding: 1.25rem; background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 50px; height: 50px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #065f46; margin-bottom: 0.25rem;">Account Security: Strong</div>
                                    <div style="font-size: 0.85rem; color: #047857;">Your account is well protected</div>
                                </div>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="password" placeholder="Leave blank to keep current">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" placeholder="Confirm new password">
                            </div>
                        </div>
                        <div style="margin-top: 2rem;">
                            <h3 style="font-size: 1rem; font-weight: 600; color: #2d3748; margin-bottom: 1rem;">Recent Activity</h3>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background='#f9fafb'">
                                    <div style="width: 40px; height: 40px; background: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">Login from Windows PC</div>
                                        <div style="font-size: 0.8rem; color: #718096;">Today at 10:30 AM</div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background='#f9fafb'">
                                    <div style="width: 40px; height: 40px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">Profile Updated</div>
                                        <div style="font-size: 0.8rem; color: #718096;">Yesterday at 3:45 PM</div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background='#f9fafb'">
                                    <div style="width: 40px; height: 40px; background: #f59e0b; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-file-upload"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #2d3748; font-size: 0.9rem;">Document Uploaded</div>
                                        <div style="font-size: 0.8rem; color: #718096;">2 days ago</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn btn-secondary" onclick="location.href='index.php'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            event.target.closest('.tab').classList.add('active');
            document.getElementById(tab + '-tab').classList.add('active');
        }

        function uploadProfilePicture(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_picture', input.files[0]);
                
                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
        
        document.querySelector('input[name="status"]').addEventListener('change', function() {
            const statusText = document.getElementById('statusText');
            if (this.checked) {
                statusText.innerHTML = '<i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 0.25rem;"></i> Active';
                statusText.style.color = '#10b981';
            } else {
                statusText.innerHTML = '<i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 0.25rem;"></i> Inactive';
                statusText.style.color = '#ef4444';
            }
        });
    </script>

    <footer class="footer" id="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - Department of Computer Studies</p>
        <p>INTEGRITY • PROFESSIONALISM • INNOVATION</p>
    </footer>

    <?php include 'chatbot.php'; ?>
</body>
</html>
