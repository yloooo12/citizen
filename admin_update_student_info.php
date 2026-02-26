<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        
        // Skip header row
        fgetcsv($file);
        
        $updated = 0;
        $failed = 0;
        
        while (($data = fgetcsv($file)) !== FALSE) {
            if (count($data) >= 4) {
                $id_number = trim($data[0]);
                $year_level = trim($data[1]);
                $semester = trim($data[2]);
                $school_year = trim($data[3]);
                
                $stmt = $conn->prepare("UPDATE users SET year_level=?, semester=?, school_year=? WHERE id_number=?");
                $stmt->bind_param("ssss", $year_level, $semester, $school_year, $id_number);
                
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $updated++;
                } else {
                    $failed++;
                }
                $stmt->close();
            }
        }
        
        fclose($file);
        $message = "Updated: $updated students. Failed: $failed";
    } else {
        $error = "File upload error";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Info - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2d3748; margin-bottom: 1rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
        .upload-box { border: 2px dashed #e2e8f0; padding: 2rem; text-align: center; border-radius: 8px; margin: 1rem 0; }
        .btn { background: #667eea; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #5568d3; }
        .format { background: #f9fafb; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .format code { background: #e2e8f0; padding: 0.2rem 0.5rem; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.75rem; text-align: left; border: 1px solid #e2e8f0; }
        th { background: #f9fafb; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Update Student Information (Bulk)</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="format">
            <h3>CSV Format Required:</h3>
            <table>
                <thead>
                    <tr>
                        <th>id_number</th>
                        <th>year_level</th>
                        <th>semester</th>
                        <th>school_year</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2021-00001</td>
                        <td>2nd Year</td>
                        <td>1st Semester</td>
                        <td>2026-2027</td>
                    </tr>
                    <tr>
                        <td>2021-00002</td>
                        <td>2nd Year</td>
                        <td>1st Semester</td>
                        <td>2026-2027</td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top: 1rem; color: #718096;">
                <strong>Note:</strong> First row must be headers. Only students with matching id_number will be updated.
            </p>
        </div>
        
        <form method="post" enctype="multipart/form-data">
            <div class="upload-box">
                <input type="file" name="csv_file" accept=".csv" required style="margin-bottom: 1rem;">
                <br>
                <button type="submit" class="btn">Upload & Update</button>
            </div>
        </form>
        
        <div style="margin-top: 2rem;">
            <a href="admin_dashboard.php" style="color: #667eea; text-decoration: none;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
