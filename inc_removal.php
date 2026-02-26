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
    
    $id_number = $_SESSION["id_number"] ?? "";
    $user_id = $_SESSION["user_id"] ?? 0;
    $subject = trim($_POST["subject"] ?? "");
    

    
    // Get INC subjects from academic_alerts
    $inc_subjects = [];
    $inc_check = $conn->query("SELECT course FROM academic_alerts WHERE student_id='$id_number' AND alert_type='INC' AND is_resolved=0");
    if ($inc_check) {
        while($row = $inc_check->fetch_assoc()) {
            $inc_subjects[] = $row['course'];
        }
    }
    
    // Get requested subjects
    $requested_subjects = [];
    $req_check = $conn->query("SELECT subject FROM inc_requests WHERE user_id=$user_id AND approved=0");
    if ($req_check && $req_check->num_rows > 0) {
        while($row = $req_check->fetch_assoc()) {
            $requested_subjects[] = $row['subject'];
        }
    }
    // Also exclude PENDING_INC subjects
    $pending_check = $conn->query("SELECT course FROM academic_alerts WHERE user_id=$user_id AND alert_type='PENDING_INC' AND is_resolved=0");
    if ($pending_check && $pending_check->num_rows > 0) {
        while($row = $pending_check->fetch_assoc()) {
            $requested_subjects[] = $row['course'];
        }
    }
    
    // Validation temporarily disabled for testing
    
    // Insert request
    $student_name = trim($_POST["student_name"]);
    $student_id = trim($_POST["student_id"]);
    $student_email = trim($_POST["student_email"]);
    $professor = trim($_POST["professor"]);
    $inc_reason = trim($_POST["inc_reason"]);
    $inc_semester = trim($_POST["inc_semester"]);
    $user_id = $_SESSION["user_id"];
    
    $stmt = $conn->prepare("INSERT INTO inc_requests (student_name, student_id, student_email, user_id, professor, subject, inc_reason, inc_semester, date_submitted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ssssssss", $student_name, $student_id, $student_email, $user_id, $professor, $subject, $inc_reason, $inc_semester);
        if ($stmt->execute()) {
            $stmt->close();
            
            // Change academic alert to PENDING_INC
            $escaped_subject = $conn->real_escape_string($subject);
            $update_query = "UPDATE academic_alerts SET alert_type='PENDING_INC', reason='INC request submitted and under review', intervention='Wait for professor and admin approval' WHERE user_id=$user_id AND course='$escaped_subject' AND alert_type='INC' AND is_resolved=0";
            $update_alert = $conn->query($update_query);
            if (!$update_alert) {
                error_log("Failed to update academic_alerts: " . $conn->error . " Query: " . $update_query);
            } else {
                error_log("Updated " . $conn->affected_rows . " rows in academic_alerts");
            }
            
            // Log activity
            $activity_title = "INC Request: " . $subject;
            $activity_desc = "Submitted INC removal request for " . $subject . " (" . $professor . ")";
            $conn->query("INSERT INTO user_activities (student_id, activity_type, activity_title, activity_description, status) VALUES ('$student_id', 'inc_request', '$activity_title', '$activity_desc', 'pending')");
            
            // Create admin notification
            $admin_msg = "New INC request from $student_name ($student_id) for $subject";
            $conn->query("INSERT INTO admin_notifications (message) VALUES ('$admin_msg')");
            
            // Create teacher notification
            $teacher_msg = "New INC request from $student_name ($student_id) for $subject - requires your approval";
            $teacher_id_result = $conn->query("SELECT id_number FROM users WHERE CONCAT(last_name, ', ', first_name) = '$professor' AND user_type = 'teacher' LIMIT 1");
            if ($teacher_id_result && $teacher_row = $teacher_id_result->fetch_assoc()) {
                $teacher_id = $teacher_row['id_number'];
                $conn->query("INSERT INTO teacher_notifications (teacher_id, message, is_read) VALUES ('$teacher_id', '$teacher_msg', 0)");
            }
            
            // Get updated available subjects count
            $requested_subjects[] = $subject;
            $available_count = count(array_diff($inc_subjects, $requested_subjects));
            
            $conn->close();
            echo json_encode(['success' => true, 'message' => 'Request submitted successfully!', 'available_count' => $available_count]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Submission failed: ' . $stmt->error]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    $conn->close();
    exit;
}

// Regular page load
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

// Check if student has INC grades from academic_alerts
$user_id = $_SESSION["user_id"] ?? 0;
$has_inc = false;
$inc_subjects = [];
$inc_data = [];
$inc_check = $conn->query("SELECT course, instructor, school_year, semester FROM academic_alerts WHERE student_id='$id_number' AND alert_type='INC' AND is_resolved=0");
if ($inc_check) {
    while($row = $inc_check->fetch_assoc()) {
        $inc_subjects[] = $row['course'];
        $inc_data[$row['course']] = [
            'professor' => $row['instructor'] ?? '',
            'semester' => ($row['semester'] ?? '') . ' ' . ($row['school_year'] ?? '')
        ];
    }
    $has_inc = count($inc_subjects) > 0;
}

// Get subjects with existing requests (pending or in-progress) OR already submitted (PENDING_INC)
$requested_subjects = [];
$req_check = $conn->query("SELECT subject FROM inc_requests WHERE user_id=$user_id AND approved=0");
if ($req_check && $req_check->num_rows > 0) {
    while($row = $req_check->fetch_assoc()) {
        $requested_subjects[] = $row['subject'];
    }
}
// Also exclude subjects with PENDING_INC status
$pending_check = $conn->query("SELECT course FROM academic_alerts WHERE user_id=$user_id AND alert_type='PENDING_INC' AND is_resolved=0");
if ($pending_check && $pending_check->num_rows > 0) {
    while($row = $pending_check->fetch_assoc()) {
        $requested_subjects[] = $row['course'];
    }
}

// Available subjects = INC subjects - already requested subjects
$available_subjects = array_diff($inc_subjects, $requested_subjects);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INC/4.0 Completion - LSPU CCS</title>
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
        body.dark-mode #incModal .modal-content {
            background: #2d3748;
            border-color: #4a5568;
        }

        body.dark-mode .page-title,
        body.dark-mode #incModal h3,
        body.dark-mode #incModal label {
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

        body.dark-mode #incModal input,
        body.dark-mode #incModal textarea,
        body.dark-mode #incModal select {
            background: #374151;
            border-color: #4a5568;
            color: #e2e8f0;
        }

        body.dark-mode #incModal .cancel-btn {
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

        #incModal {
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

        #incModal .modal-content {
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

        #incModal .close-x {
            float: right;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
        }

        #incModal .close-x:hover {
            color: #667eea;
        }

        #incModal label {
            font-weight: 600;
            margin-top: 0.75rem;
            display: block;
            color: #2d3748;
        }

        #incModal input, #incModal textarea, #incModal select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            background: #f9fafb;
        }

        #incModal input:focus, #incModal textarea:focus, #incModal select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        #incModal .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        #incModal .cancel-btn {
            flex: 1;
            background: #f3f4f6;
            color: #374151;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        #incModal .submit-btn {
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
                    <h1 class="page-title">INC/4.0 Completion/Removal Request</h1>
                    <p class="page-desc">For students with a grade of INC or 4.0 requesting completion/removal grades.</p>
                </div>
            </div>

            <div class="tabs">
                <button class="tab active" onclick="showTab('avail')">Who may avail</button>
                <button class="tab" onclick="showTab('requirements')">Requirements</button>
                <button class="tab" onclick="showTab('steps')">Process Steps</button>
            </div>

            <div id="tab-avail" class="tab-content active">
                <ul style="margin-left: 1.5rem;">
                    <li>Students with a grade of INC or 4.0 requesting completion/removal grades</li>
                </ul>
            </div>

            <div id="tab-requirements" class="tab-content">
                <table class="requirements-table">
                    <tr>
                        <th>Requirements</th>
                        <th>Where to Secure</th>
                    </tr>
                    <tr>
                        <td>Completion Form</td>
                        <td>Subject Teacher</td>
                    </tr>
                    <tr>
                        <td>Proof of Payment (official receipt)</td>
                        <td>Cashier / Registrar</td>
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
                        <td>Student logs in to portal and submits INC/4.0 completion request online</td>
                        <td class="fee">N/A</td>
                        <td class="time">5 mins</td>
                        <td class="person">Student</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>System notifies Cashier. Student pays ₱30.00 fee and provides OR number online</td>
                        <td class="fee">₱30.00</td>
                        <td class="time">10 mins</td>
                        <td class="person">Student/Cashier</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>System notifies Professor. Professor reviews and submits grade online</td>
                        <td class="fee">N/A</td>
                        <td class="time">1-3 days</td>
                        <td class="person">Professor</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>System notifies Dean. Dean reviews and approves request online</td>
                        <td class="fee">N/A</td>
                        <td class="time">1-2 days</td>
                        <td class="person">Dean</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>System generates printable completion form. Student downloads and submits to Registrar manually</td>
                        <td class="fee">N/A</td>
                        <td class="time">Instant</td>
                        <td class="person">Student</td>
                    </tr>
                </table>
                <p style="margin-top: 1rem; padding: 1rem; background: #f0f4ff; border-radius: 8px; font-size: 0.9rem; color: #667eea;">
                    <i class="fas fa-info-circle"></i> <strong>Total Processing Time:</strong> 3-5 days | After Dean approval, download the form and submit to Registrar's Office.
                </p>
            </div>

            <button class="submit-btn" onclick="showIncModal()">
                <i class="fas fa-paper-plane"></i> Submit INC/4.0 Request
            </button>
        </div>
    </main>

    <footer class="footer" id="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - Department of Computer Studies</p>
        <p>INTEGRITY • PROFESSIONALISM • INNOVATION</p>
    </footer>

    <div id="incModal">
        <div class="modal-content">
            <span class="close-x" onclick="closeIncModal()">&times;</span>
            <h3>INC/4.0 Completion Form</h3>
            <form id="incForm" onsubmit="submitIncRequest(event)">
                <label>Full Name *</label>
                <input type="text" name="student_name" value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>" required readonly>

                <label>Student ID *</label>
                <input type="text" name="student_id" value="<?php echo htmlspecialchars($id_number); ?>" required readonly>

                <label>Email *</label>
                <input type="email" name="student_email" value="<?php echo htmlspecialchars($email); ?>" required readonly>

                <label>Subject *</label>
                <select name="subject" id="subjectSelect" required>
                    <option value="">-- Select Subject --</option>
                    <?php if(empty($available_subjects)): ?>
                        <option value="" disabled>No INC subjects found</option>
                    <?php else: ?>
                        <?php foreach($available_subjects as $subj): ?>
                            <option value="<?php echo htmlspecialchars($subj); ?>"><?php echo htmlspecialchars($subj); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <label>Professor *</label>
                <input type="text" name="professor" id="professorInput" readonly required>

                <label>Semester INC Occurred *</label>
                <input type="text" name="inc_semester" id="semesterInput" readonly required>

                <label>Reason *</label>
                <textarea name="inc_reason" rows="3" placeholder="Reason for INC/4.0" required></textarea>

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeIncModal()">Cancel</button>
                    <button type="submit" class="submit-btn" id="submitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let availableCount = <?php echo count($available_subjects); ?>;

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

        function showIncModal() {
            if (availableCount === 0) {
                alert('No available INC subjects to request. All subjects have been submitted or are pending approval.');
                return;
            }
            document.getElementById('incModal').style.display = 'flex';
        }

        function closeIncModal() {
            document.getElementById('incModal').style.display = 'none';
            document.getElementById('incForm').reset();
        }

        const incData = <?php echo json_encode($inc_data); ?>;

        document.getElementById('subjectSelect').addEventListener('change', function() {
            const subject = this.value;
            if (subject && incData[subject]) {
                document.getElementById('professorInput').value = incData[subject].professor || '';
                document.getElementById('semesterInput').value = incData[subject].semester || '';
            } else {
                document.getElementById('professorInput').value = '';
                document.getElementById('semesterInput').value = '';
            }
        });

        function submitIncRequest(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('ajax', '1');
            const submitBtn = document.getElementById('submitBtn');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';
            
            fetch('inc_removal.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    availableCount = data.available_count;
                    closeIncModal();
                } else {
                    alert(data.message);
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
<?php $conn->close(); ?>
