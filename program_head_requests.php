<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== 'program_head') {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? '';
$last_name = $_SESSION["last_name"] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle send to dean
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_to_dean"])) {
    $id = $_POST['id'];
    
    $stmt = $conn->prepare("UPDATE program_head_crediting SET status='sent_to_dean' WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Request sent to Dean successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// Handle evaluation submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["evaluate_request"])) {
    $id = $_POST['id'];
    $credited_subjects = trim($_POST['credited_subjects']);
    $remarks = trim($_POST['remarks']);
    $signature = $_POST['signature'] ?? '';
    
    // Save signature image
    $signature_file = null;
    if (!empty($signature)) {
        $signature_data = str_replace('data:image/png;base64,', '', $signature);
        $signature_data = base64_decode($signature_data);
        $signature_file = 'signature_' . $id . '_' . time() . '.png';
        file_put_contents('uploads/signatures/' . $signature_file, $signature_data);
    }

    // Create uploads/signatures directory if not exists
    if (!file_exists('uploads/signatures')) {
        mkdir('uploads/signatures', 0777, true);
    }
    
    $stmt = $conn->prepare("UPDATE program_head_crediting SET credited_subjects=?, evaluation_remarks=?, signature_file=?, program_head_approved=1, status='approved' WHERE id=?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("sssi", $credited_subjects, $remarks, $signature_file, $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Create secretary_crediting table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS secretary_crediting (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT,
        student_id VARCHAR(50),
        student_name VARCHAR(100),
        credited_subjects TEXT,
        evaluation_remarks TEXT,
        signature_file VARCHAR(255),
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_to_dean_at TIMESTAMP NULL
    )");
    
    // Send to secretary for final processing
    $stmt = $conn->prepare("INSERT INTO secretary_crediting (request_id, student_id, student_name, credited_subjects, evaluation_remarks, signature_file, status, created_at) SELECT id, student_id, student_name, ?, ?, ?, 'pending', NOW() FROM program_head_crediting WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("sssi", $credited_subjects, $remarks, $signature_file, $id);
        $stmt->execute();
        $stmt->close();
    }

    // Notify student and log activity
    $stmt = $conn->prepare("SELECT student_id, student_name FROM program_head_crediting WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($student_id, $student_name);
        $stmt->fetch();
        $stmt->close();

        $notif_msg = "Your crediting request has been approved by the Program Head and sent to Secretary for final processing.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, is_read) VALUES (?, ?, 'crediting_approved', 0)");
        if ($notif_stmt) {
            $notif_stmt->bind_param("ss", $student_id, $notif_msg);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
        
        // Log activity
        $program_head_id = $_SESSION['user_id'];
        $action = "Approved Crediting Request";
        $details = "Approved crediting request for student: $student_name (ID: $student_id)";
        $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_type, action, details) VALUES (?, 'program_head', ?, ?)");
        if ($log_stmt) {
            $log_stmt->bind_param("iss", $program_head_id, $action, $details);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        // Send email notification
        require_once 'send_email_interview.php';
        sendCreditingApprovedEmail($student_id, $student_name);
    }

    echo json_encode(['success' => true, 'message' => 'Evaluation approved and sent to Secretary']);
    exit;
}

// Get pending requests (exclude dean_approved)
$pending_query = "SELECT * FROM program_head_crediting WHERE status IN ('pending', 'evaluating', 'warning') ORDER BY created_at DESC";
$pending_result = $conn->query($pending_query);
$pending_requests = $pending_result ? $pending_result->fetch_all(MYSQLI_ASSOC) : [];

// Get all requests (including approved)
$all_query = "SELECT * FROM program_head_crediting ORDER BY created_at DESC";
$all_result = $conn->query($all_query);
$all_requests = $all_result ? $all_result->fetch_all(MYSQLI_ASSOC) : [];



$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Head Dashboard - LSPU CCS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; }

        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem 2.5rem; height: calc(100vh - 65px); overflow-y: auto; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .card h2 { font-size: 1.5rem; color: #2d3748; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; color: #4a5568; }
        tr:hover td { background: #f7fafc; }
        .btn { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .btn:hover { background: #5568d3; }
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 20px; max-width: 750px; width: 90%; max-height: 90vh; overflow: hidden; box-shadow: 0 25px 80px rgba(0,0,0,0.4); }
        .modal-header { background: #667eea; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { color: white; font-size: 1.4rem; font-weight: 700; margin: 0; }
        .close-x { background: rgba(255,255,255,0.2); color: white; border: none; width: 35px; height: 35px; border-radius: 50%; font-size: 1.5rem; cursor: pointer; }
        .modal-body { padding: 2rem; max-height: calc(90vh - 150px); overflow-y: auto; }
        .modal-body textarea { width: 100%; padding: 0.875rem; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 0.95rem; resize: vertical; }
        .modal-body label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; margin-top: 1.25rem; }
        .info-box { background: #f0f4ff; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid #667eea; }
        .btn-group { display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #f1f5f9; }
        .cancel-btn { flex: 1; background: #f1f5f9; color: #475569; border: none; padding: 0.875rem; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .save-btn { flex: 1; background: #667eea; color: white; border: none; padding: 0.875rem; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .loading-modal { display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; }
        .loading-content { background: white; padding: 2rem; border-radius: 16px; text-align: center; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @media (max-width: 768px) { .main-container { margin-left: 0; padding: 1rem; } }
    </style>
</head>
<body>
    <?php include 'program_head_navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="card">
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <button onclick="showTab('pending')" id="pendingTab" class="btn" style="background: #667eea;">Pending (<?php echo count($pending_requests); ?>)</button>
                <button onclick="showTab('all')" id="allTab" class="btn" style="background: #6b7280;">All Requests (<?php echo count($all_requests); ?>)</button>
            </div>
            
            <div id="pendingSection">
            <h2><i class="fas fa-clipboard-check"></i> Pending Crediting Requests</h2>
            <div style="margin-bottom: 1.5rem;">
                <input type="text" id="searchPending" placeholder="🔍 Search by student name or ID..." onkeyup="filterTable('pendingTable')" style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
            </div>
            <table id="pendingTable">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Subjects</th>
                        <th>Date Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pending_requests)): ?>
                        <?php foreach($pending_requests as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['subjects_to_credit'] ?? 'N/A', 0, 50)) . '...'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button class="btn" onclick="evaluateRequest(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-clipboard-check"></i> Evaluate
                                    </button>
                                    <button class="btn" onclick="viewDocument(<?php echo $row['id']; ?>)" style="background: #10b981; font-size: 0.8rem;">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: #718096;">
                                No pending requests
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
            
            <div id="allSection" style="display: none;">
            <h2><i class="fas fa-list"></i> All Crediting Requests</h2>
            <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem;">
                <input type="text" id="searchAll" placeholder="🔍 Search by student name or ID..." onkeyup="filterTable('allTable')" style="flex: 1; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                <select id="filterStatus" onchange="filterTable('allTable')" style="padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="sent_to_dean">Sent to Dean</option>
                    <option value="dean_approved">Dean Approved</option>
                </select>
            </div>
            <table id="allTable">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Subjects</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_requests)): ?>
                        <?php foreach($all_requests as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['subjects_to_credit'] ?? 'N/A', 0, 50)) . '...'; ?></td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; 
                                      background: <?php 
                                      if ($row['status'] == 'dean_approved') echo '#d1fae5; color: #065f46';
                                      elseif ($row['status'] == 'approved' || $row['status'] == 'sent_to_dean') echo '#a7f3d0; color: #047857';
                                      else echo '#fef3c7; color: #92400e'; 
                                      ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <?php if ($row['status'] == 'pending' || $row['status'] == 'warning'): ?>
                                    <button class="btn" onclick="evaluateRequest(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-clipboard-check"></i> Evaluate
                                    </button>
                                <?php elseif ($row['status'] == 'approved'): ?>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button class="btn" onclick="viewDocument(<?php echo $row['id']; ?>)" style="background: #10b981; font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn" onclick="sendToDean(<?php echo $row['id']; ?>)" style="background: #f59e0b; font-size: 0.8rem;">
                                            <i class="fas fa-paper-plane"></i> Send to Dean
                                        </button>
                                        <?php if (!empty($row['signature_file'])): ?>
                                        <span style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                            <i class="fas fa-signature"></i> Signed
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($row['status'] == 'sent_to_dean'): ?>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button class="btn" onclick="viewDocument(<?php echo $row['id']; ?>)" style="background: #10b981; font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                            <i class="fas fa-clock"></i> Pending Dean Approval
                                        </span>
                                    </div>
                                <?php elseif ($row['status'] == 'dean_approved'): ?>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button class="btn" onclick="viewDocument(<?php echo $row['id']; ?>)" style="background: #10b981; font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <span style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> Dean Approved
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #6b7280; font-style: italic;">No action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #718096;">
                                No requests found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </main>

    <div id="loadingModal" class="loading-modal">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3>Processing Evaluation...</h3>
            <p>Generating document with digital signature</p>
        </div>
    </div>

    <div id="evaluateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-check"></i> Evaluate Crediting Request</h3>
                <button class="close-x" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="evalRequestId">
                <div class="info-box">
                    <div><strong>Student:</strong> <span id="evalStudentName"></span> (<span id="evalStudentId"></span>)</div>
                    <div><strong>Student Type:</strong> <span id="evalStudentType"></span></div>
                    <div style="margin-top: 0.5rem;"><strong>Requested Subjects:</strong></div>
                    <div id="evalSubjects" style="white-space: pre-wrap; font-size: 0.9rem; background: #f8f9fa; padding: 0.75rem; border-radius: 6px;"></div>
                    <div style="margin-top: 0.5rem;"><strong>Previous School Info:</strong></div>
                    <div id="evalTranscript" style="white-space: pre-wrap; font-size: 0.9rem; background: #f8f9fa; padding: 0.75rem; border-radius: 6px;"></div>
                    <div id="transcriptFileDiv" style="margin-top: 0.5rem; display: none;">
                        <strong>Transcript File:</strong> <a id="transcriptFileLink" href="#" target="_blank" style="color: #667eea;">View Transcript</a>
                    </div>
                </div>

                <label><i class="fas fa-list-check"></i> Subjects to be Credited *</label>
                <textarea id="creditedSubjects" rows="5" placeholder="List the subjects that will be credited" required></textarea>

                <label><i class="fas fa-comment-dots"></i> Evaluation Remarks</label>
                <textarea id="evalRemarks" rows="3" placeholder="Additional notes"></textarea>
                
                <label><i class="fas fa-signature"></i> Digital Signature *</label>
                <div style="border: 2px solid #e2e8f0; border-radius: 8px; background: white;">
                    <canvas id="signaturePad" width="400" height="150" style="display: block; cursor: crosshair;"></canvas>
                </div>
                <div style="margin-top: 0.5rem; text-align: right;">
                    <button type="button" onclick="clearSignature()" style="background: #f59e0b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">Clear Signature</button>
                </div>

                <div class="btn-group">
                    <button class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button class="save-btn" id="approveBtn" onclick="saveEvaluation()"><i class="fas fa-save"></i> Approve & Generate Document</button>
                </div>
                
                <div id="documentPreview" style="display: none; margin-top: 1.5rem; padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h4 style="color: #667eea; margin-bottom: 1rem;"><i class="fas fa-file-alt"></i> Generated Document</h4>
                    <div style="text-align: center;">
                        <button class="btn" onclick="viewDocument()" style="margin-right: 0.5rem;"><i class="fas fa-eye"></i> View Document</button>
                        <button class="btn" onclick="downloadDocument()" style="background: #10b981;"><i class="fas fa-download"></i> Download PDF</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Signature pad setup
        let canvas, ctx, isDrawing = false;
        
        document.addEventListener('DOMContentLoaded', function() {
            canvas = document.getElementById('signaturePad');
            ctx = canvas.getContext('2d');
            
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('touchstart', startDrawing);
            canvas.addEventListener('touchmove', draw);
            canvas.addEventListener('touchend', stopDrawing);
        });
        
        function startDrawing(e) {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX || e.touches[0].clientX) - rect.left;
            const y = (e.clientY || e.touches[0].clientY) - rect.top;
            ctx.beginPath();
            ctx.moveTo(x, y);
        }
        
        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX || e.touches[0].clientX) - rect.left;
            const y = (e.clientY || e.touches[0].clientY) - rect.top;
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
            ctx.lineTo(x, y);
            ctx.stroke();
        }
        
        function stopDrawing() {
            isDrawing = false;
        }
        
        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
        
        function isSignatureEmpty() {
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            return !imageData.data.some(channel => channel !== 0);
        }
        
        function evaluateRequest(id) {
            fetch(`get_crediting_details.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('evalRequestId').value = id;
                    document.getElementById('evalStudentName').textContent = data.student_name;
                    document.getElementById('evalStudentId').textContent = data.student_id;
                    document.getElementById('evalStudentType').textContent = data.student_type;
                    document.getElementById('evalSubjects').textContent = data.subject_code || 'N/A';
                    document.getElementById('evalTranscript').textContent = data.school_taken || 'N/A';
                    
                    // Show transcript file if available
                    if (data.transcript_file) {
                        document.getElementById('transcriptFileDiv').style.display = 'block';
                        document.getElementById('transcriptFileLink').href = 'uploads/transcripts/' + data.transcript_file;
                    } else {
                        document.getElementById('transcriptFileDiv').style.display = 'none';
                    }
                    
                    document.getElementById('creditedSubjects').value = '';
                    document.getElementById('evalRemarks').value = '';
                    document.getElementById('documentPreview').style.display = 'none';
                    document.getElementById('evaluateModal').style.display = 'flex';
                });
        }

        function closeModal() {
            document.getElementById('evaluateModal').style.display = 'none';
        }

        function saveEvaluation() {
            const id = document.getElementById('evalRequestId').value;
            const credited = document.getElementById('creditedSubjects').value.trim();
            const remarks = document.getElementById('evalRemarks').value.trim();
            const approveBtn = document.getElementById('approveBtn');

            if (!credited) {
                alert('Please specify subjects to be credited');
                return;
            }
            
            if (isSignatureEmpty()) {
                alert('Please provide your digital signature');
                return;
            }
            
            // Show loading modal and disable button
            document.getElementById('loadingModal').style.display = 'flex';
            approveBtn.disabled = true;
            approveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Get signature as base64
            const signatureData = canvas.toDataURL();

            const formData = new FormData();
            formData.append('evaluate_request', '1');
            formData.append('id', id);
            formData.append('credited_subjects', credited);
            formData.append('remarks', remarks);
            formData.append('signature', signatureData);

            fetch('program_head_requests.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                console.log('Response status:', res.status);
                return res.text();
            })
            .then(text => {
                console.log('Response text:', text);
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid JSON response: ' + text);
                }
                
                // Hide loading modal
                document.getElementById('loadingModal').style.display = 'none';
                
                if (data.success) {
                    // Show document preview section
                    document.getElementById('documentPreview').style.display = 'block';
                    window.currentRequestId = id;
                    approveBtn.innerHTML = '<i class="fas fa-check"></i> Approved!';
                    approveBtn.style.background = '#10b981';
                    alert('Evaluation approved! Document generated with your digital signature.');
                } else {
                    approveBtn.disabled = false;
                    approveBtn.innerHTML = '<i class="fas fa-save"></i> Approve & Generate Document';
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                document.getElementById('loadingModal').style.display = 'none';
                approveBtn.disabled = false;
                approveBtn.innerHTML = '<i class="fas fa-save"></i> Approve & Generate Document';
                alert('Network error occurred');
            });
        }
        
        function viewDocument() {
            const id = window.currentRequestId;
            window.open(`generate_crediting_document.php?id=${id}&action=view`, '_blank');
        }
        
        function downloadDocument() {
            const id = window.currentRequestId;
            window.open(`generate_crediting_document.php?id=${id}&action=download`, '_blank');
        }
        
        function showTab(tab) {
            if (tab === 'pending') {
                document.getElementById('pendingSection').style.display = 'block';
                document.getElementById('allSection').style.display = 'none';
                document.getElementById('pendingTab').style.background = '#667eea';
                document.getElementById('allTab').style.background = '#6b7280';
            } else {
                document.getElementById('pendingSection').style.display = 'none';
                document.getElementById('allSection').style.display = 'block';
                document.getElementById('pendingTab').style.background = '#6b7280';
                document.getElementById('allTab').style.background = '#667eea';
            }
        }
        
        function viewDocument(id) {
            window.open(`generate_crediting_document.php?id=${id}&action=view`, '_blank');
        }
        
        function downloadDocument(id) {
            window.open(`generate_crediting_document.php?id=${id}&action=download`, '_blank');
        }
        
        function sendToDean(id) {
            if (confirm('Send this crediting request to Dean for final approval?')) {
                fetch('program_head_requests.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `send_to_dean=1&id=${id}`
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                });
            }
        }
        
        function filterTable(tableId) {
            const searchId = tableId === 'pendingTable' ? 'searchPending' : 'searchAll';
            const input = document.getElementById(searchId);
            const filter = input.value.toUpperCase();
            const table = document.getElementById(tableId);
            const tr = table.getElementsByTagName('tr');
            const statusFilter = document.getElementById('filterStatus')?.value.toLowerCase() || '';

            for (let i = 1; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName('td')[0];
                const tdId = tr[i].getElementsByTagName('td')[1];
                const tdStatus = tr[i].getElementsByTagName('td')[3];
                
                if (tdName && tdId) {
                    const txtName = tdName.textContent || tdName.innerText;
                    const txtId = tdId.textContent || tdId.innerText;
                    const txtStatus = tdStatus ? (tdStatus.textContent || tdStatus.innerText).toLowerCase() : '';
                    
                    const matchesSearch = txtName.toUpperCase().indexOf(filter) > -1 || txtId.toUpperCase().indexOf(filter) > -1;
                    const matchesStatus = !statusFilter || txtStatus.indexOf(statusFilter.replace('_', ' ')) > -1;
                    
                    if (matchesSearch && matchesStatus) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }

    </script>
</body>
</html>