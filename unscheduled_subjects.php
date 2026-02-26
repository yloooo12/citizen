<?php
session_start();

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $conn = new mysqli("localhost", "root", "", "student_services");
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $user_id = $_SESSION["user_id"] ?? 0;
    $id_number = $_SESSION["id_number"] ?? '';
    
    // Check if student is Irregular
    $is_irregular = false;
    $student_check = $conn->query("SELECT student_type FROM users WHERE id_number='$id_number' LIMIT 1");
    if ($student_check && $row = $student_check->fetch_assoc()) {
        $is_irregular = ($row['student_type'] == 'Irregular');
    }
    
    if (!$is_irregular) {
        echo json_encode(['success' => false, 'message' => "This service is only available for Irregular students. Your student type does not qualify for unscheduled subject requests."]);
        exit;
    }
    
    // Handle file upload
    $eval_file = null;
    if (isset($_FILES['eval_grades']) && $_FILES['eval_grades']['error'] == 0) {
        $upload_dir = 'uploads/evaluations/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['eval_grades']['name'], PATHINFO_EXTENSION);
        $eval_file = 'eval_' . $id_number . '_' . time() . '.' . $file_ext;
        move_uploaded_file($_FILES['eval_grades']['tmp_name'], $upload_dir . $eval_file);
    }
    
    // Create table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS unscheduled_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(100) NOT NULL,
        student_id VARCHAR(20) NOT NULL,
        student_email VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        subject_code VARCHAR(20) NOT NULL,
        subject_name VARCHAR(100) NOT NULL,
        reason TEXT NOT NULL,
        eval_file VARCHAR(255) DEFAULT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        date_submitted TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert request
    $student_name  = trim($_POST["student_name"] ?? '');
    $student_id    = trim($_POST["student_id"] ?? '');
    $student_email = trim($_POST["student_email"] ?? '');
    $subject_code  = trim($_POST["subject_code"] ?? '');
    $subject_name  = trim($_POST["subject_name"] ?? '');
    $reason        = trim($_POST["reason"] ?? '');
    
    // If email is empty, get from database
    if (empty($student_email)) {
        $email_result = $conn->query("SELECT email FROM users WHERE id_number='$student_id' LIMIT 1");
        if ($email_result && $email_row = $email_result->fetch_assoc()) {
            $student_email = $email_row['email'];
        }
    }
    
    // Simple insert
    $student_name = $conn->real_escape_string($student_name);
    $student_id = $conn->real_escape_string($student_id);
    $student_email = $conn->real_escape_string($student_email);
    $subject_code = $conn->real_escape_string($subject_code);
    $subject_name = $conn->real_escape_string($subject_name);
    $reason = $conn->real_escape_string($reason);
    $eval_file_safe = $eval_file ? "'" . $conn->real_escape_string($eval_file) . "'" : 'NULL';
    
    $sql = "INSERT INTO unscheduled_requests 
        (student_name, student_id, student_email, user_id, subject_code, subject_name, reason, eval_file, date_submitted) 
        VALUES ('$student_name', '$student_id', '$student_email', $user_id, '$subject_code', '$subject_name', '$reason', $eval_file_safe, NOW())";
    
    if (empty($student_name) || empty($subject_code)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $result = $conn->query($sql);
    
    if ($result) {
            $request_id = $conn->insert_id;
            
            // Notify Dean
            require_once 'dean_notification_system.php';
            notifyDeanUnscheduledRequest($student_id, $student_name, $request_id, $subject_code, $subject_name);
            
            // Send email to dean
            $dean_result = $conn->query("SELECT email, first_name FROM users WHERE is_admin=3 LIMIT 1");
            if ($dean_result && $dean_row = $dean_result->fetch_assoc()) {
                $dean_email = $dean_row['email'];
                $dean_name = $dean_row['first_name'];
                
                require_once 'PHPMailer/src/Exception.php';
                require_once 'PHPMailer/src/PHPMailer.php';
                require_once 'PHPMailer/src/SMTP.php';
                require_once 'email_config.php';
                
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = SMTP_HOST;
                    $mail->SMTPAuth = SMTP_AUTH;
                    $mail->Username = SMTP_USERNAME;
                    $mail->Password = SMTP_PASSWORD;
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = SMTP_PORT;
                    $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
                    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    $mail->addAddress($dean_email, $dean_name);
                    $mail->Subject = 'New Unscheduled Subject Request - LSPU CCS';
                    $mail->Body = "Dear $dean_name,\n\nA new unscheduled subject request has been submitted.\n\nStudent: $student_name ($student_id)\nSubject: $subject_code - $subject_name\n\nPlease check your notifications at: dean_notifications.php\n\nLSPU-CCS";
                    $mail->send();
                } catch (Exception $e) {
                    // Ignore email errors
                }
            }
            
            $conn->close();
            echo json_encode(['success' => true, 'message' => 'Request submitted successfully!']);
            exit;
            

    } else {
        echo json_encode(['success' => false, 'message' => 'Submission failed: ' . $conn->error]);
        exit;
    }
    
    $conn->close();
    exit;
}

