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

$message = "";

require_once 'send_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["teacher_csv"])) {
    $file = $_FILES["teacher_csv"]["tmp_name"];
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        $row = 0;
        $success = 0;
        $failed = 0;
        $skipped = 0;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row++;
            if ($row == 1) continue;
            
            if (count($data) < 5) continue;
            
            $id_number = trim($data[0]);
            $first_name = trim($data[1]);
            $middle_name = isset($data[2]) && !empty(trim($data[2])) ? trim($data[2]) : NULL;
            $last_name = trim($data[3]);
            $email = trim($data[4]);
            
            if (empty($id_number) || empty($first_name) || empty($last_name) || empty($email)) continue;
            
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $check = $conn->prepare("SELECT id FROM teacher_invitations WHERE id_number=?");
            if ($check) {
                $check->bind_param("s", $id_number);
                $check->execute();
                $check->store_result();
                
                if ($check->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO teacher_invitations (id_number, first_name, last_name, email, token, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ssssss", $id_number, $first_name, $last_name, $email, $token, $expires);
                        
                        if ($stmt->execute()) {
                            sendTeacherRegistrationEmail($email, $first_name, $last_name, $id_number, $token);
                            $success++;
                        } else {
                            $failed++;
                            $message = "SQL Error: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $failed++;
                        error_log("Prepare failed: " . $conn->error);
                    }
                } else {
                    $skipped++;
                }
                $check->close();
            } else {
                $message = "Error: teacher_invitations table does not exist. Run create_teacher_invitations_table.sql first.";
                break;
            }
        }
        fclose($handle);
        if (empty($message)) {
            $message = "Upload complete: $success successful, $failed failed, $skipped already exists";
            if ($failed > 0) {
                $message .= " (Check error log for details)";
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
    <title>Upload Teachers - Dean Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2d3748; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; }
        input[type="file"] { width: 100%; padding: 0.75rem; border: 2px dashed #667eea; border-radius: 8px; }
        .btn { background: #667eea; color: white; padding: 0.875rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #5568d3; }
        .message { padding: 1rem; background: #e6f4ea; color: #1e7e34; border-radius: 8px; margin-bottom: 1rem; }
        .info { background: #f0f4ff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .info h3 { color: #667eea; margin-bottom: 0.5rem; }
        .info p { color: #4a5568; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 12px; text-align: center; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <?php include 'dean_navbar.php'; ?>
    <div class="container" style="margin-left: 260px; margin-top: 85px; max-width: calc(100% - 280px);">
        <h1>Upload Teacher Data</h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="info">
            <h3>CSV Format Required:</h3>
            <p><strong>ID Number, First Name, Middle Name (optional), Last Name, Email</strong></p>
            <p>Example 1: TEACHER001, Maria, Cruz, Santos, maria.santos@lspu.edu.ph</p>
            <p>Example 2: TEACHER002, Juan, , Dela Cruz, juan.delacruz@lspu.edu.ph</p>
            <p style="margin-top: 0.5rem;"><a href="sample_teachers.csv" download style="color: #667eea; font-weight: 600;"><i class="fas fa-download"></i> Download Sample CSV</a></p>
            <p style="margin-top: 0.5rem; color: #667eea;"><strong>Note:</strong> Teachers will set their own password during registration</p>
        </div>
        
        <form method="post" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label>Upload Teacher CSV File</label>
                <input type="file" name="teacher_csv" accept=".csv" required>
            </div>
            <button type="submit" class="btn" id="uploadBtn">Upload Teachers</button>
        </form>
    </div>
    
    <div id="loadingModal" class="modal">
        <div class="modal-content">
            <div class="spinner"></div>
            <h3>Uploading Teachers...</h3>
            <p>Please wait while we process your file.</p>
        </div>
    </div>
    
    <script>
        document.getElementById('uploadForm').addEventListener('submit', function() {
            document.getElementById('loadingModal').style.display = 'block';
            document.getElementById('uploadBtn').disabled = true;
        });
    </script>
    </div>
</body>
</html>
