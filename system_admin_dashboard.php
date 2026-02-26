<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 4) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Get system stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type='student'")->fetch_assoc()['count'];
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type='teacher'")->fetch_assoc()['count'];
$total_admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin > 0")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #1a202c;
            min-height: 100vh;
        }
        .main-container { 
            margin-left: 260px; 
            margin-top: 85px; 
            padding: 2rem;
            animation: fadeIn 0.6s ease-out;
        }
        .main-container.collapsed { margin-left: 70px; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .header { 
            background: #2d3748;
            padding: 2.5rem; 
            border-radius: 16px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
            border: 1px solid #4a5568;
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #e53e3e;
            border-radius: 16px 16px 0 0;
        }
        
        .header h1 { 
            color: white; 
            font-size: 2.2rem; 
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .header p {
            color: #a0aec0;
            font-size: 1.1rem;
        }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 2rem; 
            margin-bottom: 3rem; 
        }
        
        .stat-card { 
            background: white;
            padding: 2rem; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
        }
        
        .stat-card:hover { 
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 { 
            color: #4a5568; 
            font-size: 0.9rem; 
            margin-bottom: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number { 
            color: #2d3748; 
            font-size: 2.5rem; 
            font-weight: 800;
        }
        
        .admin-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
            gap: 2rem; 
        }
        
        .admin-card { 
            background: white;
            padding: 2.5rem; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-top: 4px solid #4299e1;
        }
        
        .admin-card:hover { 
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .admin-card h3 { 
            color: #2d3748; 
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        .admin-card p {
            margin-bottom: 1.5rem; 
            color: #718096;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .btn { 
            padding: 1rem 2rem; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            border: none; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary { 
            background: #4299e1;
            color: white;
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.3);
        }
        
        .btn-primary:hover { 
            background: #3182ce;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 153, 225, 0.4);
        }
        
        .btn-danger { 
            background: #e53e3e;
            color: white;
            box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
        }
        
        .btn-danger:hover { 
            background: #c53030;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
        }
        
        .btn-success { 
            background: #38a169;
            color: white;
            box-shadow: 0 4px 12px rgba(56, 161, 105, 0.3);
        }
        
        .btn-success:hover { 
            background: #2f855a;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 161, 105, 0.4);
        }
        
        .admin-card .fas {
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .admin-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'system_admin_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="header">
            <h1><i class="fas fa-cogs"></i> System Administration</h1>
            <p>Complete control over the entire system</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $total_users; ?></div>
            </div>
            <div class="stat-card">
                <h3>Students</h3>
                <div class="number"><?php echo $total_students; ?></div>
            </div>
            <div class="stat-card">
                <h3>Teachers</h3>
                <div class="number"><?php echo $total_teachers; ?></div>
            </div>
            <div class="stat-card">
                <h3>Administrators</h3>
                <div class="number"><?php echo $total_admins; ?></div>
            </div>
        </div>
        
        <div class="admin-grid">
            <div class="admin-card">
                <h3><i class="fas fa-users-cog"></i> User Management</h3>
                <p style="margin-bottom: 1rem; color: #718096;">Manage all users in the system</p>
                <a href="system_admin_users.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </div>
            
            <div class="admin-card">
                <h3><i class="fas fa-user-shield"></i> Admin Management</h3>
                <p style="margin-bottom: 1rem; color: #718096;">Create and manage administrators</p>
                <a href="system_admin_admins.php" class="btn btn-primary">
                    <i class="fas fa-user-shield"></i> Manage Admins
                </a>
            </div>
            
            <div class="admin-card">
                <h3><i class="fas fa-database"></i> Database Management</h3>
                <p style="margin-bottom: 1rem; color: #718096;">Backup, restore, and maintain database</p>
                <a href="system_admin_database.php" class="btn btn-success">
                    <i class="fas fa-database"></i> Database Tools
                </a>
            </div>
            
            <div class="admin-card">
                <h3><i class="fas fa-chart-bar"></i> System Reports</h3>
                <p style="margin-bottom: 1rem; color: #718096;">Generate comprehensive system reports</p>
                <a href="system_admin_reports.php" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> View Reports
                </a>
            </div>
            
            <div class="admin-card">
                <h3><i class="fas fa-cog"></i> System Settings</h3>
                <p style="margin-bottom: 1rem; color: #718096;">Configure system-wide settings</p>
                <a href="system_admin_settings.php" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
            
            <div class="admin-card">
                <h3><i class="fas fa-exclamation-triangle"></i> Emergency Controls</h3>
                <p style="margin-bottom: 1rem; color: #718096;">System maintenance and emergency actions</p>
                <a href="system_admin_emergency.php" class="btn btn-danger">
                    <i class="fas fa-exclamation-triangle"></i> Emergency Panel
                </a>
            </div>
        </div>
    </div>
</body>
</html>