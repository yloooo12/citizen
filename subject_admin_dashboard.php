<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "subject_admin") {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? "Subject Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Admin Dashboard - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
        .header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s ease; cursor: pointer; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .card-icon { font-size: 3rem; margin-bottom: 1rem; }
        .card h3 { color: #2d3748; margin-bottom: 0.5rem; }
        .card p { color: #718096; font-size: 0.9rem; }
        .logout-btn { background: white; color: #667eea; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .logout-btn:hover { background: #f0f4ff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>👋 Welcome, <?php echo htmlspecialchars($first_name); ?>!</h1>
                    <p>Subject Admin Dashboard - Teacher Subject Management</p>
                </div>
                <form method="post" action="login.php">
                    <button type="submit" name="logout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <div class="cards">
            <div class="card" onclick="location.href='subject_admin_assign.php'">
                <div class="card-icon">📚</div>
                <h3>Assign Subjects to Teachers</h3>
                <p>Assign multiple subjects to teachers for different classes</p>
            </div>

            <div class="card" onclick="location.href='subject_admin_view.php'">
                <div class="card-icon">👁️</div>
                <h3>View Teacher Assignments</h3>
                <p>View all teacher subject assignments</p>
            </div>

            <div class="card" onclick="location.href='subject_admin_reports.php'">
                <div class="card-icon">📊</div>
                <h3>Subject Reports</h3>
                <p>Generate reports on subject assignments</p>
            </div>
        </div>
    </div>
</body>
</html>
