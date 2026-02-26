<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "secretary") {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? "Secretary";

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$total_interviews = $conn->query("SELECT COUNT(*) as count FROM admission_interviews")->fetch_assoc()['count'];
$pending_interviews = $conn->query("SELECT COUNT(*) as count FROM admission_interviews WHERE status='pending'")->fetch_assoc()['count'];
$scheduled_interviews = $conn->query("SELECT COUNT(*) as count FROM admission_interviews WHERE status='scheduled'")->fetch_assoc()['count'];

$total_crediting = $conn->query("SELECT COUNT(*) as count FROM program_head_crediting")->fetch_assoc()['count'];
$pending_crediting = $conn->query("SELECT COUNT(*) as count FROM program_head_crediting WHERE status IN ('pending', 'warning')")->fetch_assoc()['count'];
$approved_crediting = $conn->query("SELECT COUNT(*) as count FROM program_head_crediting WHERE status='dean_approved'")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem 2.5rem; height: calc(100vh - 65px); overflow-y: auto; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .card h2 { font-size: 1.5rem; color: #2d3748; margin-bottom: 1.5rem; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .service-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s ease; cursor: pointer; }
        .service-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .card-icon { font-size: 3rem; margin-bottom: 1rem; }
        .service-card h3 { color: #2d3748; margin-bottom: 0.5rem; }
        .service-card p { color: #718096; font-size: 0.9rem; }
        @media (max-width: 768px) { .main-container { margin-left: 0; padding: 1rem; } }
    </style>
</head>
<body>
    <?php include 'secretary_navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="card">
            <h2><i class="fas fa-chart-line"></i> Dashboard Overview</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 12px; color: white;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $total_interviews; ?></div>
                    <div style="font-size: 0.9rem; opacity: 0.9;"><i class="fas fa-microphone"></i> Total Interviews</div>
                </div>
                <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 1.5rem; border-radius: 12px; color: white;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $pending_interviews; ?></div>
                    <div style="font-size: 0.9rem; opacity: 0.9;"><i class="fas fa-clock"></i> Pending Interviews</div>
                </div>
                <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 1.5rem; border-radius: 12px; color: white;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $scheduled_interviews; ?></div>
                    <div style="font-size: 0.9rem; opacity: 0.9;"><i class="fas fa-check-circle"></i> Scheduled Interviews</div>
                </div>
                <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 1.5rem; border-radius: 12px; color: white;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $total_crediting; ?></div>
                    <div style="font-size: 0.9rem; opacity: 0.9;"><i class="fas fa-clipboard-check"></i> Total Crediting</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 12px; border: 1px solid #e5e7eb;">
                    <h3 style="font-size: 1.1rem; color: #2d3748; margin-bottom: 1rem;"><i class="fas fa-chart-bar"></i> Interview Status</h3>
                    <div style="height: 250px;"><canvas id="interviewChart"></canvas></div>
                </div>
                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 12px; border: 1px solid #e5e7eb;">
                    <h3 style="font-size: 1.1rem; color: #2d3748; margin-bottom: 1rem;"><i class="fas fa-chart-bar"></i> Crediting Status</h3>
                    <div style="height: 250px;"><canvas id="creditingChart"></canvas></div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        const interviewCtx = document.getElementById('interviewChart').getContext('2d');
        new Chart(interviewCtx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Scheduled'],
                datasets: [{
                    label: 'Interviews',
                    data: [<?php echo $pending_interviews; ?>, <?php echo $scheduled_interviews; ?>],
                    backgroundColor: ['#f59e0b', '#10b981'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
        
        const creditingCtx = document.getElementById('creditingChart').getContext('2d');
        new Chart(creditingCtx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Approved'],
                datasets: [{
                    label: 'Requests',
                    data: [<?php echo $pending_crediting; ?>, <?php echo $approved_crediting; ?>],
                    backgroundColor: ['#f59e0b', '#10b981'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
        });
    </script>
</body>
</html>
