<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_year = $_POST['from_year'];
    $from_sem = $_POST['from_sem'];
    $to_year = $_POST['to_year'];
    $to_sem = $_POST['to_sem'];
    
    $stmt = $conn->prepare("UPDATE users SET year_level = ?, semester = ? WHERE year_level = ? AND semester = ? AND user_type = 'student'");
    $stmt->bind_param("ssss", $to_year, $to_sem, $from_year, $from_sem);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $message = "✅ Successfully updated $affected students from $from_year $from_sem to $to_year $to_sem";
    } else {
        $message = "❌ Failed to update students";
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Semester - Dean Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 800px; }
        h1 { color: #2d3748; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; }
        select { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
        .btn { background: #667eea; color: white; border: none; padding: 0.875rem 2rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem; }
        .btn:hover { background: #5568d3; }
        .message { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .message.success { background: #d1fae5; color: #065f46; }
        .message.error { background: #fee2e2; color: #b91c1c; }
        .arrow { text-align: center; font-size: 2rem; color: #667eea; margin: 1rem 0; }
    </style>
</head>
<body>
    <?php include 'dean_navbar.php'; ?>
    
    <div class="main-container">
        <div class="card">
            <h1><i class="fas fa-sync-alt"></i> Update Student Semester</h1>
            
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <h3 style="color: #2d3748; margin-bottom: 1rem;">From:</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="from_year" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="from_sem" required>
                            <option value="1st">1st Semester</option>
                            <option value="2nd">2nd Semester</option>
                            <option value="Intersem">Intersem</option>
                        </select>
                    </div>
                </div>
                
                <div class="arrow"><i class="fas fa-arrow-down"></i></div>
                
                <h3 style="color: #2d3748; margin-bottom: 1rem;">To:</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="to_year" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="to_sem" required>
                            <option value="1st">1st Semester</option>
                            <option value="2nd">2nd Semester</option>
                            <option value="Intersem">Intersem</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-sync-alt"></i> Update Students
                </button>
            </form>
        </div>
    </div>
</body>
</html>
