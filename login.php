<?php
session_start();

// Database connection
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Removed early $pass usage and status check. Status check will be inside the login handler after $pass is set.
// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $id_number = trim($_POST["id_number"]);
    $pass = $_POST["password"];

    if (empty($id_number) || empty($pass)) {
        echo "<script>alert('ID Number and password are required.');</script>";
    } elseif (!preg_match('/^[0-9\-]+$/', $id_number)) {
        echo "<script>alert('ID Number must contain only numbers and dash.');</script>";
    } else {
        // Check if user is admin/teacher/staff - block them from student login
        $check = $conn->query("SELECT is_admin, user_type FROM users WHERE id_number='$id_number' LIMIT 1");
        if ($check && $row = $check->fetch_assoc()) {
            $is_admin = $row['is_admin'] ?? 0;
            $user_type = $row['user_type'] ?? '';
            
            if ($is_admin >= 1 || in_array($user_type, ['teacher', 'program_head', 'secretary', 'dean'])) {
                echo "<script>alert('Access denied. Please use the admin/staff login page.'); window.location.href='login.php';</script>";
                exit;
            }
        }
        
        $stmt = $conn->prepare("SELECT id, password, first_name, last_name, id_number, email, status, user_type, assigned_course, assigned_section, assigned_lecture, assigned_lab FROM users WHERE id_number=? LIMIT 1");
        $stmt->bind_param("s", $id_number);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $plain_pass, $first_name_db, $last_name_db, $id_number_db, $email_db, $status_db, $user_type_db, $assigned_course_db, $assigned_section_db, $assigned_lecture_db, $assigned_lab_db);
            $stmt->fetch();
            if (password_verify($pass, $plain_pass) || $pass === $plain_pass) {
                // Check if user is active
                $check_active = $conn->query("SELECT is_active FROM users WHERE id_number='$id_number_db' LIMIT 1");
                if ($check_active && $active_row = $check_active->fetch_assoc()) {
                    if (!$active_row['is_active']) {
                        echo "<script>alert('Your account has been disabled. Contact administrator.');</script>";
                        $stmt->close();
                        $conn->close();
                        exit;
                    }
                }
                
                if ($status_db !== 'approved') {
                    echo "<script>alert('Your account is not yet approved by admin.');</script>";
                    exit;
                }
                $_SESSION["user_id"] = $id;
                $_SESSION["first_name"] = $first_name_db;
                $_SESSION["last_name"] = $last_name_db;
                $_SESSION["id_number"] = $id_number_db;
                $_SESSION["email"] = $email_db;

                // Check admin level from database
                $admin_check = $conn->query("SELECT is_admin FROM users WHERE id_number='$id_number_db' LIMIT 1");
                $admin_level = 0;
                if ($admin_check && $admin_row = $admin_check->fetch_assoc()) {
                    $admin_level = $admin_row['is_admin'];
                }
                
                if ($admin_level == 4) {
                    $_SESSION["is_admin"] = 4;
                    echo "<script>window.location.href='system_admin_dashboard.php';</script>";
                } elseif ($admin_level == 3) {
                    $_SESSION["is_admin"] = 3;
                    echo "<script>window.location.href='dean_dashboard.php';</script>";
                } elseif ($admin_level == 2) {
                    $_SESSION["is_admin"] = 2;
                    echo "<script>window.location.href='secretary_dashboard.php';</script>";
                } elseif ($admin_level == 1) {
                    $_SESSION["is_admin"] = 1;
                    echo "<script>window.location.href='program_head_dashboard.php';</script>";
                } elseif ($admin_level == 0 && in_array($id_number_db, ['246', '999'])) {
                    $_SESSION["is_admin"] = true;
                    echo "<script>window.location.href='dean_student_upload.php';</script>";
                } elseif (isset($user_type_db) && $user_type_db == 'program_head') {
                    $_SESSION["user_type"] = 'program_head';
                    echo "<script>window.location.href='program_head_dashboard.php';</script>";
                } elseif (isset($user_type_db) && $user_type_db == 'secretary') {
                    $_SESSION["user_type"] = 'secretary';
                    echo "<script>window.location.href='secretary_dashboard.php';</script>";
                } elseif (isset($user_type_db) && $user_type_db == 'teacher') {
                    $_SESSION["is_teacher"] = true;
                    $_SESSION["user_type"] = 'teacher';
                    $_SESSION["assigned_course"] = $assigned_course_db;
                    $_SESSION["assigned_section"] = $assigned_section_db;
                    $_SESSION["assigned_lecture"] = $assigned_lecture_db;
                    $_SESSION["assigned_lab"] = $assigned_lab_db;
                    echo "<script>window.location.href='teacher_dashboard.php';</script>";
                } else {
                    echo "<script>window.location.href='dashboard.php';</script>";
                    //echo "<script>alert('Login successful!'); window.location.href='dashboard.php';</script>";
                }
                exit;
            } else {
                echo "<script>alert('Incorrect password.');</script>";
            }
        } else {
            echo "<script>alert('ID Number not found.');</script>";
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Portal Login</title>
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
            background: url('he.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
            color: #2d3748;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 80% 20%, rgba(102, 126, 234, 0.4) 0%, transparent 40%),
                radial-gradient(circle at 20% 80%, rgba(118, 75, 162, 0.35) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(102, 126, 234, 0.25) 0%, transparent 50%),
                radial-gradient(circle at 10% 40%, rgba(118, 75, 162, 0.2) 0%, transparent 35%),
                radial-gradient(circle at 90% 60%, rgba(102, 126, 234, 0.3) 0%, transparent 40%),
                radial-gradient(ellipse at 30% 10%, rgba(118, 75, 162, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 90%, rgba(102, 126, 234, 0.2) 0%, transparent 45%),
                rgba(255, 255, 255, 0.9);
            z-index: 0;
            animation: bgShift 10s ease infinite;
        }
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 40% 70%, rgba(102, 126, 234, 0.15) 0%, transparent 30%),
                radial-gradient(circle at 60% 30%, rgba(118, 75, 162, 0.12) 0%, transparent 35%);
            z-index: 0;
            animation: bgShift 15s ease infinite reverse;
        }
        @keyframes bgShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        .particle {
            position: fixed;
            width: 4px;
            height: 4px;
            background: rgba(102, 126, 234, 0.6);
            border-radius: 50%;
            z-index: 0;
            animation: particleFloat 15s infinite;
        }
        @keyframes particleFloat {
            0% { transform: translateY(100vh) translateX(0) scale(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(100px) scale(1); opacity: 0; }
        }
        .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 20%; animation-delay: 2s; animation-duration: 12s; }
        .particle:nth-child(3) { left: 30%; animation-delay: 4s; animation-duration: 18s; }
        .particle:nth-child(4) { left: 40%; animation-delay: 1s; animation-duration: 14s; }
        .particle:nth-child(5) { left: 50%; animation-delay: 3s; animation-duration: 16s; }
        .particle:nth-child(6) { left: 60%; animation-delay: 5s; animation-duration: 13s; }
        .particle:nth-child(7) { left: 70%; animation-delay: 2.5s; animation-duration: 17s; }
        .particle:nth-child(8) { left: 80%; animation-delay: 4.5s; animation-duration: 15s; }
        .particle:nth-child(9) { left: 90%; animation-delay: 1.5s; animation-duration: 19s; }
        .particle:nth-child(10) { left: 15%; animation-delay: 3.5s; animation-duration: 11s; }
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            display: grid;
            grid-template-columns: 45% 55%;
            min-height: 550px;
        }
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        .header {
            background: #667eea;
            color: white;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            transition: all 0.5s ease;
        }
        .login-container:hover .header {
            background: #5568d3;
        }
        .header::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -30%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: headerRotate 20s linear infinite;
        }
        .header::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        @keyframes headerRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo {
            width: 120px;
            height: 120px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
            transition: transform 0.3s;
            position: relative;
            z-index: 1;
        }
        .logo img {
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.2));
        }
        .logo:hover {
            animation: pulse 0.6s ease-in-out infinite;
        }
        .header-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: white;
            animation: slideUp 0.6s ease 0.2s both;
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .header-subtitle {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.85);
            animation: slideUp 0.6s ease 0.4s both;
            text-align: center;
            line-height: 1.5;
            position: relative;
            z-index: 1;
        }
        .login-form {
            padding: 3rem 3rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }
        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 2rem;
            text-align: left;
            animation: slideUp 0.6s ease 0.6s both;
        }
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }
        .form-group:nth-child(2) {
            animation: slideUp 0.6s ease 0.8s both;
        }
        .form-group:nth-child(3) {
            animation: slideUp 0.6s ease 1s both;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #2d3748;
            font-size: 0.8125rem;
            margin-bottom: 0.375rem;
            transition: all 0.3s;
        }
        .form-group input:focus + label,
        .form-group:focus-within label {
            color: #667eea;
            transform: translateX(3px);
        }
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 1px solid rgba(226, 232, 240, 0.5);
            border-radius: 12px;
            font-size: 0.9375rem;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            color: #2d3748;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .form-group:nth-child(1) input {
            background: rgba(247, 250, 252, 0.6);
        }
        .form-group:nth-child(2) input {
            background: rgba(255, 255, 255, 0.5);
        }
        .form-group input:hover {
            border-color: rgba(102, 126, 234, 0.5);
            transform: translateX(5px);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
            background: rgba(255, 255, 255, 0.9);
        }
        .form-group:nth-child(1) input:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        .form-group:nth-child(2) input:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        .form-group::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 2.2rem;
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #a0aec0;
            transition: all 0.3s;
            z-index: 1;
            pointer-events: none;
        }
        .form-group:nth-child(1)::before {
            content: '\f2bb';
        }
        .form-group:nth-child(2)::before {
            content: '\f023';
        }
        .form-group:focus-within::before {
            color: #667eea;
            transform: scale(1.2);
        }
        .form-group input::placeholder {
            color: #a0aec0;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 8px 30px rgba(102, 126, 234, 0.2);
            transform: translateX(5px) scale(1.02);
        }
        .btn-login {
            width: 100%;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s ease 1.2s both;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn-login::after {
            content: '→';
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%) translateX(10px);
            opacity: 0;
            transition: all 0.3s;
            font-size: 1.2rem;
        }
        .btn-login:hover::before {
            width: 400px;
            height: 400px;
        }
        .btn-login:hover::after {
            opacity: 1;
            transform: translateY(-50%) translateX(0);
        }
        .btn-login:hover {
            background: #5568d3;
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.5);
            letter-spacing: 1.5px;
        }
        .btn-login:active {
            transform: translateY(-1px) scale(0.98);
        }

        /* Chatbot Styles */
        .chatbot-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 56px;
            height: 56px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            z-index: 1001;
            transition: all 0.3s;
            animation: float 3s ease-in-out infinite;
        }
        .chatbot-toggle:hover {
            background: #5568d3;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .chatbot-toggle i {
            color: white;
            font-size: 1.5rem;
        }
        .chatbot-container {
            position: fixed;
            bottom: 85px;
            right: 20px;
            width: 340px;
            height: 480px;
            background: #f5f0e8;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
            flex-direction: column;
            z-index: 1001;
            overflow: hidden;
        }
        .chatbot-container.active {
            display: flex;
        }
        .chatbot-header {
            background: #667eea;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chatbot-header h3 {
            margin: 0;
            font-size: 1.125rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .chatbot-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0;
            transition: transform 0.3s;
        }
        .chatbot-close:hover {
            transform: rotate(90deg);
        }
        .chatbot-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: white;
        }
        .chat-message {
            margin-bottom: 1rem;
            display: flex;
            gap: 0.5rem;
        }
        .chat-message.bot {
            justify-content: flex-start;
        }
        .chat-message.user {
            justify-content: flex-end;
        }
        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        .chat-message.bot .message-bubble {
            background: #f0f4ff;
            color: #2d3748;
            border: 1px solid #e2e8f0;
        }
        .chat-message.user .message-bubble {
            background: #667eea;
            color: white;
        }
        .chatbot-input {
            padding: 1rem;
            border-top: 1px solid #d4c4b0;
            display: flex;
            gap: 0.5rem;
            background: #f5f0e8;
        }
        .chatbot-input input {
            flex: 1;
            padding: 0.625rem;
            border: 1px solid #d4c4b0;
            border-radius: 4px;
            font-size: 0.875rem;
            background: white;
            color: #4a3f35;
        }
        .chatbot-input input::placeholder {
            color: #a89885;
        }
        .chatbot-input input:focus {
            outline: none;
            border-color: #a89885;
        }
        .chatbot-send {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.625rem 0.875rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .chatbot-send:hover {
            background: #5568d3;
            transform: scale(1.05);
        }
        @media (max-width: 900px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 450px;
                min-height: auto;
            }
            .header {
                padding: 2.5rem 2rem;
            }
            .logo {
                width: 100px;
                height: 100px;
                margin-bottom: 1.5rem;
            }
            .header-title {
                font-size: 1.5rem;
            }
            .header-subtitle {
                font-size: 0.9rem;
            }
            .login-form {
                padding: 2.5rem 2rem;
            }
            .form-title {
                font-size: 1.35rem;
            }
        }
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
                border-radius: 16px;
            }
            .header {
                padding: 2rem 1.5rem;
            }
            .logo {
                width: 80px;
                height: 80px;
                margin-bottom: 1rem;
            }
            .header-title {
                font-size: 1.25rem;
            }
            .header-subtitle {
                font-size: 0.8rem;
            }
            .login-form {
                padding: 2rem 1.5rem;
            }
            .form-title {
                font-size: 1.15rem;
            }
            .form-group input {
                padding: 0.75rem 1rem 0.75rem 2.5rem;
            }
            .form-group::before {
                left: 0.75rem;
                font-size: 0.9rem;
            }
        }
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .quick-btn {
            background: white;
            border: 1px solid #d4c4b0;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: background 0.2s;
            color: #4a3f35;
        }
        .quick-btn:hover {
            background: #e8dcc8;
        }
        @media (max-width: 768px) {
            .chatbot-container {
                width: calc(100vw - 40px);
                height: 450px;
                bottom: 80px;
            }
        }

    </style>
