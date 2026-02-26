<?php
session_start();

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $id_number = trim($_POST["id_number"]);
    $pass = $_POST["password"];

    if (empty($id_number) || empty($pass)) {
        echo "<script>alert('ID Number and password are required.');</script>";
    } else {
        $stmt = $conn->prepare("SELECT id, password, first_name, last_name, id_number, email, user_type, is_admin, assigned_course, assigned_section, assigned_lecture, assigned_lab FROM users WHERE id_number=? LIMIT 1");
        if (!$stmt) {
            echo "<script>alert('Database error.');</script>";
        } else {
            $stmt->bind_param("s", $id_number);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $plain_pass, $first_name, $last_name, $id_number_db, $email, $user_type, $is_admin, $assigned_course, $assigned_section, $assigned_lecture, $assigned_lab);
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
                    
                    $_SESSION["user_id"] = $id;
                    $_SESSION["first_name"] = $first_name;
                    $_SESSION["last_name"] = $last_name;
                    $_SESSION["id_number"] = $id_number_db;
                    $_SESSION["email"] = $email;
                    
                    if ($is_admin == 4) {
                        $_SESSION["is_admin"] = 4;
                        header("Location: system_admin_dashboard.php");
                    } elseif ($is_admin == 3) {
                        $_SESSION["is_admin"] = true;
                        header("Location: dean_dashboard.php");
                    } elseif ($is_admin == 2) {
                        $_SESSION["is_admin"] = 2;
                        header("Location: secretary_dashboard.php");
                    } elseif ($is_admin == 1) {
                        $_SESSION["is_admin"] = 1;
                        header("Location: program_head_dashboard.php");
                    } elseif ($user_type == 'program_head') {
                        $_SESSION["user_type"] = 'program_head';
                        header("Location: program_head_dashboard.php");
                    } elseif ($user_type == 'secretary') {
                        $_SESSION["user_type"] = 'secretary';
                        header("Location: secretary_dashboard.php");
                    } elseif ($user_type == 'dean') {
                        $_SESSION["is_admin"] = true;
                        header("Location: dean_dashboard.php");
                    } elseif ($user_type == 'teacher') {
                        $_SESSION["is_teacher"] = true;
                        $_SESSION["user_type"] = 'teacher';
                        $_SESSION["assigned_course"] = $assigned_course;
                        $_SESSION["assigned_section"] = $assigned_section;
                        $_SESSION["assigned_lecture"] = $assigned_lecture;
                        $_SESSION["assigned_lab"] = $assigned_lab;
                        header("Location: teacher_dashboard.php");
                    } else {
                        echo "<script>alert('Access denied. Only admin/staff can login here.');</script>";
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
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin/Staff Login - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #1a202c;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-container {
            background: #2d3748;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 420px;
            border: 1px solid #4a5568;
            animation: fadeIn 0.5s ease;
        }
        .header {
            background: #374151;
            color: white;
            padding: 2rem;
            text-align: center;
            border-bottom: 2px solid #667eea;
            animation: slideDown 0.6s ease;
        }
        .header i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #667eea;
            animation: fadeIn 0.8s ease;
        }
        .header h1 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        .header p {
            font-size: 0.85rem;
            color: #cbd5e0;
        }
        .login-form {
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
            animation: fadeIn 0.7s ease;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #4a5568;
            border-radius: 6px;
            font-size: 0.95rem;
            background: #1a202c;
            color: #e2e8f0;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: #2d3748;
        }
        .btn-login {
            width: 100%;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            animation: fadeIn 0.9s ease;
        }
        .btn-login:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .back-link a:hover {
            color: #5568d3;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <i class="fas fa-user-shield"></i>
            <h1>Admin/Staff Log In</h1>
            <p>System Admin • Dean • Teacher • Program Head • Secretary</p>
        </div>
        
        <div class="login-form">
            <form method="post">
                <div class="form-group">
                    <label for="id_number">ID Number</label>
                    <input type="text" name="id_number" id="id_number" placeholder="Enter your ID" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-login" name="login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

        </div>
    </div>
</body>
</html>
