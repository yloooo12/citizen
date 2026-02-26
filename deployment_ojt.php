<?php
session_start();

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$first_name = $_SESSION["first_name"] ?? "";
$last_name = $_SESSION["last_name"] ?? "";
$id_number = $_SESSION["id_number"] ?? "";
$email = $_SESSION["email"] ?? "";

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Check student eligibility - must be 4th year 1st semester
$student_id_number = $_SESSION['id_number'] ?? '';
$is_eligible = false;
$year_level = 'N/A';
$semester = 'N/A';

if ($student_id_number) {
    $conn2 = new mysqli("localhost", "root", "", "student_services");
    if (!$conn2->connect_error) {
        $result = $conn2->query("SELECT year_level, semester FROM student_subjects WHERE student_id='$student_id_number' ORDER BY id DESC LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $year_level = $row['year_level'] ?? 'N/A';
            $semester = $row['semester'] ?? 'N/A';
            
            // Check if student is 4th year 1st semester
            if ($year_level === '4th Year' && $semester === '1st') {
                $is_eligible = true;
            }
        }
        $conn2->close();
    }
}

$success = false;
$error_msg = "";
// Create tables if not exist
$conn->query("CREATE TABLE IF NOT EXISTS ojt_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(255),
    student_id VARCHAR(50),
    student_email VARCHAR(255),
    company_preference VARCHAR(255),
    company_address TEXT,
    preferred_schedule VARCHAR(100),
    skills TEXT,
    requirements_complete TINYINT DEFAULT 0,
    resume_file VARCHAR(255),
    parent_consent VARCHAR(255),
    enrollment_form VARCHAR(255),
    medical_cert VARCHAR(255),
    letter_inquiry VARCHAR(255),
    letter_response VARCHAR(255),
    application_letter VARCHAR(255),
    recommendation_letter VARCHAR(255),
    acceptance_letter VARCHAR(255),
    internship_plan VARCHAR(255),
    internship_contract_lspu VARCHAR(255),
    internship_contract_company VARCHAR(255),
    moa_draft VARCHAR(255),
    certificate_employment VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS dean_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    message TEXT,
    type VARCHAR(50),
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["student_name"])) {
    error_log("OJT Form submitted");
    $student_name = trim($_POST["student_name"]);
    $student_id = trim($_POST["student_id"]);
    $student_email = trim($_POST["student_email"]);
    error_log("Student: $student_name, ID: $student_id");
    
    // Handle file uploads
    $upload_dir = 'uploads/ojt_requirements/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $resume_file = '';
    $parent_consent = '';
    $enrollment_form = '';
    $medical_cert = '';
    $letter_inquiry = '';
    $letter_response = '';
    $application_letter = '';
    $recommendation_letter = '';
    $acceptance_letter = '';
    $internship_plan = '';
    $internship_contract_lspu = '';
    $internship_contract_company = '';
    $moa_draft = '';
    $certificate_employment = '';
    
    $files = ['resume_file', 'parent_consent', 'enrollment_form', 'medical_cert', 'letter_inquiry', 'letter_response', 'application_letter', 'recommendation_letter', 'acceptance_letter', 'internship_plan', 'internship_contract_lspu', 'internship_contract_company', 'moa_draft', 'certificate_employment'];
    
    foreach ($files as $file) {
        if (isset($_FILES[$file]) && $_FILES[$file]['error'] === 0) {
            $$file = $upload_dir . $student_id . '_' . $file . '_' . time() . '.' . pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES[$file]['tmp_name'], $$file);
        }
    }
    
    $requirements_complete = (count(array_filter([$resume_file, $parent_consent, $enrollment_form, $medical_cert, $letter_inquiry, $letter_response, $application_letter, $recommendation_letter, $acceptance_letter, $internship_plan, $internship_contract_lspu, $internship_contract_company, $moa_draft])) >= 13) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO ojt_requests (student_name, student_id, student_email, requirements_complete, resume_file, parent_consent, enrollment_form, medical_cert, letter_inquiry, letter_response, application_letter, recommendation_letter, acceptance_letter, internship_plan, internship_contract_lspu, internship_contract_company, moa_draft, certificate_employment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        error_log("Prepared statement created");
        $stmt->bind_param("ssssssssssssssssss", $student_name, $student_id, $student_email, $requirements_complete, $resume_file, $parent_consent, $enrollment_form, $medical_cert, $letter_inquiry, $letter_response, $application_letter, $recommendation_letter, $acceptance_letter, $internship_plan, $internship_contract_lspu, $internship_contract_company, $moa_draft, $certificate_employment);
        if ($stmt->execute()) {
            error_log("OJT request inserted successfully");
            // Notify Dean
            $submitted_docs = [];
            if ($resume_file) $submitted_docs[] = 'Student Resume';
            if ($parent_consent) $submitted_docs[] = 'Parent Consent (Notarized)';
            if ($enrollment_form) $submitted_docs[] = 'Enrollment/Registration Form/ID';
            if ($medical_cert) $submitted_docs[] = 'Medical Certificate';
            if ($letter_inquiry) $submitted_docs[] = 'Letter of Inquiry';
            if ($letter_response) $submitted_docs[] = 'Letter of Response';
            if ($application_letter) $submitted_docs[] = 'Application Letter';
            if ($recommendation_letter) $submitted_docs[] = 'Recommendation Letter';
            if ($acceptance_letter) $submitted_docs[] = 'Acceptance Letter';
            if ($internship_plan) $submitted_docs[] = 'Internship Plan/Time Frame';
            if ($internship_contract_lspu) $submitted_docs[] = 'Internship Contract - LSPU';
            if ($internship_contract_company) $submitted_docs[] = 'Internship Contract - Company';
            if ($moa_draft) $submitted_docs[] = 'MOA DRAFT';
            if ($certificate_employment) $submitted_docs[] = 'Certificate of Employment';
            
            $docs_list = implode(', ', $submitted_docs);
            $notification_msg = "Student $student_name ($student_id) has submitted OJT deployment request. Documents submitted: $docs_list";
            
            $conn->query("INSERT INTO dean_notifications (title, message, type, created_at) VALUES ('New OJT Deployment Request', '$notification_msg', 'ojt_request', NOW())");
            
            $success = true;
        } else {
            $error_msg = "Submission failed: " . $stmt->error;
            error_log("OJT submission error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $error_msg = "Database error: " . $conn->error;
        error_log("Prepare failed: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJT Deployment Request - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            color: #333;
            overflow: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        body.dark-mode { background: #1a202c; color: #e2e8f0; }
        body.dark-mode .main-content, body.dark-mode .footer, body.dark-mode #ojtModal .modal-content { background: #2d3748; }
        body.dark-mode .page-title, body.dark-mode #ojtModal h3, body.dark-mode #ojtModal label { color: #e2e8f0; }
        body.dark-mode .page-desc, body.dark-mode .footer { color: #cbd5e0; }
        body.dark-mode .tab { color: #cbd5e0; }
        body.dark-mode .tab.active { color: #667eea; }
        body.dark-mode .requirements-table th, body.dark-mode .process-table th { background: #374151; color: #667eea; }
        body.dark-mode .requirements-table td, body.dark-mode .process-table td { background: #2d3748; border-color: #4a5568; color: #e2e8f0; }
        body.dark-mode .process-table td.fee, body.dark-mode .process-table td.time, body.dark-mode .process-table td.person { background: #374151; }
        body.dark-mode #ojtModal input, body.dark-mode #ojtModal textarea, body.dark-mode #ojtModal select { background: #374151; border-color: #4a5568; color: #e2e8f0; }
        body.dark-mode #ojtModal .cancel-btn { background: #374151; color: #e2e8f0; }
        body.dark-mode .back-btn { background: #374151; color: #667eea; }
        body.dark-mode .back-btn:hover { background: #667eea; color: white; }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInModal {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .main-container {
            margin-left: 260px;
            margin-top: 65px;
            padding: 2rem 2.5rem;
            height: calc(100vh - 65px);
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }

        .main-container.collapsed {
            margin-left: 70px;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            animation: fadeIn 0.5s ease;
        }

        .page-header {
            margin-bottom: 2rem;
            border-left: 5px solid #667eea;
            padding-left: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: fadeIn 0.6s ease 0.1s both;
        }

        .back-btn {
            background: #f3f4f6;
            color: #667eea;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .back-btn:hover {
            background: #667eea;
            color: white;
        }

        .page-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .page-desc {
            font-size: 0.95rem;
            color: #718096;
        }

        .tabs {
            display: flex;
            margin: 2rem 0 1rem 0;
            border-bottom: 2px solid #e2e8f0;
            gap: 0.5rem;
            animation: fadeIn 0.6s ease 0.2s both;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            background: transparent;
            color: #718096;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab:hover {
            color: #667eea;
        }

        .tab-content {
            display: none;
            padding: 1.5rem 0;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        .requirements-table, .process-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .requirements-table th, .requirements-table td,
        .process-table th, .process-table td {
            border: 1px solid #e2e8f0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }

        .requirements-table th, .process-table th {
            background: #f0f4ff;
            color: #667eea;
            font-weight: 600;
            text-align: left;
        }

        .process-table td.fee, .process-table td.time, .process-table td.person {
            text-align: center;
            background: #f9fafb;
        }

        .submit-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin: 2rem auto;
            display: block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .footer {
            text-align: center;
            padding: 1rem;
            color: #718096;
            font-size: 0.75rem;
            background: white;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
            border-top: 1px solid #e8ecf4;
        }

        .footer.collapsed {
            margin-left: 70px;
        }

        #ojtModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        #ojtModal .modal-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: fadeInModal 0.3s ease;
        }

        #ojtModal .close-x {
            float: right;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
        }

        #ojtModal .close-x:hover {
            color: #667eea;
        }

        #ojtModal label {
            font-weight: 600;
            margin-top: 0.75rem;
            display: block;
            color: #2d3748;
        }

        #ojtModal input, #ojtModal textarea, #ojtModal select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            background: #f9fafb;
        }

        #ojtModal input:focus, #ojtModal textarea:focus, #ojtModal select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        #ojtModal .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        #ojtModal .cancel-btn {
            flex: 1;
            background: #f3f4f6;
            color: #374151;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        #ojtModal .submit-btn {
            flex: 1;
            margin: 0;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }

            .main-content {
                padding: 1.5rem;
            }

            .page-header {
                flex-wrap: wrap;
                padding-left: 1rem;
            }

            .page-logo {
                width: 40px;
                height: 40px;
            }

            .page-title {
                font-size: 1.25rem;
            }

            .page-desc {
                font-size: 0.85rem;
            }

            .footer {
                margin-left: 0;
            }

            .tabs {
                flex-direction: column;
                gap: 0;
            }

            .tab {
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
            }

            .requirements-table th, .requirements-table td,
            .process-table th, .process-table td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="main-content">
            <div class="page-header">
                <button class="back-btn" onclick="window.location.href='index.php'" title="Back to Services">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <img src="logo-ccs.webp" alt="CCS Logo" class="page-logo">
                <div>
                    <h1 class="page-title">OJT Deployment Request</h1>
                    <p class="page-desc">For students ready for On-the-Job Training deployment to partner companies.</p>
                </div>
            </div>

            <div class="tabs">
                <button class="tab active" onclick="showTab('avail')">Who may avail</button>
                <button class="tab" onclick="showTab('requirements')">Requirements</button>
                <button class="tab" onclick="showTab('steps')">Process Steps</button>
            </div>

            <div id="tab-avail" class="tab-content active">
                <ul style="margin-left: 1.5rem;">
                    <li>Students who have completed required academic units for OJT</li>
                    <li>Students with approved OJT application</li>
                    <li>Students ready for industry immersion</li>
                </ul>
            </div>

            <div id="tab-requirements" class="tab-content">
                <table class="requirements-table">
                    <tr>
                        <th>Requirements</th>
                        <th>Where to Secure</th>
                    </tr>
                    <tr>
                        <td>OJT Application Form</td>
                        <td>OJT Coordinator</td>
                    </tr>
                    <tr>
                        <td>Medical Certificate</td>
                        <td>Health Center/Clinic</td>
                    </tr>
                    <tr>
                        <td>Resume/CV</td>
                        <td>Student</td>
                    </tr>
                    <tr>
                        <td>Endorsement Letter</td>
                        <td>OJT Coordinator</td>
                    </tr>
                </table>
            </div>

            <div id="tab-steps" class="tab-content">
                <table class="process-table">
                    <tr>
                        <th>Step</th>
                        <th>Process</th>
                        <th>Fees</th>
                        <th>Time</th>
                        <th>Responsible</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>Student logs in to portal and submits OJT deployment request with company preference</td>
                        <td class="fee">N/A</td>
                        <td class="time">10 mins</td>
                        <td class="person">Student</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>System notifies OJT Coordinator. Coordinator reviews request and validates requirements online</td>
                        <td class="fee">N/A</td>
                        <td class="time">1-2 days</td>
                        <td class="person">OJT Coordinator</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Coordinator schedules general orientation. System sends notification to student with schedule</td>
                        <td class="fee">N/A</td>
                        <td class="time">1 day</td>
                        <td class="person">OJT Coordinator</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Student attends general orientation (online/face-to-face). Coordinator marks attendance in system</td>
                        <td class="fee">N/A</td>
                        <td class="time">4 hours</td>
                        <td class="person">Student/Coordinator</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Coordinator prepares endorsement and sends to partner company. System tracks communication</td>
                        <td class="fee">N/A</td>
                        <td class="time">2-3 days</td>
                        <td class="person">OJT Coordinator</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Company confirms acceptance. Coordinator issues travel order and deployment documents online</td>
                        <td class="fee">N/A</td>
                        <td class="time">2-3 days</td>
                        <td class="person">Company/Coordinator</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>Student receives deployment notification and reports to company. OJT officially starts</td>
                        <td class="fee">N/A</td>
                        <td class="time">1 day</td>
                        <td class="person">Student</td>
                    </tr>
                </table>
                <p style="margin-top: 1rem; padding: 1rem; background: #f0f4ff; border-radius: 8px; font-size: 0.9rem; color: #667eea;">
                    <i class="fas fa-info-circle"></i> <strong>Total Processing Time:</strong> 7-12 days | All steps are done online through this portal system.
                </p>
            </div>

            <?php if ($is_eligible): ?>
                <button class="submit-btn" onclick="showOjtModal()">
                    <i class="fas fa-paper-plane"></i> Submit OJT Deployment Request
                </button>
            <?php else: ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 1.5rem; margin: 2rem 0; text-align: center;">
                    <i class="fas fa-exclamation-triangle" style="color: #dc2626; font-size: 2rem; margin-bottom: 1rem;"></i>
                    <h3 style="color: #dc2626; margin-bottom: 0.5rem;">OJT Eligibility Required</h3>
                    <p style="color: #7f1d1d; margin-bottom: 1rem;">You must be enrolled as <strong>4th Year 1st Semester</strong> to submit OJT deployment requirements.</p>
                    <p style="color: #7f1d1d; font-size: 0.9rem;">Current Status: <strong><?php echo htmlspecialchars($year_level); ?> - <?php echo htmlspecialchars($semester); ?> Semester</strong></p>
                    <p style="color: #7f1d1d; font-size: 0.9rem; margin-top: 0.5rem;">Please update your profile to 4th Year 1st Semester to proceed with OJT deployment.</p>
                </div>
                <button class="submit-btn" onclick="showEligibilityAlert()" style="background: #9ca3af; cursor: not-allowed;">
                    <i class="fas fa-lock"></i> Submit OJT Deployment Request (Not Eligible)
                </button>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer" id="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - Department of Computer Studies</p>
        <p>INTEGRITY • PROFESSIONALISM • INNOVATION</p>
    </footer>

    <div id="ojtModal">
        <div class="modal-content">
            <span class="close-x" onclick="closeOjtModal()">&times;</span>
            <h3>OJT Deployment Request Form</h3>
            <form method="post" enctype="multipart/form-data">
                <?php
                // Check existing submission
                $existing_submission = null;
                $result = $conn->query("SELECT * FROM ojt_requests WHERE student_id='$id_number' ORDER BY created_at DESC LIMIT 1");
                if ($result && $result->num_rows > 0) {
                    $existing_submission = $result->fetch_assoc();
                }
                ?>
                <label>Full Name *</label>
                <input type="text" name="student_name" value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>" required readonly>

                <label>Student ID *</label>
                <input type="text" name="student_id" value="<?php echo htmlspecialchars($id_number); ?>" required readonly>

                <label>Email *</label>
                <input type="email" name="student_email" value="<?php echo htmlspecialchars($email); ?>" required readonly>



                <div style="margin: 1.5rem 0; padding: 1.5rem; background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%); border-radius: 12px; border: 1px solid #c7d2fe;">
                    <h4 style="color: #4338ca; margin-bottom: 1.5rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-clipboard-check"></i> OJT Requirements Checklist</h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #10b981; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                <input type="checkbox" id="resume_file_check" onchange="toggleFileInput('resume_file')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['resume_file'])) ? 'checked' : ''; ?>>
                                <span>1. Student Resume <?php echo ($existing_submission && !empty($existing_submission['resume_file'])) ? '✓ Submitted' : ''; ?></span>
                            </label>
                            <input type="file" name="resume_file" id="resume_file_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="parent_consent_check" onchange="toggleFileInput('parent_consent')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['parent_consent'])) ? 'checked' : ''; ?>>
                                    <span>2. Parent Consent (Notarized) <?php echo ($existing_submission && !empty($existing_submission['parent_consent'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <a href="https://docs.google.com/document/d/1ah3n0Z4eDR-OH4ht82sp9hunhLJlrZGt/edit" target="_blank" style="color: #3b82f6; font-size: 0.8rem; text-decoration: none; margin-left: 2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-download"></i> Download Template
                                </a>
                            </div>
                            <input type="file" name="parent_consent" id="parent_consent_file" accept=".pdf,.jpg,.jpeg,.png" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #3b82f6; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                <input type="checkbox" id="enrollment_form_check" onchange="toggleFileInput('enrollment_form')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['enrollment_form'])) ? 'checked' : ''; ?>>
                                <span>3. Enrollment/Registration Form/ID <?php echo ($existing_submission && !empty($existing_submission['enrollment_form'])) ? '✓ Submitted' : ''; ?></span>
                            </label>
                            <input type="file" name="enrollment_form" id="enrollment_form_file" accept=".pdf,.jpg,.jpeg,.png" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #ef4444; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                <input type="checkbox" id="medical_cert_check" onchange="toggleFileInput('medical_cert')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['medical_cert'])) ? 'checked' : ''; ?>>
                                <span>4. Medical Certificate <?php echo ($existing_submission && !empty($existing_submission['medical_cert'])) ? '✓ Submitted' : ''; ?></span>
                            </label>
                            <input type="file" name="medical_cert" id="medical_cert_file" accept=".pdf,.jpg,.jpeg,.png" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #8b5cf6; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="letter_inquiry_check" onchange="toggleFileInput('letter_inquiry')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['letter_inquiry'])) ? 'checked' : ''; ?>>
                                    <span>5. Letter of Inquiry <?php echo ($existing_submission && !empty($existing_submission['letter_inquiry'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <a href="https://docs.google.com/document/d/1Ot1jAFHfxVonej63nPtnG1nRhb6hOWJL/edit" target="_blank" style="color: #3b82f6; font-size: 0.8rem; text-decoration: none; margin-left: 2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-download"></i> Download Template
                                </a>
                            </div>
                            <input type="file" name="letter_inquiry" id="letter_inquiry_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #06b6d4; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="letter_response_check" onchange="toggleFileInput('letter_response')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['letter_response'])) ? 'checked' : ''; ?>>
                                    <span>6. Letter of Response <?php echo ($existing_submission && !empty($existing_submission['letter_response'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <a href="https://docs.google.com/document/d/1v9M90HxhltYJTQQOYSTlGcJsNnxaIASv/edit#heading=h.gjdgxs" target="_blank" style="color: #3b82f6; font-size: 0.8rem; text-decoration: none; margin-left: 2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-download"></i> Download Template
                                </a>
                            </div>
                            <input type="file" name="letter_response" id="letter_response_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #84cc16; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="application_letter_check" onchange="toggleFileInput('application_letter')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['application_letter'])) ? 'checked' : ''; ?>>
                                    <span>7. Application Letter <?php echo ($existing_submission && !empty($existing_submission['application_letter'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <a href="https://docs.google.com/document/d/1WBYEgtEDT5GdkzCNnjW6efOQsO8yqPJe/edit" target="_blank" style="color: #3b82f6; font-size: 0.8rem; text-decoration: none; margin-left: 2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-download"></i> Download Template
                                </a>
                            </div>
                            <input type="file" name="application_letter" id="application_letter_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #f97316; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="recommendation_letter_check" onchange="toggleFileInput('recommendation_letter')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['recommendation_letter'])) ? 'checked' : ''; ?>>
                                    <span>8. Recommendation Letter <?php echo ($existing_submission && !empty($existing_submission['recommendation_letter'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <a href="https://docs.google.com/document/d/1YNhcMRmj4kUzLksw5EZ_f262eru1DE9c/edit" target="_blank" style="color: #3b82f6; font-size: 0.8rem; text-decoration: none; margin-left: 2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-download"></i> Download Template
                                </a>
                            </div>
                            <input type="file" name="recommendation_letter" id="recommendation_letter_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #ec4899; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="acceptance_letter_check" onchange="toggleFileInput('acceptance_letter')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['acceptance_letter'])) ? 'checked' : ''; ?>>
                                    <span>9. Acceptance Letter <?php echo ($existing_submission && !empty($existing_submission['acceptance_letter'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <a href="https://docs.google.com/document/d/1wY0ivychlpOUxdh9D_vuHB4qnWnM66gO/edit" target="_blank" style="color: #3b82f6; font-size: 0.8rem; text-decoration: none; margin-left: 2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-download"></i> Download Template
                                </a>
                            </div>
                            <input type="file" name="acceptance_letter" id="acceptance_letter_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #14b8a6; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="internship_plan_check" onchange="toggleFileInput('internship_plan')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['internship_plan'])) ? 'checked' : ''; ?>>
                                    <span>10. Internship Plan/Time Frame <?php echo ($existing_submission && !empty($existing_submission['internship_plan'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <a href="https://docs.google.com/document/d/1vtk9a5FjXmLcGaPPRu8Y8ObUmWkikxJU/edit" target="_blank" style="color: #3b82f6; font-size: 0.8rem; text-decoration: none; margin-left: 2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-download"></i> Download Template
                                </a>
                            </div>
                            <input type="file" name="internship_plan" id="internship_plan_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #6366f1; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="internship_contract_lspu_check" onchange="toggleFileInput('internship_contract_lspu')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['internship_contract_lspu'])) ? 'checked' : ''; ?>>
                                    <span>11. Internship Contract - LSPU <?php echo ($existing_submission && !empty($existing_submission['internship_contract_lspu'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <a href="https://docs.google.com/document/d/1DhqIangxgB4ITONJdcH8-08O8oz26JPQ/edit" target="_blank" style="color: #3b82f6; font-size: 0.8rem; text-decoration: none; margin-left: 2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-download"></i> Download Template
                                </a>
                            </div>
                            <input type="file" name="internship_contract_lspu" id="internship_contract_lspu_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #a855f7; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="internship_contract_company_check" onchange="toggleFileInput('internship_contract_company')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['internship_contract_company'])) ? 'checked' : ''; ?>>
                                    <span>12. Internship Contract - Company <?php echo ($existing_submission && !empty($existing_submission['internship_contract_company'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <a href="https://docs.google.com/document/d/1SiVs1sHDBtQq6CVTK5zKy7lb_pfrYk0X/edit" target="_blank" style="color: #3b82f6; font-size: 0.8rem; text-decoration: none; margin-left: 2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-download"></i> Download Template
                                </a>
                            </div>
                            <input type="file" name="internship_contract_company" id="internship_contract_company_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #22c55e; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                    <input type="checkbox" id="moa_draft_check" onchange="toggleFileInput('moa_draft')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['moa_draft'])) ? 'checked' : ''; ?>>
                                    <span>13. MOA DRAFT <?php echo ($existing_submission && !empty($existing_submission['moa_draft'])) ? '✓ Submitted' : ''; ?></span>
                                </label>
                                <span style="color: #6b7280; font-size: 0.8rem; margin-left: 2rem; font-style: italic;">
                                    Template to be follow
                                </span>
                            </div>
                            <input type="file" name="moa_draft" id="moa_draft_file" accept=".pdf,.doc,.docx" style="display: none; margin-top: 0.5rem;">
                        </div>
                        
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b; min-height: 80px; display: flex; flex-direction: column; justify-content: space-between;">
                            <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-weight: 600; color: #1f2937; line-height: 1.4;">
                                <input type="checkbox" id="certificate_employment_check" onchange="toggleFileInput('certificate_employment')" style="transform: scale(1.2); margin-top: 2px;" <?php echo ($existing_submission && !empty($existing_submission['certificate_employment'])) ? 'checked' : ''; ?>>
                                <span>14. Certificate of Employment <em>(if EMPLOYED)</em> <?php echo ($existing_submission && !empty($existing_submission['certificate_employment'])) ? '✓ Submitted' : ''; ?></span>
                            </label>
                            <input type="file" name="certificate_employment" id="certificate_employment_file" accept=".pdf,.jpg,.jpeg,.png" style="display: none; margin-top: 0.5rem;">
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(59, 130, 246, 0.1); border-radius: 8px; border: 1px solid #93c5fd;">
                        <p style="color: #1e40af; font-size: 0.9rem; margin: 0;"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Check the documents you have ready and upload them. You can submit partial requirements and complete them later.</p>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeOjtModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
            
            if(tab === 'avail') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('tab-avail').classList.add('active');
            } else if(tab === 'requirements') {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('tab-requirements').classList.add('active');
            } else if(tab === 'steps') {
                document.querySelectorAll('.tab')[2].classList.add('active');
                document.getElementById('tab-steps').classList.add('active');
            }
        }

        function showOjtModal() {
            <?php if (!$is_eligible): ?>
            showEligibilityAlert();
            return;
            <?php endif; ?>
            document.getElementById('ojtModal').style.display = 'flex';
        }
        
        function showEligibilityAlert() {
            alert('You must be enrolled as 4th Year 1st Semester to submit OJT deployment requirements.\n\nCurrent Status: <?php echo addslashes($year_level . ' - ' . $semester); ?> Semester\n\nPlease update your profile first.');
        }

        function closeOjtModal() {
            document.getElementById('ojtModal').style.display = 'none';
        }

        function toggleFileInput(type) {
            const checkbox = document.getElementById(type + '_check');
            const fileInput = document.getElementById(type + '_file');
            const span = checkbox.nextElementSibling;
            
            // Check if document was already submitted
            const isSubmitted = span.textContent.includes('✓ Submitted');
            
            if (checkbox.checked) {
                fileInput.style.display = 'block';
                fileInput.style.animation = 'fadeIn 0.3s ease';
            } else {
                // If unchecking a submitted document, show confirmation
                if (isSubmitted) {
                    if (confirm('Are you sure you want to uncheck this document and attach a new file? This will replace your previously submitted document.')) {
                        fileInput.style.display = 'none';
                        fileInput.value = '';
                    } else {
                        // Re-check the checkbox if user cancels
                        checkbox.checked = true;
                        return;
                    }
                } else {
                    fileInput.style.display = 'none';
                    fileInput.value = '';
                }
            }
        }
        
        <?php if($success): ?>
        alert('OJT deployment request submitted successfully! The Dean has been notified and will review your submission.');
        window.location.href = 'deployment_ojt.php';
        <?php endif; ?>
        
        <?php if($error_msg): ?>
        alert('ERROR: <?php echo addslashes($error_msg); ?>');
        console.log('Error details: <?php echo addslashes($error_msg); ?>');
        <?php endif; ?>
        
        <?php if($_SERVER["REQUEST_METHOD"] == "POST" && !$success && !$error_msg): ?>
        alert('Form submitted but no response. Check console for details.');
        console.log('POST data received but no success/error flag set');
        <?php endif; ?>
    </script>

    <?php include 'chatbot.php'; ?>
</body>
</html>
<?php $conn->close(); ?>
