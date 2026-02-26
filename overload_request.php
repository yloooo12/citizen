<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$first_name = isset($_SESSION["first_name"]) ? $_SESSION["first_name"] : "";
$last_name = isset($_SESSION["last_name"]) ? $_SESSION["last_name"] : "";
$id_number = isset($_SESSION["id_number"]) ? $_SESSION["id_number"] : "";
$email = isset($_SESSION["email"]) ? $_SESSION["email"] : "";

// Database connection
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "citizenproj";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$success = false;
$error_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["student_name"])) {
    $student_name = trim($_POST["student_name"]);
    $student_id = trim($_POST["student_id"]);
    $student_email = trim($_POST["student_email"]);
    $request_letter = trim($_POST["request_letter"]);
    $eval_grades = trim($_POST["eval_grades"]);
    $user_id = $_SESSION["user_id"];

    $stmt = $conn->prepare("INSERT INTO overload_requests (student_name, student_id, student_email, user_id, request_letter, eval_grades, date_submitted) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        $error_msg = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("ssisss", $student_name, $student_id, $student_email, $user_id, $request_letter, $eval_grades);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error_msg = "Submission failed: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request for Overload Subject - OL System</title>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            max-width: 1000px;
            margin: 30px auto 0 auto;
            display: flex;
            justify-content: flex-start;
        }
        .back-btn {
            background: #fff;
            border: 1.5px solid #2563eb;
            color: #2563eb;
            border-radius: 6px;
            padding: 7px 22px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            margin-bottom: 18px;
        }
        .back-btn:hover {
            background: #2563eb;
            color: #fff;
        }
        .main-content {
            max-width: 1000px;
            margin: 0 auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 16px #6c2eb722;
            padding: 0 32px 32px 32px;
        }
        .header-row {
            display: flex;
            align-items: flex-start;
            gap: 18px;
            margin-top: 18px;
        }
        .logo {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 4px #0002;
            object-fit: cover;
        }
        .school-info {
            flex: 1;
        }
        .school-title {
            font-size: 1.18rem;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .school-dept {
            font-size: 1.02rem;
            font-weight: 600;
            color: #222;
        }
        .school-charter {
            font-size: 0.98rem;
            color: #444;
            margin-bottom: 2px;
        }
        .overload-title {
            font-size: 1.08rem;
            font-weight: 700;
            margin: 18px 0 0 0;
            color: #222;
        }
        .overload-desc {
            font-size: 0.98rem;
            color: #444;
            margin-bottom: 18px;
        }
        .tabs {
            display: flex;
            margin: 28px 0 0 0;
            border: 1px solid #e5e7eb;
            border-radius: 6px 6px 0 0;
            overflow: hidden;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 10px 0;
            font-weight: 600;
            font-size: 1rem;
            background: #f9fafb;
            color: #2563eb;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .tab.active {
            background: #e0e7ff;
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
        }
        .tab.inactive {
            color: #888;
        }
        .tab.left {
            color: #dc2626;
        }
        .tab.right {
            color: #16a34a;
        }
        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            margin-bottom: 18px;
        }
        .requirements-table th, .requirements-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 14px;
            font-size: 0.98rem;
        }
        .requirements-table th {
            background: #ede9fe;
            color: #6c2eb7;
            font-weight: 700;
        }
        .requirements-table td:last-child, .requirements-table th:last-child {
            background: #f3f4f6;
            color: #222;
            font-weight: 500;
        }
        .requirements-table tr:last-child td {
            border-bottom: 1.5px solid #e5e7eb;
        }
        .process-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
            margin-bottom: 18px;
            font-size: 0.97rem;
        }
        .process-table th, .process-table td {
            border: 1px solid #bdb7d3;
            padding: 8px 10px;
            vertical-align: top;
        }
        .process-table th {
            background: #ede9fe;
            color: #6c2eb7;
            font-weight: 700;
            text-align: center;
        }
        .process-table td {
            background: #f9fafb;
        }
        .process-table td.fee, .process-table td.time, .process-table td.person {
            text-align: center;
            background: #f3f4f6;
        }
        .submit-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 7px;
            font-weight: 600;
            font-size: 1.08rem;
            cursor: pointer;
            margin: 0 auto;
            display: block;
            margin-bottom: 18px;
            transition: background 0.2s;
        }
        .submit-btn:hover {
            background: #1d4ed8;
        }
        .success-message {
            background: #d1fae5;
            color: #065f46;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 18px;
            font-weight: 600;
            display: <?php echo $success ? 'block' : 'none'; ?>;
            text-align: center;
        }
        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 18px;
            font-weight: 600;
            display: <?php echo $error_msg ? 'block' : 'none'; ?>;
            text-align: center;
        }
        .footer {
            text-align: center;
            color: #888;
            font-size: 0.95rem;
            margin: 32px 0 12px 0;
        }
        /* Modal Styles */
        #overloadModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(30,18,54,0.35);
            align-items: center;
            justify-content: center;
        }
        #overloadModal .modal-content {
            background: #fff;
            border-radius: 16px;
            padding: 28px 24px 24px 24px;
            box-shadow: 0 2px 24px #6c2eb744;
            min-width: 330px;
            max-width: 98vw;
            width: 420px;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        #overloadModal .close-x {
            position: absolute;
            top: 12px;
            right: 18px;
            font-size: 3.3rem;
            color: #888;
            cursor: pointer;
            transition: color 0.2s;
        }
        #overloadModal .close-x:hover {
            color: #6c2eb7;
        }
        #overloadModal label {
            font-weight: 600;
            margin-top: 8px;
            margin-bottom: 2px;
            display: block;
        }
        #overloadModal input[type="text"],
        #overloadModal input[type="email"],
        #overloadModal textarea {
            width: calc(100% - 6px);
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-size: 1rem;
            margin-bottom: 8px;
            background: #f9fafb;
            box-sizing: border-box;
        }
        #overloadModal textarea {
            min-height: 48px;
            max-height: 240px;
            height: 100px;
            resize: vertical;
        }
        #overloadModal .modal-buttons { 
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 8px;
        }
        #overloadModal .cancel-btn {
            background: #f3f4f6;
            color: #222;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 18px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        #overloadModal .cancel-btn:hover {
            background: #e5e7eb;
        }
        #overloadModal .submit-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 22px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        #overloadModal .submit-btn:hover {
            background: #1d4ed8;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        @media (max-width: 1000px) {
            .main-content, #overloadModal .modal-content {
                width: 99vw;
                min-width: unset;
                padding: 12px 2vw;
            }
            .process-table th, .process-table td {
                font-size: 0.93rem;
                padding: 7px 4px;
            }
        }
        @media (max-width: 700px) {
            .main-content, .top-bar {
                max-width: 99vw;
                padding: 0 2vw;
            }
            .header-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .logo {
                width: 48px;
                height: 48px;
            }
            #overloadModal .modal-content {
                width: 97vw;
                min-width: unset;
                padding: 12px 2vw;
            }
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <button class="back-btn" onclick="window.location.href='index.php'">&lt; Back to Home</button>
    </div>
    <div class="main-content">
        <div style="height:18px;"></div>
        <div class="header-row">
            <img src="logo-ccs.webp" alt="LSPU CCS Logo" class="logo">
            <div class="school-info">
                <div class="school-title">LAGUNA STATE POLYTECHNIC UNIVERSITY</div>
                <div class="school-dept">Department of College of Computer Studies</div>
                <div class="school-charter">CITIZEN'S CHARTER V. 2024</div>
            </div>
        </div>
        <div class="overload-title">Request for Overload Subject - OL System</div>
        <div class="overload-desc">
            This service is offered to graduating students who wish to request for overload subjects.
        </div>
        <div class="tabs" id="tabNav">
            <div class="tab left active" onclick="showTab('avail')">Who may avail</div>
            <div class="tab" onclick="showTab('requirements')">Checklist of Requirements</div>
            <div class="tab right" onclick="showTab('steps')">Client Steps</div>
        </div>
        <div id="tab-avail" class="tab-content active">
            <ul style="margin:18px 0 0 18px;font-size:1.05rem;">
                <li>Graduating Students</li>
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
                    <th>Client Steps</th>
                    <th>Agency Actions</th>
                    <th>Fees to be Paid</th>
                    <th>Processing Time</th>
                    <th>Person Responsible</th>
                </tr>
                <tr>
                    <td>1. Submit the evaluation of grades to the Dean/Associate Dean</td>
                    <td>Evaluate the grades of the student to determine whether he/she is qualified to enroll an overload subject</td>
                    <td class="fee">N/A</td>
                    <td class="time">2 MINUTES</td>
                    <td class="person">Dean/Associate Dean/Program Coordinator</td>
                </tr>
                <tr>
                    <td>2. Assessment and evaluation of the availability of subjects</td>
                    <td>Assessment of subject availability and class size</td>
                    <td class="fee">N/A</td>
                    <td class="time">1 DAY</td>
                    <td class="person">Dean/Associate Dean</td>
                </tr>
                <tr>
                    <td>3. Submits letter request for the offering of unscheduled subject/s and copy of evaluation of grades from the Office of the Registrar to the Office of the Dean/Associate Dean</td>
                    <td>Receives the letter request and verifies the need for the offering of unscheduled subject/s</td>
                    <td class="fee">N/A</td>
                    <td class="time">3 MINUTES</td>
                    <td class="person">Dean/Associate Dean/Program Coordinator</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Forward request to the Office of the Registrar</td>
                    <td class="fee">N/A</td>
                    <td class="time">30 MINUTES</td>
                    <td class="person">Dean/Associate Dean</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Forward the request to the OVPAA for approval</td>
                    <td class="fee">N/A</td>
                    <td class="time">10 MINUTES</td>
                    <td class="person">Document Controllers</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Inform the student that the subject requested has been offered (or not) upon receipt of approved request</td>
                    <td class="fee">N/A</td>
                    <td class="time">1 MINUTE</td>
                    <td class="person">Dean/Associate Dean/Program Coordinator</td>
                </tr>
                <tr>
                    <td>4. Proceed to the enrolment/adding of subject</td>
                    <td>None</td>
                    <td class="fee">N/A</td>
                    <td class="time">N/A</td>
                    <td class="person">Client</td>
                </tr>
            </table>
        </div>
        <div style="height:18px;"></div>
        <button class="submit-btn" onclick="showOverloadModal()">
            Submit Requirements
        </button>
        <div class="success-message" id="successMsg">
            <i class="fa fa-check-circle"></i> Your request has been submitted successfully!
        </div>
        <div class="error-message" id="errorMsg">
            <i class="fa fa-times-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
    </div>
    <!-- Overload Modal -->
    <div id="overloadModal">
        <div class="modal-content">
            <span class="close-x" onclick="closeOverloadModal()">&times;</span>
            <h3 style="margin-top:0;">Overload Subject Request Form</h3>
            <form id="overloadForm" method="post" autocomplete="off">
                <label for="student_name">Student Name <span class="required-star">*</span></label>
                <input type="text" id="student_name" name="student_name" value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>" required readonly>

                <label for="student_id">Student ID <span class="required-star">*</span></label>
                <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($id_number); ?>" required readonly>

                <label for="student_email">Email <span class="required-star">*</span></label>
                <input type="email" id="student_email" name="student_email" value="<?php echo htmlspecialchars($email); ?>" required readonly>

                <label for="request_letter">Request Letter <span class="required-star">*</span></label>
                <textarea id="request_letter" name="request_letter" rows="4" placeholder="Write your request letter here..." required></textarea>

                <label for="eval_grades">Copy of Evaluation of Grades <span class="required-star">*</span></label>
                <textarea id="eval_grades" name="eval_grades" rows="3" placeholder="Paste or describe your evaluation of grades..." required></textarea>

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeOverloadModal()">Cancel</button>
                    <button type="submit" class="submit-btn"><i class="fa fa-paper-plane"></i> Submit</button>
                </div>
            </form>
        </div>
    </div>
    <div class="footer">
        &copy; 2024 Laguna State Polytechnic University
    </div>
    <script>
        function showTab(tab) {
            var tabs = document.querySelectorAll('.tabs .tab');
            tabs.forEach(function(t){ t.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function(tc){ tc.classList.remove('active'); });
            if(tab === 'avail') {
                tabs[0].classList.add('active');
                document.getElementById('tab-avail').classList.add('active');
            } else if(tab === 'requirements') {
                tabs[1].classList.add('active');
                document.getElementById('tab-requirements').classList.add('active');
            } else if(tab === 'steps') {
                tabs[2].classList.add('active');
                document.getElementById('tab-steps').classList.add('active');
            }
        }
        function showOverloadModal() {
            document.getElementById('overloadModal').style.display = 'flex';
            document.getElementById('overloadForm').reset();
            document.getElementById('student_name').value = "<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>";
            document.getElementById('student_id').value = "<?php echo htmlspecialchars($id_number); ?>";
            document.getElementById('student_email').value = "<?php echo htmlspecialchars($email); ?>";
        }
        function closeOverloadModal() {
            document.getElementById('overloadModal').style.display = 'none';
        }
        // Hide success/error messages after 4 seconds
        window.onload = function() {
            if (<?php echo $success ? 'true' : 'false'; ?>) {
                setTimeout(function() {
                    document.getElementById('successMsg').style.display = 'none';
                }, 4000);
            }
            <?php if ($error_msg): ?>
            setTimeout(function() {
                document.getElementById('errorMsg').style.display = 'none';
            }, 6000);
            <?php endif; ?>
        };
    </script>
</body>
</html>
<?php $conn->close(); ?>