// Regular page load
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? "";
$last_name  = $_SESSION["last_name"] ?? "";
$id_number  = $_SESSION["id_number"] ?? "";
$email      = $_SESSION["email"] ?? "";

// If email not in session, get from database
if (empty($email) && !empty($id_number)) {
    $conn_temp = new mysqli("localhost", "root", "", "student_services");
    if (!$conn_temp->connect_error) {
        $result = $conn_temp->query("SELECT email FROM users WHERE id_number='$id_number' LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $email = $row['email'];
        }
        $conn_temp->close();
    }
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Check if student is Irregular
$user_id       = $_SESSION["user_id"] ?? 0;
$is_irregular  = false;
$has_submitted = false;

$student_check = $conn->query("SELECT student_type FROM users WHERE id_number='$id_number' LIMIT 1");
if ($student_check && $row = $student_check->fetch_assoc()) {
    $is_irregular = ($row['student_type'] == 'Irregular');
}

// Check if already submitted
$request_check = $conn->query("SELECT id FROM unscheduled_requests WHERE student_id='$id_number' AND status IN ('pending', 'approved') LIMIT 1");
if ($request_check && $request_check->num_rows > 0) {
    $has_submitted = true;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request/Offering Subject - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #333;
            overflow: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }
        body.dark-mode { background: #1a202c; color: #e2e8f0; }
        body.dark-mode .main-content, body.dark-mode .footer, body.dark-mode #unschedModal .modal-content { background: #2d3748; }
        body.dark-mode .page-title, body.dark-mode #unschedModal h3, body.dark-mode #unschedModal label { color: #e2e8f0; }
        body.dark-mode .page-desc, body.dark-mode .footer { color: #cbd5e0; }
        body.dark-mode .tab { color: #cbd5e0; }
        body.dark-mode .tab.active { color: #667eea; }
        body.dark-mode .requirements-table th, body.dark-mode .process-table th { background: #374151; color: #667eea; }
        body.dark-mode .requirements-table td, body.dark-mode .process-table td { background: #2d3748; border-color: #4a5568; color: #e2e8f0; }
        body.dark-mode .process-table td.fee, body.dark-mode .process-table td.time, body.dark-mode .process-table td.person { background: #374151; }
        body.dark-mode #unschedModal input, body.dark-mode #unschedModal textarea { background: #374151; border-color: #4a5568; color: #e2e8f0; }
        body.dark-mode #unschedModal .cancel-btn { background: #374151; color: #e2e8f0; }
        body.dark-mode .back-btn { background: #374151; color: #667eea; }
        body.dark-mode .back-btn:hover { background: #667eea; color: white; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }

        .main-container {
            margin-left: 260px;
            margin-top: 65px;
            padding: 2rem 2.5rem;
            height: calc(100vh - 65px);
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }
        .main-container.collapsed { margin-left: 70px; }

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
        .back-btn:hover { background: #667eea; color: white; }

        .page-logo {
            width: 50px; height: 50px;
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

        .tab:hover { color: #667eea; }

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
        .footer.collapsed { margin-left: 70px; }

        #unschedModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        #unschedModal .modal-content {
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

        #unschedModal .close-x {
            float: right;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
        }

        #unschedModal .close-x:hover {
            color: #667eea;
        }

        #unschedModal label {
            font-weight: 600;
            margin-top: 0.75rem;
            display: block;
            color: #2d3748;
        }

        #unschedModal input, #unschedModal textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            background: #f9fafb;
        }

        #unschedModal input:focus, #unschedModal textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        #unschedModal .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        #unschedModal .cancel-btn {
            flex: 1;
            background: #f3f4f6;
            color: #374151;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        #unschedModal .submit-btn { flex: 1; margin: 0; }

        @media (max-width: 768px) {
            .main-container { margin-left: 0; padding: 1rem; }
            .main-content { padding: 1.5rem; }

            .page-header {
                flex-wrap: wrap;
                padding-left: 1rem;
            }

            .page-logo { width: 40px; height: 40px; }
            .page-title { font-size: 1.25rem; }
            .page-desc { font-size: 0.85rem; }

            .footer { margin-left: 0; }

            .tabs { flex-wrap: wrap; gap: 0.5rem; }
            .tab {
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
                flex: 1 1 auto;
                text-align: center;
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
                    <h1 class="page-title">Request/Offering Subject - OL System</h1>
                    <p class="page-desc">For irregular students including transferees and returnees.</p>
                </div>
            </div>

            <div class="tabs">
                <button class="tab active" onclick="showTab('avail')">Who may avail</button>
                <button class="tab" onclick="showTab('requirements')">Requirements</button>
                <button class="tab" onclick="showTab('steps')">Process Steps</button>
            </div>

            <div id="tab-avail" class="tab-content active">
                <ul style="margin-left: 1.5rem;">
                    <li>Irregular students including transferees and returnees</li>
                </ul>
            </div>

            <div id="tab-requirements" class="tab-content">
                <table class="requirements-table">
                    <tr>
                        <th>Requirements</th>
                        <th>Where to Secure</th>
                    </tr>
                    <tr>
                        <td>Request Letter</td>
                        <td>Client</td>
                    </tr>
                    <tr>
                        <td>Copy of Evaluation of Grades</td>
                        <td>Registrar</td>
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
                        <td>Student logs in to portal and submits request letter with evaluation of grades online</td>
                        <td class="fee">N/A</td>
                        <td class="time">5 mins</td>
                        <td class="person">Student</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>System notifies Dean/Associate Dean. Reviews request and verifies need for unscheduled subject</td>
                        <td class="fee">N/A</td>
                        <td class="time">3 mins</td>
                        <td class="person">Dean/Associate Dean</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Request forwarded to Registrar's Office for processing</td>
                        <td class="fee">N/A</td>
                        <td class="time">30 mins</td>
                        <td class="person">Dean/Associate</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>System generates printable request document. Student downloads and submits to Registrar manually</td>
                        <td class="fee">N/A</td>
                        <td class="time">Instant</td>
                        <td class="person">Student</td>
                    </tr>
                </table>
                <p style="margin-top: 1rem; padding: 1rem; background: #f0f4ff; border-radius: 8px; font-size: 0.9rem; color: #667eea;">
                    <i class="fas fa-info-circle"></i> <strong>Total Processing Time:</strong> 1-2 days | After Dean approval, download the document and submit to Registrar's Office.
                </p>
            </div>

            <button class="submit-btn" onclick="showUnschedModal()">
                <i class="fas fa-paper-plane"></i> Submit Request
            </button>
        </div>
    </main>

    <footer class="footer" id="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - Department of Computer Studies</p>
        <p>INTEGRITY • PROFESSIONALISM • INNOVATION</p>
    </footer>

    <div id="unschedModal">
        <div class="modal-content">
            <span class="close-x" onclick="closeUnschedModal()">&times;</span>
            <h3>Unscheduled Subject Request Form</h3>
            <form id="unschedForm" onsubmit="submitUnschedRequest(event)">
                <input type="hidden" name="student_name"  value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>">
                <input type="hidden" name="student_id"    value="<?php echo htmlspecialchars($id_number); ?>">
                <input type="hidden" name="student_email" value="<?php echo htmlspecialchars($email); ?>">
                
                <label>Full Name *</label>
                <input type="text" value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>" required readonly style="background: #f0f0f0;">

                <label>Student ID *</label>
                <input type="text" value="<?php echo htmlspecialchars($id_number); ?>" required readonly style="background: #f0f0f0;">

                <label>Email *</label>
                <input type="email" value="<?php echo htmlspecialchars($email); ?>" required readonly style="background: #f0f0f0;">

                <label>Subject Code *</label>
                <input type="text" name="subject_code" placeholder="e.g., CS101" required>

                <label>Subject Name *</label>
                <input type="text" name="subject_name" placeholder="e.g., Introduction to Programming" required>

                <label>Reason for Request *</label>
                <textarea name="reason" rows="8" required readonly style="background: white;">I, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>, a student of Laguna State Polytechnic University, College of Computer Studies, would like to formally request for an unscheduled subject offering.

I am currently enrolled as an Irregular student and need to take this subject to complete my academic requirements. The subject is not included in the regular schedule for this semester, which is why I am requesting for a special class arrangement.

I understand that this request is subject to approval by the Dean and availability of faculty members. I am willing to comply with all requirements and schedules that will be set for this unscheduled subject.

Thank you for your consideration.

Respectfully yours,
<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
<?php echo htmlspecialchars($id_number); ?></textarea>

                <label>Copy of Evaluation of Grades (PDF/Image) *</label>
                <input type="file" name="eval_grades" accept=".pdf,.jpg,.jpeg,.png" required style="padding: 0.5rem;">

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeUnschedModal()">Cancel</button>
                    <button type="submit" class="submit-btn" id="submitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
            
            if (tab === 'avail') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('tab-avail').classList.add('active');
            } else if (tab === 'requirements') {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('tab-requirements').classList.add('active');
            } else if (tab === 'steps') {
                document.querySelectorAll('.tab')[2].classList.add('active');
                document.getElementById('tab-steps').classList.add('active');
            }
        }

        function showUnschedModal() {
            <?php if (!$is_irregular): ?>
            alert('This service is only available for Irregular students. Your student type does not qualify for unscheduled subject requests.');
            return;
            <?php endif; ?>
            <?php if ($has_submitted): ?>
            alert('You have already submitted an unscheduled subject request. Please wait for Dean approval before submitting a new request.');
            return;
            <?php endif; ?>
            document.getElementById('unschedModal').style.display = 'flex';
        }

        function closeUnschedModal() {
            document.getElementById('unschedModal').style.display = 'none';
            document.getElementById('unschedForm').reset();
        }

        function submitUnschedRequest(e) {
            e.preventDefault();
            const form = e.target;
            const fileInput = form.querySelector('input[type="file"]');
            
            if (!fileInput.files[0]) {
                alert('Please upload your evaluation of grades file');
                return;
            }
            
            const formData = new FormData(form);
            formData.append('ajax', '1');
            const submitBtn = document.getElementById('submitBtn');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            fetch('unscheduled_subjects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response:', response);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    alert(data.message);
                    if (data.success) {
                        closeUnschedModal();
                        window.location.reload();
                    } else {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit';
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Server error: ' + text);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Network error: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit';
            });
        }
    </script>

    <?php include 'chatbot.php'; ?>
</body>
</html>
