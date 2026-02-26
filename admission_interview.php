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

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["student_name"])) {
    $student_name = trim($_POST["student_name"]);
    $student_id = trim($_POST["student_id"]);
    $student_email = trim($_POST["student_email"]);
    $contact_number = trim($_POST["contact_number"]);

    // Debug: Show what we're trying to insert
    error_log("Attempting insert: name=$student_name, id=$student_id, email=$student_email, phone=$contact_number");
    
    $stmt = $conn->prepare("INSERT INTO admission_interviews (student_name, student_id, email, phone, status) VALUES (?, ?, ?, ?, 'pending')");
    if (!$stmt) {
        $_SESSION['error_msg'] = "Prepare failed: " . $conn->error;
        header("Location: admission_interview.php");
        exit;
    }
    
    $stmt->bind_param("ssss", $student_name, $student_id, $student_email, $contact_number);
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        
        // Verify insert
        $verify = $conn->query("SELECT id FROM admission_interviews WHERE id=$insert_id");
        if (!$verify || $verify->num_rows == 0) {
            $_SESSION['error_msg'] = "Data was not saved to database";
            header("Location: admission_interview.php");
            exit;
        }
        
        // Create academic alert for interview request
        $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, alert_type, is_resolved) 
                      VALUES ('$student_id', 'Admission Interview Request', 'PENDING', 'B.S. Information Technology', 'Interview request submitted', 'Waiting for secretary to send interview schedule', 'Admission Office', 'INTERVIEW', 0)
                      ON DUPLICATE KEY UPDATE is_resolved=0, reason='Interview request submitted'");
        
        // Send email notification
        require_once 'send_email_interview.php';
        $email_sent = sendInterviewRequestEmail($student_email, $student_name);
        error_log("Email send result: " . ($email_sent ? 'SUCCESS' : 'FAILED') . " to $student_email");
        
        // Send notification and email to secretary
        $sec_query = "INSERT INTO notifications (user_id, message, type, is_read) 
                      SELECT id_number, '🔔 New interview request from $student_name ($student_id)', 'interview_request', 0 
                      FROM users WHERE user_type='secretary'";
        $sec_result = $conn->query($sec_query);
        error_log("Secretary notification: " . ($sec_result ? 'SUCCESS' : 'FAILED - ' . $conn->error));
        error_log("Secretary rows affected: " . $conn->affected_rows);
        
        // Send email to secretary
        $sec_email_result = $conn->query("SELECT email, first_name FROM users WHERE user_type='secretary'");
        if ($sec_email_result && $sec_email_result->num_rows > 0) {
            while ($sec_row = $sec_email_result->fetch_assoc()) {
                require_once 'PHPMailer/src/Exception.php';
                require_once 'PHPMailer/src/PHPMailer.php';
                require_once 'PHPMailer/src/SMTP.php';
                require_once 'email_config.php';
                
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = SMTP_HOST;
                    $mail->SMTPAuth = SMTP_AUTH;
                    if ($mail->SMTPAuth) {
                        $mail->Username = SMTP_USERNAME;
                        $mail->Password = SMTP_PASSWORD;
                    }
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = SMTP_PORT;
                    $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
                    
                    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    $mail->addAddress($sec_row['email'], $sec_row['first_name']);
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'New Interview Request - Action Required';
                    $mail->Body = "<h2 style='color: #667eea;'>🔔 New Interview Request</h2>
                                   <p>Dear {$sec_row['first_name']},</p>
                                   <p>A new admission interview request has been submitted.</p>
                                   <div style='background: #f0f4ff; padding: 15px; border-left: 4px solid #667eea; margin: 15px 0;'>
                                       <p><strong>Student Name:</strong> $student_name</p>
                                       <p><strong>Student ID:</strong> $student_id</p>
                                       <p><strong>Email:</strong> $student_email</p>
                                       <p><strong>Contact:</strong> $contact_number</p>
                                   </div>
                                   <p>Please log in to the secretary portal to schedule the interview.</p>
                                   <br><p>Best regards,<br>LSPU CCS System</p>";
                    
                    $mail->send();
                } catch (Exception $e) {}
            }
        }
        
        // Send notification to student
        $notif_query1 = "INSERT INTO notifications (user_id, message, type, is_read) 
                         VALUES ('$student_id', '📋 Interview request submitted successfully! Secretary will send you the schedule soon.', 'interview_submitted', 0)";
        $notif_result1 = $conn->query($notif_query1);
        error_log("Notification 1 insert: " . ($notif_result1 ? 'SUCCESS' : 'FAILED - ' . $conn->error));
        
        // Create alert notification
        $notif_query2 = "INSERT INTO notifications (user_id, message, type, is_read) 
                         VALUES ('$student_id', '⚠️ Academic Alert: Admission Interview Request - PENDING. Waiting for secretary to send interview schedule.', 'academic_alert', 0)";
        $notif_result2 = $conn->query($notif_query2);
        error_log("Notification 2 insert: " . ($notif_result2 ? 'SUCCESS' : 'FAILED - ' . $conn->error));
        error_log("Student ID for notifications: $student_id");
        
        error_log("Insert successful with ID: $insert_id");
        $_SESSION['success_msg'] = 'Request submitted successfully! (ID: ' . $insert_id . ') Secretary will send you the interview schedule.';
        header("Location: admission_interview.php");
        exit;
    } else {
        error_log("Execute failed: " . $stmt->error);
        $_SESSION['error_msg'] = "Submission failed: " . $stmt->error . " | Errno: " . $stmt->errno;
        header("Location: admission_interview.php");
        exit;
    }
    $stmt->close();
}

