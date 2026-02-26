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

// Check if student is eligible based on academic alerts or student_type
$user_id = $_SESSION["user_id"];
$is_eligible = false;
$eligibility_reason = "";
$eligible_subjects = [];
$has_pending_request = false;

// Check if there's a CREDITING academic alert (from student_services database)
$conn_alerts = new mysqli("localhost", "root", "", "student_services");
$crediting_alert = $conn_alerts->query("SELECT * FROM academic_alerts WHERE student_id='$id_number' AND alert_type='CREDITING' AND is_resolved=0 LIMIT 1");
if ($crediting_alert && $crediting_alert->num_rows > 0) {
    $is_eligible = true;
    $eligible_subjects[] = ['course' => 'Previous subjects for crediting', 'reason' => 'Automatic crediting eligibility'];
    $eligibility_reason = "You are eligible for crediting";
} else {
    // Fallback: Check student_type from users table
    $student_type_result = $conn_alerts->query("SELECT student_type FROM users WHERE id_number='$id_number' LIMIT 1");
    if ($student_type_result && $row = $student_type_result->fetch_assoc()) {
        $student_type = $row['student_type'] ?? '';
        if (in_array($student_type, ['Transferee', 'Shifter', 'Returnee'])) {
            $is_eligible = true;
            $eligible_subjects[] = ['course' => 'Previous subjects for crediting', 'reason' => "$student_type - Previous academic records"];
            $eligibility_reason = "You are eligible as a $student_type";
        } else {
            $eligibility_reason = "This service is only for transferees, shifters, and returnees";
        }
    }
}
$conn_alerts->close();

