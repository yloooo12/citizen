<?php
session_start();

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$first_name = $_SESSION["first_name"];
$last_name = isset($_SESSION["last_name"]) ? $_SESSION["last_name"] : '';
$role = "Student"; 

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Get unread notification count
$notif_count = 5; // Test value - change to 0 after testing
try {
    $conn = new mysqli("localhost", "root", "", "citizenproj");
    if (!$conn->connect_error) {
        $user_id = $_SESSION["user_id"];
        $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
        if ($result) {
            $row = $result->fetch_assoc();
            $notif_count = $row['count'];
        }
        $conn->close();
    }
} catch (Exception $e) {
    // Keep test value
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen's Charter - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            height: 100vh;
            color: #333;
            overflow: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        body.dark-mode {
            background: #1a202c;
            color: #e2e8f0;
        }

        body.dark-mode .hero-section,
        body.dark-mode .service-card,
        body.dark-mode .footer {
            background: #2d3748;
            border-color: #4a5568;
        }

        body.dark-mode .hero-section h1,
        body.dark-mode .service-title,
        body.dark-mode .section-title,
        body.dark-mode .stat-box .stat-value {
            color: #e2e8f0;
        }

        body.dark-mode .hero-section p,
        body.dark-mode .service-desc,
        body.dark-mode .stat-box .stat-label,
        body.dark-mode .footer {
            color: #cbd5e0;
        }

        body.dark-mode .stat-box {
            background: #374151;
            border-color: #4a5568;
        }

        .main-container {
            margin-left: 260px;
            margin-top: 65px;
            padding: 2rem 2.5rem;
            height: calc(100vh - 65px);
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .main-container.collapsed {
            margin-left: 70px;
        }

        .hero-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            animation: fadeInDown 0.6s ease;
            border-left: 5px solid #667eea;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-section h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .hero-section h1 span {
            color: #667eea;
        }

        .hero-section p {
            font-size: 0.95rem;
            color: #718096;
            margin-bottom: 1rem;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-box {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .stat-box i {
            font-size: 1.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-box .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }

        .stat-box .stat-label {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 0.25rem;
        }

        .section-title {
            color: #2d3748;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #667eea;
            display: inline-block;
            animation: fadeInUp 0.6s ease 0.2s both;
        }

        .section-header {
            margin-bottom: 1.5rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .services-grid .service-card:nth-child(1) { animation: fadeInUp 0.6s ease 0.3s both; }
        .services-grid .service-card:nth-child(2) { animation: fadeInUp 0.6s ease 0.4s both; }
        .services-grid .service-card:nth-child(3) { animation: fadeInUp 0.6s ease 0.5s both; }
        .services-grid .service-card:nth-child(4) { animation: fadeInUp 0.6s ease 0.6s both; }
        .services-grid .service-card:nth-child(5) { animation: fadeInUp 0.6s ease 0.7s both; }
        .services-grid .service-card:nth-child(6) { animation: fadeInUp 0.6s ease 0.8s both; }

        .service-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem 1.5rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: #667eea;
            transform: scaleY(0);
            transition: transform 0.4s ease;
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(102,126,234,0.2);
            border-color: #667eea;
        }

        .service-card:hover::before {
            transform: scaleY(1);
        }

        .service-icon {
            width: 55px;
            height: 55px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .service-card:hover .service-icon {
            background: #667eea;
            transform: scale(1.1);
        }

        .service-card:hover .service-icon i {
            color: white;
        }

        .service-icon i {
            font-size: 1.5rem;
            color: #667eea;
            transition: color 0.3s ease;
        }

        .service-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .service-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 0.75rem;
            background: #e6f4ea;
            color: #1e7e34;
        }

        .service-desc {
            color: #718096;
            line-height: 1.5;
            font-size: 0.875rem;
        }

        .footer {
            text-align: center;
            padding: 1rem;
            color: #718096;
            font-size: 0.75rem;
            background: white;
            margin-left: 260px;
            transition: all 0.3s ease;
            border-top: 1px solid #e8ecf4;
        }

        .footer.collapsed {
            margin-left: 70px;
        }



        @media (max-width: 992px) {
            .services-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
                margin-top: 55px;
            }

            .footer {
                margin-left: 0;
                padding: 0.75rem;
                font-size: 0.7rem;
            }



            .hero-section {
                padding: 1.5rem;
            }

            .hero-section h1 {
                font-size: 1.3rem;
            }

            .hero-section p {
                font-size: 0.85rem;
            }

            .quick-stats {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .section-title {
                font-size: 1.25rem;
            }

            .services-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 0.75rem;
                margin-top: 55px;
            }

            .hero-section {
                padding: 1rem;
            }

            .hero-section h1 {
                font-size: 1rem;
            }

            .hero-section p {
                font-size: 0.75rem;
            }

            .stat-box {
                padding: 0.6rem;
            }

            .stat-box .stat-value {
                font-size: 1.1rem;
            }

            .stat-box .stat-label {
                font-size: 0.7rem;
            }

            .service-card {
                padding: 1rem;
            }

            .service-icon {
                width: 45px;
                height: 45px;
            }

            .service-icon i {
                font-size: 1.3rem;
            }

            .service-title {
                font-size: 0.9rem;
            }

            .service-desc {
                font-size: 0.8rem;
            }

            .footer {
                font-size: 0.65rem;
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="section-header">
            <h2 class="section-title">Student Services</h2>
        </div>
        <div class="services-grid">
            <a href="inc_removal.php" class="service-card">
                <div class="service-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3 class="service-title">INC / Removal Exam</h3>
                <p class="service-desc">Complete incomplete grades and remove failing marks through scheduled examinations.</p>
                <span class="service-status">Available</span>
            </a>

            <a href="crediting.php" class="service-card">
                <div class="service-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3 class="service-title">Subject Crediting</h3>
                <p class="service-desc">Credit previously taken subjects for transferees and shifters.</p>
                <span class="service-status">Available</span>
            </a>

            <a href="admission_interview.php" class="service-card">
                <div class="service-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3 class="service-title">Admission & Interview</h3>
                <p class="service-desc">Apply for admission as new students or transferees to the program.</p>
                <span class="service-status">Available</span>
            </a>

            <a href="unscheduled_subjects.php" class="service-card">
                <div class="service-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 class="service-title">Unscheduled Subjects</h3>
                <p class="service-desc">Request special subject offerings for irregular students and special cases.</p>
                <span class="service-status">Available</span>
            </a>

            <a href="deployment_ojt.php" class="service-card">
                <div class="service-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3 class="service-title">OJT Deployment</h3>
                <p class="service-desc">Get deployed for On-the-Job Training and practicum requirements.</p>
                <span class="service-status">Available</span>
            </a>

            <!-- <a href="dropping_subject.php" class="service-card">
                <div class="service-icon">
                    <i class="fas fa-minus-circle"></i>
                </div>
                <h3 class="service-title">Subject Dropping</h3>
                <p class="service-desc">Officially drop enrolled subjects with proper approval and documentation.</p>
                <span class="service-status">Available</span>
            </a> -->
        </div>
    </main>

    <footer class="footer" id="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - Department of Computer Studies</p>
        <p>INTEGRITY • PROFESSIONALISM • INNOVATION</p>
    </footer>

    <?php include 'chatbot.php'; ?>
</body>
</html>