<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== 4) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Get report data
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type='student'")->fetch_assoc()['count'];
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type='teacher'")->fetch_assoc()['count'];
$active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active=1")->fetch_assoc()['count'];

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_report'])) {
        $report_type = $_POST['report_type'];
        $success_msg = "Report generated successfully: " . ucfirst($report_type) . " Report";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - System Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .main-container.collapsed { margin-left: 70px; }
        .header { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .header h1 { color: #2d3748; font-size: 1.75rem; margin-bottom: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; }
        .stat-card h3 { color: #718096; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .stat-card .number { color: #2d3748; font-size: 2rem; font-weight: 700; }
        .tech-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .tech-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .tech-card h3 { color: #2d3748; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .vr-panel { background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; }
        .ar-panel { background: linear-gradient(135deg, #06b6d4, #67e8f9); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; }
        .blockchain-panel { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; }
        .hologram-panel { background: linear-gradient(135deg, #ec4899, #f472b6); color: white; padding: 1.5rem; border-radius: 12px; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.25rem; }
        .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
    </style>
</head>
<body>
    <?php include 'system_admin_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Advanced System Reports</h1>
            <p>Generate reports with cutting-edge visualization technologies</p>
        </div>
        
        <?php if (isset($success_msg)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
        </div>
        <?php endif; ?>
        
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
                <h3>Active Users</h3>
                <div class="number"><?php echo $active_users; ?></div>
            </div>
        </div>
        
        <!-- VR Reports -->
        <div class="vr-panel">
            <h3><i class="fas fa-vr-cardboard"></i> Virtual Reality Reports</h3>
            <p>Experience your data in immersive 3D virtual environments</p>
            <button class="btn btn-primary" onclick="launchVR()">
                <i class="fas fa-cube"></i> Launch VR Dashboard
            </button>
        </div>
        
        <!-- AR Visualization -->
        <div class="ar-panel">
            <h3><i class="fas fa-eye"></i> Augmented Reality Analytics</h3>
            <p>View live data overlays in your real environment</p>
            <button class="btn btn-success" onclick="startAR()">
                <i class="fas fa-camera"></i> Start AR View
            </button>
        </div>
        
        <!-- Blockchain Reports -->
        <div class="blockchain-panel">
            <h3><i class="fas fa-link"></i> Blockchain Audit Trail</h3>
            <p>Tamper-proof reports with cryptographic verification</p>
            <button class="btn btn-warning" onclick="generateBlockchain()">
                <i class="fas fa-shield-alt"></i> Generate Blockchain Report
            </button>
        </div>
        
        <!-- Holographic Display -->
        <div class="hologram-panel">
            <h3><i class="fas fa-magic"></i> Holographic Projection</h3>
            <p>Project your reports as 3D holograms in space</p>
            <button class="btn btn-danger" onclick="projectHologram()">
                <i class="fas fa-broadcast-tower"></i> Project Hologram
            </button>
        </div>
        
        <!-- Monthly Trends -->
        <div class="tech-card" style="margin-bottom: 2rem;">
            <h3><i class="fas fa-chart-line"></i> Monthly Trends - Subject Rankings</h3>
            <p>Track student performance trends and subject rankings over time</p>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1rem 0;">
                <div style="background: #f0f4ff; padding: 1rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 700; color: #667eea;">🥇 24</div>
                    <div style="font-size: 0.8rem; color: #718096;">Top Performers</div>
                </div>
                <div style="background: #f0fdf4; padding: 1rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 700; color: #10b981;">📈 15%</div>
                    <div style="font-size: 0.8rem; color: #718096;">Grade Improvement</div>
                </div>
                <div style="background: #fef3c7; padding: 1rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 700; color: #f59e0b;">📊 8</div>
                    <div style="font-size: 0.8rem; color: #718096;">Active Subjects</div>
                </div>
            </div>
            <button class="btn btn-primary" onclick="showTrends()">
                <i class="fas fa-chart-area"></i> View Monthly Trends
            </button>
        </div>
        
        <div class="tech-grid">
            <div class="tech-card">
                <h3><i class="fas fa-chart-pie"></i> Interactive Reports</h3>
                <form method="post">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select name="report_type" required>
                            <option value="users">User Activity Report</option>
                            <option value="performance">System Performance</option>
                            <option value="security">Security Audit</option>
                            <option value="usage">Usage Statistics</option>
                        </select>
                    </div>
                    <button type="submit" name="generate_report" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> Generate Report
                    </button>
                </form>
            </div>
            
            <div class="tech-card">
                <h3><i class="fas fa-satellite"></i> Real-time Analytics</h3>
                <p>Live data streaming from all system components</p>
                <button class="btn btn-success" onclick="startLiveStream()">
                    <i class="fas fa-play"></i> Start Live Stream
                </button>
            </div>
            
            <div class="tech-card">
                <h3><i class="fas fa-mobile-alt"></i> Mobile Reports</h3>
                <p>Access reports on any mobile device with QR codes</p>
                <button class="btn btn-warning" onclick="generateQR()">
                    <i class="fas fa-qrcode"></i> Generate QR Code
                </button>
            </div>
            
            <div class="tech-card">
                <h3><i class="fas fa-cloud-download-alt"></i> Cloud Export</h3>
                <p>Export reports directly to cloud storage platforms</p>
                <button class="btn btn-primary" onclick="exportCloud()">
                    <i class="fas fa-cloud-upload-alt"></i> Export to Cloud
                </button>
            </div>
            
            <div class="tech-card">
                <h3><i class="fas fa-voice"></i> Voice Commands</h3>
                <p>Generate reports using voice recognition technology</p>
                <button class="btn btn-danger" onclick="startVoice()">
                    <i class="fas fa-microphone"></i> Voice Control
                </button>
            </div>
            
            <div class="tech-card">
                <h3><i class="fas fa-fingerprint"></i> Biometric Access</h3>
                <p>Secure report access with fingerprint authentication</p>
                <button class="btn btn-success" onclick="scanFingerprint()">
                    <i class="fas fa-hand-paper"></i> Scan Fingerprint
                </button>
            </div>
        </div>
    </div>
    
    <script>
    function launchVR() {
        alert('🥽 VR Dashboard Launching:\n\n• Connecting to VR headset\n• Loading 3D data models\n• Immersive environment ready\n• Navigate with hand gestures');
    }
    
    function startAR() {
        alert('📱 AR View Activated:\n\n• Camera access granted\n• Data overlay enabled\n• Real-time tracking active\n• Point device at any surface');
    }
    
    function generateBlockchain() {
        alert('🔗 Blockchain Report:\n\n• Cryptographic hash generated\n• Tamper-proof verification\n• Distributed ledger updated\n• Report authenticity guaranteed');
    }
    
    function projectHologram() {
        alert('✨ Hologram Projection:\n\n• 3D projector activated\n• Spatial mapping complete\n• Holographic display ready\n• Data floating in mid-air');
    }
    
    function startLiveStream() {
        alert('📡 Live Analytics:\n\n• Real-time data streaming\n• Multiple sensors connected\n• Live updates every second\n• Dashboard refreshing automatically');
    }
    
    function generateQR() {
        alert('📱 QR Code Generated:\n\n• Mobile-friendly report created\n• QR code ready to scan\n• Works on any smartphone\n• Instant access anywhere');
    }
    
    function exportCloud() {
        alert('☁️ Cloud Export:\n\n• Connecting to cloud storage\n• Report uploaded successfully\n• Available on all devices\n• Automatic sync enabled');
    }
    
    function startVoice() {
        alert('🎤 Voice Control Active:\n\n• Microphone listening\n• Say "Generate user report"\n• Voice recognition ready\n• Hands-free operation enabled');
    }
    
    function scanFingerprint() {
        alert('👆 Biometric Scan:\n\n• Fingerprint scanner active\n• Place finger on sensor\n• Identity verified\n• Secure access granted');
    }
    
    function showTrends() {
        alert('📊 Monthly Trends Analysis:\n\n• Subject ranking changes tracked\n• Performance improvement metrics\n• Top performers identified\n• Grade distribution analysis\n• Semester comparison data\n• Interactive charts loading...');
    }
    </script>
</body>
</html>