</head>
<body>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="login-container">
        <div class="header">
            <div class="logo">
                <img src="logo-ccs.webp" alt="CCS Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h1 class="header-title">College of Computer Studies Santa Cruz Campus</h1>
            <p class="header-subtitle">College of Computer Studies Santa Cruz Campus</p>
        </div>
        
        <div class="login-form">
            <h2 class="form-title">Student Portal Login</h2>
            <?php if (isset($_GET['registered'])): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; font-weight: 600;">
                    ✅ Registration successful! You can now login with your credentials.
                </div>
            <?php endif; ?>
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="id_number">Student ID Number</label>
                    <input type="text" name="id_number" id="id_number" placeholder="Enter your student ID" required maxlength="20" autocomplete="username"
                        oninput="this.value=this.value.replace(/[^0-9\\-]/g,'');">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-login" name="login">
                    <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
                    Login
                </button>
            </form>
        </div>
    </div>
    <!-- Chatbot -->
    <div class="chatbot-toggle" onclick="toggleChatbot()">
        <i class="fas fa-comments"></i>
    </div>
    <div class="chatbot-container" id="chatbotContainer">
        <div class="chatbot-header">
            <h3><i class="fas fa-robot"></i> CCS Assistant</h3>
            <button class="chatbot-close" onclick="toggleChatbot()">&times;</button>
        </div>
        <div class="chatbot-messages" id="chatbotMessages">
            <div class="chat-message bot">
                <div class="message-bubble">
                    👋 Hi! I'm your CCS Portal assistant. How can I help you today?
                </div>
            </div>
            <div class="quick-actions" id="quickActions">
                <button class="quick-btn" onclick="sendQuickMessage('How do I register?')">📝 Register</button>
                <button class="quick-btn" onclick="sendQuickMessage('What documents do I need?')">📄 Documents</button>
                <button class="quick-btn" onclick="sendQuickMessage('I forgot my password')">🔑 Password</button>
                <button class="quick-btn" onclick="sendQuickMessage('How long does approval take?')">⏱️ Approval</button>
                <button class="quick-btn" onclick="sendQuickMessage('What is my student ID format?')">🆔 ID Format</button>
                <button class="quick-btn" onclick="sendQuickMessage('Who can I contact for help?')">📞 Contact</button>
                <button class="quick-btn" onclick="sendQuickMessage('What browsers are supported?')">🌐 Browser</button>
                <button class="quick-btn" onclick="sendQuickMessage('How to request unscheduled subjects?')">📚 Unscheduled</button>
            </div>
        </div>
        <div class="chatbot-input">
            <input type="text" id="chatbotInput" placeholder="Type your question..." onkeypress="if(event.key==='Enter') sendMessage()">
            <button class="chatbot-send" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

  <script>
    // Chatbot Functions - Vanilla JavaScript
    // Framework: Native JS with Fetch API
    function toggleChatbot() {
        const container = document.getElementById('chatbotContainer');
        container.classList.toggle('active');
        if (container.classList.contains('active')) {
            document.getElementById('chatbotInput').focus();
        }
    }

    async function sendMessage() {
        const input = document.getElementById('chatbotInput');
        const message = input.value.trim();
        if (!message) return;
        addMessage(message, 'user');
        input.value = '';
        try {
            const response = await fetch('chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });
            const data = await response.json();
            addMessage(data.reply, 'bot');
        } catch (error) {
            addMessage('Sorry, I encountered an error. Please try again.', 'bot');
        }
    }

    function addMessage(text, sender) {
        const messagesDiv = document.getElementById('chatbotMessages');
        const messageDiv = document.createElement('div');
        const id = 'msg-' + Date.now();
        messageDiv.id = id;
        messageDiv.className = 'chat-message ' + sender;
        messageDiv.innerHTML = '<div class="message-bubble">' + text + '</div>';
        messagesDiv.appendChild(messageDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
        return id;
    }

    async function sendQuickMessage(message) {
        addMessage(message, 'user');
        try {
            const response = await fetch('chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });
            const data = await response.json();
            addMessage(data.reply, 'bot');
            showQuickActions();
        } catch (error) {
            addMessage('Sorry, I encountered an error. Please try again.', 'bot');
            showQuickActions();
        }
    }

    function showQuickActions() {
        const messagesDiv = document.getElementById('chatbotMessages');
        const existing = document.getElementById('quickActions');
        if (existing) existing.remove();
        const quickDiv = document.createElement('div');
        quickDiv.id = 'quickActions';
        quickDiv.className = 'quick-actions';
        quickDiv.innerHTML = `
            <button class="quick-btn" onclick="sendQuickMessage('How do I register?')">📝 Register</button>
            <button class="quick-btn" onclick="sendQuickMessage('What documents do I need?')">📄 Documents</button>
            <button class="quick-btn" onclick="sendQuickMessage('I forgot my password')">🔑 Password</button>
            <button class="quick-btn" onclick="sendQuickMessage('How long does approval take?')">⏱️ Approval</button>
            <button class="quick-btn" onclick="sendQuickMessage('What is my student ID format?')">🆔 ID Format</button>
            <button class="quick-btn" onclick="sendQuickMessage('Who can I contact for help?')">📞 Contact</button>
            <button class="quick-btn" onclick="sendQuickMessage('What browsers are supported?')">🌐 Browser</button>
            <button class="quick-btn" onclick="sendQuickMessage('How to request unscheduled subjects?')">📚 Unscheduled</button>
        `;
        messagesDiv.appendChild(quickDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }


  </script>
</body>
</html>