// Check if there's already any request
$result = $conn->query("SELECT id, status FROM program_head_crediting WHERE student_id='$id_number' ORDER BY created_at DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $request_row = $result->fetch_assoc();
    $has_pending_request = true;
    
    // Resolve crediting alert if request is approved
    if ($request_row['status'] == 'dean_approved' || $request_row['status'] == 'completed') {
        $conn->query("UPDATE academic_alerts SET is_resolved=1 WHERE student_id='$id_number' AND alert_type='CREDITING'");
        $conn->query("UPDATE crediting_alerts SET is_resolved=1 WHERE student_id='$id_number'");
    }
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $student_name = trim($_POST["student_name"]);
    $student_id = trim($_POST["student_id"]);
    $student_email = trim($_POST["student_email"]);
    $subjects_to_credit = trim($_POST["subjects_to_credit"]);
    $transcript_info = trim($_POST["transcript_info"]);
    $user_id = $_SESSION["user_id"];
    
    // Handle file upload
    $transcript_file = null;
    if (isset($_FILES['transcript_file']) && $_FILES['transcript_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['transcript_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, PDF allowed.']);
            exit;
        }
        
        if ($_FILES['transcript_file']['size'] > 5242880) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum 5MB.']);
            exit;
        }
        
        $transcript_file = 'transcript_' . $student_id . '_' . time() . '.' . $ext;
        $upload_path = 'uploads/transcripts/' . $transcript_file;
        
        if (!move_uploaded_file($_FILES['transcript_file']['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'File upload failed.']);
            exit;
        }
    }

    // Get student_type from users table
    $student_type_result = $conn->query("SELECT student_type FROM users WHERE id_number='$student_id' LIMIT 1");
    $student_type = 'Transferee';
    if ($student_type_result && $row = $student_type_result->fetch_assoc()) {
        $student_type = $row['student_type'] ?? 'Transferee';
    }
    
    $stmt = $conn->prepare("INSERT INTO program_head_crediting (student_id, student_name, student_type, subjects_to_credit, transcript_info, transcript_file, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("ssssss", $student_id, $student_name, $student_type, $subjects_to_credit, $transcript_info, $transcript_file);
    if ($stmt->execute()) {
        $stmt->close();
        
        // Update academic alert to show submitted status and resolve crediting alerts
        $conn->query("UPDATE academic_alerts SET grade='SUBMITTED', reason='Crediting request submitted', intervention='Your request is being reviewed by the Program Head' WHERE student_id='$student_id' AND alert_type='CREDITING'");
        $conn->query("UPDATE crediting_alerts SET is_resolved=1 WHERE student_id='$student_id'");
        
        echo json_encode(['success' => true, 'message' => 'Crediting request submitted successfully!']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Crediting - LSPU CCS</title>
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

        body.dark-mode {
            background: #1a202c;
            color: #e2e8f0;
        }

        body.dark-mode .main-content,
        body.dark-mode .footer,
        body.dark-mode #creditModal .modal-content {
            background: #2d3748;
        }

        body.dark-mode .page-title,
        body.dark-mode #creditModal h3,
        body.dark-mode #creditModal label {
            color: #e2e8f0;
        }

        body.dark-mode .page-desc,
        body.dark-mode .footer {
            color: #cbd5e0;
        }

        body.dark-mode .tab {
            color: #cbd5e0;
        }

        body.dark-mode .tab.active {
            color: #667eea;
        }

        body.dark-mode .requirements-table th,
        body.dark-mode .process-table th {
            background: #374151;
            color: #667eea;
        }

        body.dark-mode .requirements-table td,
        body.dark-mode .process-table td {
            background: #2d3748;
            border-color: #4a5568;
            color: #e2e8f0;
        }

        body.dark-mode .process-table td.fee,
        body.dark-mode .process-table td.time,
        body.dark-mode .process-table td.person {
            background: #374151;
        }

        body.dark-mode #creditModal input,
        body.dark-mode #creditModal textarea {
            background: #374151;
            border-color: #4a5568;
            color: #e2e8f0;
        }

        body.dark-mode #creditModal .cancel-btn {
            background: #374151;
            color: #e2e8f0;
        }

        body.dark-mode .back-btn {
            background: #374151;
            color: #667eea;
        }

        body.dark-mode .back-btn:hover {
            background: #667eea;
            color: white;
        }

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

        #creditModal {
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

        #creditModal .modal-content {
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

        #creditModal .close-x {
            float: right;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
        }

        #creditModal .close-x:hover {
            color: #667eea;
        }

        #creditModal label {
            font-weight: 600;
            margin-top: 0.75rem;
            display: block;
            color: #2d3748;
        }

        #creditModal input, #creditModal textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            background: #f9fafb;
        }

        #creditModal input:focus, #creditModal textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        #creditModal .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        #creditModal .cancel-btn {
            flex: 1;
            background: #f3f4f6;
            color: #374151;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        #creditModal .submit-btn {
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
                flex-wrap: wrap;
                gap: 0.5rem;
            }

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
                    <h1 class="page-title">Subject Crediting Request</h1>
                    <p class="page-desc">For transferees, shifters, and returnees requesting subject crediting.</p>
                </div>
            </div>

            <div class="tabs">
                <button class="tab active" onclick="showTab('avail')">Who may avail</button>
                <button class="tab" onclick="showTab('requirements')">Requirements</button>
                <button class="tab" onclick="showTab('steps')">Process Steps</button>
            </div>

            <div id="tab-avail" class="tab-content active">
                <ul style="margin-left: 1.5rem;">
                    <li>Students who are transferees, shifters, and returnees</li>
                </ul>
            </div>

            <div id="tab-requirements" class="tab-content">
                <table class="requirements-table">
                    <tr>
                        <th>Requirements</th>
                        <th>Where to Secure</th>
                    </tr>
                    <tr>
                        <td>Transcript of Records / Copy of Grades</td>
                        <td>Previous School / College Department</td>
                    </tr>
                    <tr>
                        <td>Course Syllabus (if required)</td>
                        <td>Previous School / Department</td>
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
                        <td>Student logs in to portal and submits crediting request with transcript online</td>
                        <td class="fee">N/A</td>
                        <td class="time">10 mins</td>
                        <td class="person">Student</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>System notifies Program Head. Evaluates transcript and identifies subjects to credit</td>
                        <td class="fee">N/A</td>
                        <td class="time">1-2 days</td>
                        <td class="person">Program Head</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>College Secretary prepares document listing credited subjects in system</td>
                        <td class="fee">N/A</td>
                        <td class="time">1 day</td>
                        <td class="person">College Secretary</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>System notifies Dean. Dean reviews and approves credited subjects online</td>
                        <td class="fee">N/A</td>
                        <td class="time">1 day</td>
                        <td class="person">Dean</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>System generates printable crediting document. Student downloads and submits to Registrar manually</td>
                        <td class="fee">N/A</td>
                        <td class="time">Instant</td>
                        <td class="person">Student</td>
                    </tr>
                </table>
                <p style="margin-top: 1rem; padding: 1rem; background: #f0f4ff; border-radius: 8px; font-size: 0.9rem; color: #667eea;">
                    <i class="fas fa-info-circle"></i> <strong>Total Processing Time:</strong> 4-5 days | After Dean approval, download the document and submit to Registrar's Office.
                </p>
            </div>

            <?php if ($has_pending_request): ?>
            <?php
            // Get request status (already fetched above)
            $request_status = $request_row['status'] ?? 'pending';
            ?>
            <div style="text-align: center; padding: 1rem; background: #fef3c7; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #f59e0b;">
                <p style="color: #92400e; font-weight: 600; margin: 0;">
                    <i class="fas fa-info-circle"></i> 
                    You have already submitted a crediting request. Please wait for approval before submitting a new request.
                </p>
            </div>
            <?php endif; ?>
            
            <button class="submit-btn" onclick="showCreditModal()">
                <i class="fas fa-paper-plane"></i> Submit Crediting Request
            </button>
        </div>
    </main>

    <footer class="footer" id="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - Department of Computer Studies</p>
        <p>INTEGRITY • PROFESSIONALISM • INNOVATION</p>
    </footer>

    <div id="creditModal">
        <div class="modal-content">
            <span class="close-x" onclick="closeCreditModal()">&times;</span>
            <h3>Subject Crediting Form</h3>
            <form id="creditForm" onsubmit="submitCreditRequest(event)">
                <label>Full Name *</label>
                <input type="text" name="student_name" value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>" required readonly>

                <label>Student ID *</label>
                <input type="text" name="student_id" value="<?php echo htmlspecialchars($id_number); ?>" required readonly>

                <label>Email *</label>
                <input type="email" name="student_email" value="<?php echo htmlspecialchars($email); ?>" required readonly>

                <label>Subjects to be Credited *</label>
                <textarea name="subjects_to_credit" rows="4" placeholder="List subjects you want to be credited" required></textarea>

                <label>Transcript Info / Previous School *</label>
                <textarea name="transcript_info" rows="3" placeholder="Previous school name, course, and any relevant details" required></textarea>

                <label>Upload Transcript of Records</label>
                <input type="file" name="transcript_file" accept=".jpg,.jpeg,.png,.pdf">
                <small style="color: #718096; font-size: 0.85rem;">JPG, PNG, or PDF (Max 5MB)</small>

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeCreditModal()">Cancel</button>
                    <button type="submit" class="submit-btn" id="submitBtn">Submit</button>
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

        function showCreditModal() {
            <?php if (!$is_eligible || empty($eligible_subjects)): ?>
            alert('You have no pending crediting eligibility or you have already submitted your request.');
            return;
            <?php endif; ?>
            document.getElementById('creditModal').style.display = 'flex';
        }

        function closeCreditModal() {
            document.getElementById('creditModal').style.display = 'none';
            document.getElementById('creditForm').reset();
        }

        function submitCreditRequest(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('ajax', '1');
            const submitBtn = document.getElementById('submitBtn');
            const mainSubmitBtn = document.querySelector('.submit-btn');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';
            
            fetch('crediting.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    closeCreditModal();
                    window.location.reload();
                } else {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit';
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit';
            });
        }
    </script>

    <?php include 'chatbot.php'; ?>
</body>
</html>
