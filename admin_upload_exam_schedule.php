<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$upload_success = false;
$upload_error = '';
$stats = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            
            if ($handle !== false) {
                $header = fgetcsv($handle);
                $success_count = 0;
                $error_count = 0;
                $matched_students = [];
                
                while (($data = fgetcsv($handle)) !== false) {
                    if (count($data) >= 5) {
                        $student_name = trim($data[0]);
                        $subject = trim($data[1]);
                        $exam_date = trim($data[2]);
                        $exam_time = trim($data[3]);
                        $exam_room = trim($data[4]);
                        
                        // Find student by name
                        $stmt = $conn->prepare("SELECT id, email FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE ? AND role = 'student' LIMIT 1");
                        $search_name = "%$student_name%";
                        $stmt->bind_param("s", $search_name);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($student = $result->fetch_assoc()) {
                            $student_id = $student['id'];
                            $student_email = $student['email'];
                            
                            // Create notification
                            $message = "Your exam schedule for $subject: Date: $exam_date, Time: $exam_time, Room: $exam_room";
                            $stmt2 = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                            $stmt2->bind_param("is", $student_id, $message);
                            $stmt2->execute();
                            
                            // Send email
                            $to = $student_email;
                            $email_subject = "Exam Schedule - $subject";
                            $email_message = "Dear $student_name,\n\nYour exam schedule for $subject has been finalized:\n\nDate: $exam_date\nTime: $exam_time\nRoom: $exam_room\n\nPlease be on time.\n\n- Admin";
                            $headers = "From: admin@lspu.edu.ph";
                            mail($to, $email_subject, $email_message, $headers);
                            
                            $success_count++;
                            $matched_students[] = $student_name;
                        } else {
                            $error_count++;
                        }
                        $stmt->close();
                    }
                }
                
                fclose($handle);
                $upload_success = true;
                $stats = [
                    'success' => $success_count,
                    'errors' => $error_count,
                    'total' => $success_count + $error_count
                ];
            } else {
                $upload_error = 'Failed to read CSV file.';
            }
        } else {
            $upload_error = 'Please upload a CSV file.';
        }
    } else {
        $upload_error = 'File upload error.';
    }
}

// Get current exam schedule stats
$total_scheduled = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE message LIKE '%exam schedule%' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_scheduled = $row['count'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Exam Schedules - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 1.5rem;
        }

        .header .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .container {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        .sidebar {
            width: 250px;
            background: white;
            padding: 2rem 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar a {
            display: block;
            padding: 1rem 2rem;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background: #667eea;
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            opacity: 0.9;
        }

        .upload-form {
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px dashed #667eea;
            border-radius: 5px;
            background: #f8f9ff;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 5px;
        }

        .info-box h4 {
            color: #2196F3;
            margin-bottom: 0.5rem;
        }

        .info-box code {
            background: white;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-calendar-alt"></i> Upload Exam Schedules</h1>
        <div class="user-info">
            <span>Admin</span>
            <a href="admin_dashboard.php" style="color: #667eea; text-decoration: none;"><i class="fas fa-home"></i></a>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <a href="admin_dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
            <a href="admin_students.php"><i class="fas fa-users"></i> Students</a>
            <a href="admin_inc.php"><i class="fas fa-file-alt"></i> INC Requests</a>
            <a href="admin_upload_grades.php"><i class="fas fa-upload"></i> Upload Grades</a>
            <a href="admin_upload_crediting.php"><i class="fas fa-award"></i> Upload Crediting</a>
            <a href="admin_upload_exam_schedule.php" class="active"><i class="fas fa-calendar-alt"></i> Upload Exam Schedule</a>
        </div>

        <div class="main-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $total_scheduled; ?></h3>
                    <p>Exam Schedules Sent (30 Days)</p>
                </div>
            </div>

            <?php if ($upload_success): ?>
                <div class="alert alert-success">
                    <strong>Success!</strong> Exam schedules uploaded successfully.
                    <br>Matched: <?php echo $stats['success']; ?> | Not Found: <?php echo $stats['errors']; ?> | Total: <?php echo $stats['total']; ?>
                </div>
            <?php endif; ?>

            <?php if ($upload_error): ?>
                <div class="alert alert-error">
                    <strong>Error!</strong> <?php echo htmlspecialchars($upload_error); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>Upload Exam Schedule CSV</h2>
                <p>Upload a CSV file containing exam schedules for students.</p>

                <div class="info-box">
                    <h4>CSV Format Required:</h4>
                    <p><code>Student Name | Subject | Date | Time | Room</code></p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem;">Example: John Doe | Mathematics | 2024-01-15 | 9:00 AM | Room 101</p>
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="csv_file">Select CSV File:</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-upload"></i> Upload Exam Schedules
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
