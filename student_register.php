<?php
session_start();

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$token = $_GET['token'] ?? '';
$error = "";
$student_data = null;

// Verify token
if ($token) {
    $stmt = $conn->prepare("SELECT id_number, first_name, last_name, email FROM student_invitations WHERE token=? AND expires_at > NOW() AND used=0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $student_data = $result->fetch_assoc();
    } else {
        $error = "Invalid or expired token";
    }
    $stmt->close();
}

// Handle registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && $student_data) {
    $course = $_POST['course'];
    $section = $_POST['section'];
    $student_type = $_POST['student_type'];
    $sex = $_POST['sex'];
    $contact = $_POST['contact_number'];
    $password = $_POST['password'];
    
    // Add student_type column if not exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'student_type'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN student_type VARCHAR(20) AFTER section");
    }
    
    $stmt = $conn->prepare("INSERT INTO users (id_number, first_name, last_name, email, course, section, student_type, sex, contact_number, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
    $stmt->bind_param("ssssssssss", $student_data['id_number'], $student_data['first_name'], $student_data['last_name'], $student_data['email'], $course, $section, $student_type, $sex, $contact, $password);
    
    if ($stmt->execute()) {
        // Mark invitation as used
        $update = $conn->prepare("UPDATE student_invitations SET used=1 WHERE token=?");
        $update->bind_param("s", $token);
        $update->execute();
        
        // Add notification for dean
        $notif_message = "New student registered: " . $student_data['first_name'] . " " . $student_data['last_name'] . " (" . $student_data['id_number'] . ")";
        $notif_message = mysqli_real_escape_string($conn, $notif_message);
        $conn->query("INSERT INTO dean_notifications (user_id, message, is_read, created_at) VALUES (NULL, '$notif_message', 0, NOW())");
        
        echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
        exit;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .container { background: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; width: 100%; }
        h1 { color: #2d3748; margin-bottom: 0.5rem; font-size: 1.75rem; }
        .subtitle { color: #718096; margin-bottom: 2rem; }
        .info-box { background: #f0f4ff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .info-box p { color: #4a5568; font-size: 0.9rem; margin: 0.25rem 0; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; font-size: 0.9rem; }
        input, select { width: 100%; padding: 0.875rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
        input:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { width: 100%; background: #667eea; color: white; padding: 1rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: #5568d3; }
        .error { background: #fee; color: #c00; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php elseif ($student_data): ?>
            <h1>Complete Your Registration</h1>
            <p class="subtitle">Fill in the additional details below</p>
            
            <div class="info-box">
                <p><strong>ID Number:</strong> <?php echo $student_data['id_number']; ?></p>
                <p><strong>Name:</strong> <?php echo $student_data['first_name'] . ' ' . $student_data['last_name']; ?></p>
                <p><strong>Email:</strong> <?php echo $student_data['email']; ?></p>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label>Course</label>
                    <select name="course" required>
                        <option value="">Select Course</option>
                        <option value="IT">Information Technology</option>
                        <option value="CS">Computer Science</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Section</label>
                    <select name="section" required>
                        <option value="">Select Section</option>
                        <option value="A">Section A</option>
                        <option value="B">Section B</option>
                        <option value="C">Section C</option>
                        <option value="D">Section D</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Student Type</label>
                    <select name="student_type" required>
                        <option value="">Select Student Type</option>
                        <option value="Freshmen">Freshmen</option>
                        <option value="Returnee">Returnee</option>
                        <option value="Transferee">Transferee</option>
                        <option value="Shifter">Shifter</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Gender</label>
                    <select name="sex" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" placeholder="09XXXXXXXXX" maxlength="11" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Create your password" required>
                </div>
                
                <button type="submit" class="btn">Complete Registration</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