// Check if already has any request (after POST processing)
$has_pending = false;
$check_query = "SELECT id, student_name, created_at FROM admission_interviews WHERE student_id='$id_number' LIMIT 1";
$result = $conn->query($check_query);
if ($result && $result->num_rows > 0) {
    $has_pending = true;
    // Debug: uncomment to see existing record
    // $existing = $result->fetch_assoc();
    // echo "<script>console.log('Existing request found: ID=" . $existing['id'] . ", Name=" . $existing['student_name'] . ", Date=" . $existing['created_at'] . "');</script>";
}

$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission & Interview - LSPU CCS</title>
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
        body.dark-mode .main-content, body.dark-mode .footer, body.dark-mode #admissionModal .modal-content { background: #2d3748; }
        body.dark-mode .page-title, body.dark-mode #admissionModal h3, body.dark-mode #admissionModal label { color: #e2e8f0; }
        body.dark-mode .page-desc, body.dark-mode .footer, body.dark-mode .file-note { color: #cbd5e0; }
        body.dark-mode .tab { color: #cbd5e0; }
        body.dark-mode .tab.active { color: #667eea; }
        body.dark-mode .requirements-table th, body.dark-mode .process-table th { background: #374151; color: #667eea; }
        body.dark-mode .requirements-table td, body.dark-mode .process-table td { background: #2d3748; border-color: #4a5568; color: #e2e8f0; }
        body.dark-mode .process-table td.fee, body.dark-mode .process-table td.time, body.dark-mode .process-table td.person { background: #374151; }
        body.dark-mode #admissionModal input, body.dark-mode #admissionModal textarea, body.dark-mode #admissionModal select { background: #374151; border-color: #4a5568; color: #e2e8f0; }
        body.dark-mode #admissionModal .cancel-btn { background: #374151; color: #e2e8f0; }
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

        #admissionModal {
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

        #admissionModal .modal-content {
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

        #admissionModal .close-x {
            float: right;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
        }

        #admissionModal .close-x:hover {
            color: #667eea;
        }

        #admissionModal label {
            font-weight: 600;
            margin-top: 0.75rem;
            display: block;
            color: #2d3748;
        }

        #admissionModal input, #admissionModal textarea, #admissionModal select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            background: #f9fafb;
        }

        #admissionModal input:focus, #admissionModal textarea:focus, #admissionModal select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        #admissionModal .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        #admissionModal .cancel-btn {
            flex: 1;
            background: #f3f4f6;
            color: #374151;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        #admissionModal .submit-btn {
            flex: 1;
            margin: 0;
        }

        .file-note {
            font-size: 0.75rem;
            color: #718096;
            margin-top: 0.25rem;
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
                    <h1 class="page-title">Admission Interview Request (Freshmen Only)</h1>
                    <p class="page-desc">For freshmen students requesting admission interview schedule.</p>
                </div>
            </div>

            <div class="tabs">
                <button class="tab active" onclick="showTab('avail')">Who may avail</button>
                <button class="tab" onclick="showTab('requirements')">Requirements</button>
                <button class="tab" onclick="showTab('steps')">Process Steps</button>
            </div>

            <div id="tab-avail" class="tab-content active">
                <ul style="margin-left: 1.5rem;">
                    <li>Freshmen students only</li>
                    <li>Students who need admission interview</li>
                </ul>
            </div>

            <div id="tab-requirements" class="tab-content">
                <table class="requirements-table">
                    <tr>
                        <th>Requirements</th>
                        <th>Where to Secure</th>
                    </tr>
                    <tr>
                        <td>Valid Student ID or Proof of Enrollment</td>
                        <td>Registrar's Office</td>
                    </tr>
                    <tr>
                        <td>Contact Number</td>
                        <td>Personal</td>
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
                        <td>Student submits interview request through portal</td>
                        <td class="fee">N/A</td>
                        <td class="time">5 mins</td>
                        <td class="person">Student</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Admin reviews request and sets interview date, time, and platform</td>
                        <td class="fee">N/A</td>
                        <td class="time">1-2 days</td>
                        <td class="person">Admin</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Student receives interview schedule via email and portal notification</td>
                        <td class="fee">N/A</td>
                        <td class="time">Instant</td>
                        <td class="person">System</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Student attends interview at scheduled date/time</td>
                        <td class="fee">N/A</td>
                        <td class="time">15-30 mins</td>
                        <td class="person">Student</td>
                    </tr>
                </table>
                <p style="margin-top: 1rem; padding: 1rem; background: #f0f4ff; border-radius: 8px; font-size: 0.9rem; color: #667eea;">
                    <i class="fas fa-info-circle"></i> <strong>Total Processing Time:</strong> 1-2 days | Admin will send interview schedule via email and portal.
                </p>
            </div>

            <?php if (!$has_pending): ?>
            <button class="submit-btn" onclick="showAdmissionModal()">
                <i class="fas fa-paper-plane"></i> Request Interview Schedule
            </button>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer" id="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - Department of Computer Studies</p>
        <p>INTEGRITY • PROFESSIONALISM • INNOVATION</p>
    </footer>

    <div id="admissionModal">
        <div class="modal-content">
            <span class="close-x" onclick="closeAdmissionModal()">&times;</span>
            <h3>Interview Request Form</h3>
            <form method="post">
                <label>Full Name *</label>
                <input type="text" name="student_name" value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>" required readonly>

                <label>Student ID *</label>
                <input type="text" name="student_id" value="<?php echo htmlspecialchars($id_number); ?>" required readonly>

                <label>Email *</label>
                <input type="email" name="student_email" value="<?php echo htmlspecialchars($email); ?>" required readonly>

                <label>Contact Number *</label>
                <input type="text" name="contact_number" placeholder="09XXXXXXXXX" pattern="[0-9]{11}" maxlength="11" required>

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeAdmissionModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Submit Request</button>
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

        function showAdmissionModal() {
            document.getElementById('admissionModal').style.display = 'flex';
        }

        function closeAdmissionModal() {
            document.getElementById('admissionModal').style.display = 'none';
        }

        <?php if($success_msg): ?>
        alert('<?php echo addslashes($success_msg); ?>');
        <?php endif; ?>
        
        <?php if($error_msg): ?>
        alert('<?php echo addslashes($error_msg); ?>');
        <?php endif; ?>
    </script>

    <?php include 'chatbot.php'; ?>
</body>
</html>
<?php $conn->close(); ?>
