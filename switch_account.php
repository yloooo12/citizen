<?php
session_start();

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$current_id = $_SESSION["id_number"] ?? '';
$mia_accounts = [];

// Check if Mia (ID 246 or 009)
if ($current_id == '246' || $current_id == '009') {
    $result = $conn->query("SELECT id_number, first_name, last_name, email, user_type FROM users WHERE (id_number='246' OR id_number='009') AND status='approved'");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $mia_accounts[] = $row;
        }
    }
}

// Handle switch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["switch_to"])) {
    $switch_id = $_POST["switch_to"];
    $stmt = $conn->prepare("SELECT id, password, first_name, last_name, id_number, email, status, user_type, assigned_course, assigned_section, assigned_lecture, assigned_lab FROM users WHERE id_number=?");
    $stmt->bind_param("s", $switch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Clear session
        session_destroy();
        session_start();
        
        // Set new session
        $_SESSION["user_id"] = $user['id'];
        $_SESSION["first_name"] = $user['first_name'];
        $_SESSION["last_name"] = $user['last_name'];
        $_SESSION["id_number"] = $user['id_number'];
        $_SESSION["email"] = $user['email'];
        
        if ($user['user_type'] == 'teacher') {
            $_SESSION["is_teacher"] = true;
            $_SESSION["user_type"] = 'teacher';
            $_SESSION["assigned_course"] = $user['assigned_course'];
            $_SESSION["assigned_section"] = $user['assigned_section'];
            $_SESSION["assigned_lecture"] = $user['assigned_lecture'];
            $_SESSION["assigned_lab"] = $user['assigned_lab'];
            header("Location: teacher_dashboard.php");
        } elseif ($user['id_number'] == '246' || $user['user_type'] == 'dean') {
            $_SESSION["is_admin"] = true;
            header("Location: dean_student_upload.php");
        }
        exit;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Switch Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .container { background: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; width: 100%; }
        h1 { color: #2d3748; margin-bottom: 1.5rem; font-size: 1.75rem; }
        .account-card { padding: 1.5rem; background: #f7fafc; border-radius: 10px; margin-bottom: 1rem; border-left: 4px solid #667eea; cursor: pointer; transition: all 0.3s; }
        .account-card:hover { background: #edf2f7; transform: translateX(5px); }
        .account-name { font-weight: 600; color: #2d3748; font-size: 1.1rem; margin-bottom: 0.5rem; }
        .account-type { color: #718096; font-size: 0.9rem; }
        .btn { width: 100%; background: #667eea; color: white; padding: 1rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
        .btn:hover { background: #5568d3; }
        .back-link { text-align: center; margin-top: 1rem; }
        .back-link a { color: #667eea; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-exchange-alt"></i> Switch Account</h1>
        
        <?php if (!empty($mia_accounts)): ?>
            <?php foreach ($mia_accounts as $acc): ?>
                <form method="post" style="margin: 0;">
                    <input type="hidden" name="switch_to" value="<?php echo $acc['id_number']; ?>">
                    <div class="account-card" onclick="this.parentElement.submit()">
                        <div class="account-name"><?php echo $acc['first_name'] . ' ' . $acc['last_name']; ?></div>
                        <div class="account-type">
                            <i class="fas fa-<?php echo $acc['user_type'] == 'teacher' ? 'chalkboard-teacher' : 'user-shield'; ?>"></i>
                            <?php echo ucfirst($acc['user_type']); ?> - <?php echo $acc['email']; ?>
                        </div>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #718096; padding: 2rem;">No accounts available to switch.</p>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Go Back</a>
        </div>
    </div>
</body>
</html>
