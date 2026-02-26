<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? '';
$last_name = $_SESSION["last_name"] ?? '';

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Tools - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; overflow: hidden; transition: background 0.3s ease; }
        
        body.dark-mode { background: #1a202c; color: #e2e8f0; }
        
        .main-container {
            margin-left: 260px;
            margin-top: 65px;
            padding: 2rem;
            height: calc(100vh - 65px);
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        .main-container.collapsed { margin-left: 70px; }
        
        .tools-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-header {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        body.dark-mode .page-header { background: #2d3748; border-color: #4a5568; }
        
        .page-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 0.5rem;
        }
        
        body.dark-mode .page-header h1 { color: #e2e8f0; }
        
        .page-header p {
            font-size: 1rem;
            color: #666;
        }
        
        body.dark-mode .page-header p { color: #cbd5e0; }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .tool-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        body.dark-mode .tool-card { background: #2d3748; border-color: #4a5568; }
        
        .tool-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .tool-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            color: white;
        }
        
        .ai-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .schedule-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .library-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .career-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .study-icon { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .gpa-icon { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        
        .tool-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #000;
            margin-bottom: 0.75rem;
        }
        
        body.dark-mode .tool-card h3 { color: #e2e8f0; }
        
        .tool-card p {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        body.dark-mode .tool-card p { color: #cbd5e0; }
        
        .tool-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            background: #667eea;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .tool-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .tool-modal.show { display: flex; }
        
        .tool-modal-content {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 550px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            animation: scaleIn 0.3s ease;
        }
        
        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        body.dark-mode .tool-modal-content { background: #2d3748; }
        
        .tool-modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: none;
            border: none;
            font-size: 1.75rem;
            cursor: pointer;
            color: #666;
            transition: color 0.2s;
        }
        
        .tool-modal-close:hover { color: #ef4444; }
        body.dark-mode .tool-modal-close { color: #cbd5e0; }
        
        .tool-modal h3 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 1.5rem;
        }
        
        body.dark-mode .tool-modal h3 { color: #e2e8f0; }
        
        .tool-input-group {
            margin-bottom: 1.25rem;
        }
        
        .tool-input-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        body.dark-mode .tool-input-group label { color: #e2e8f0; }
        
        .tool-input-group input,
        .tool-input-group select {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }
        
        .tool-input-group input:focus,
        .tool-input-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        body.dark-mode .tool-input-group input,
        body.dark-mode .tool-input-group select {
            background: #374151;
            border-color: #4a5568;
            color: #e2e8f0;
        }
        
        .tool-btn {
            width: 100%;
            padding: 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tool-btn:hover {
            background: #5568d3;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .tool-result {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
        }
        
        body.dark-mode .tool-result { background: #1e3a5f; border-color: #2563eb; }
        
        .tool-result h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0369a1;
            margin-bottom: 0.75rem;
        }
        
        body.dark-mode .tool-result h4 { color: #60a5fa; }
        
        .tool-result p {
            font-size: 0.9rem;
            color: #075985;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }
        
        body.dark-mode .tool-result p { color: #93c5fd; }
        
        @media (max-width: 768px) {
            .main-container { margin-left: 0; padding: 1rem; margin-top: 55px; }
            .page-header { padding: 1.5rem; }
            .page-header h1 { font-size: 1.5rem; }
            .tools-grid { grid-template-columns: 1fr; gap: 1rem; }
            .tool-card { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main class="main-container" id="mainContainer">
        <div class="tools-wrapper">
            <div class="page-header">
                <i class="fas fa-robot"></i>
                <h1>AI-Powered University Tools</h1>
                <p>Smart solutions to enhance your academic journey</p>
            </div>
            
            <div class="tools-grid">
                <div class="tool-card" onclick="openTool('gradePredictor')">
                    <div class="tool-icon ai-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Grade Predictor</h3>
                    <p>AI predicts your final grade based on current performance using advanced algorithms</p>
                    <span class="tool-badge">AI Powered</span>
                </div>
                
                <div class="tool-card" onclick="openTool('scheduleOptimizer')">
                    <div class="tool-icon schedule-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Schedule Optimizer</h3>
                    <p>Smart study schedule based on your workload and priorities</p>
                    <span class="tool-badge">Smart AI</span>
                </div>
                
                <div class="tool-card" onclick="openTool('libraryAssistant')">
                    <div class="tool-icon library-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h3>Virtual Library</h3>
                    <p>AI assistant for finding academic resources instantly</p>
                    <span class="tool-badge">24/7 Access</span>
                </div>
                
                <div class="tool-card" onclick="openTool('careerAnalyzer')">
                    <div class="tool-icon career-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>Career Analyzer</h3>
                    <p>Recommends career paths based on your performance and interests</p>
                    <span class="tool-badge">ML Powered</span>
                </div>
                
                <div class="tool-card" onclick="openTool('studyMatcher')">
                    <div class="tool-icon study-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Study Matcher</h3>
                    <p>Connect with students in similar courses for group study</p>
                    <span class="tool-badge">Social AI</span>
                </div>
                
                <div class="tool-card" onclick="openTool('gpaCalculator')">
                    <div class="tool-icon gpa-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3>GPA Calculator</h3>
                    <p>Real-time GPA calculation and academic tracking</p>
                    <span class="tool-badge">Instant</span>
                </div>
            </div>
        </div>
    </main>
    
    <div class="tool-modal" id="toolModal">
        <div class="tool-modal-content">
            <button class="tool-modal-close" onclick="closeTool()">&times;</button>
            <div id="toolContent"></div>
        </div>
    </div>
    <script src="ai_tools_scripts.js"></script>
</body>
</html>
