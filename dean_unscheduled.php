<?php
session_start();

/**
 * OPTIONAL: Turn this to true while debugging email issues.
 * Check your PHP error log after approving a request.
 */
$ENABLE_MAIL_DEBUG = true;

// Handle approval FIRST before any output
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["approve_request"])) {
    ob_start();
    
    if (!isset($_SESSION["user_id"]) || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] != 3) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $conn = new mysqli("localhost", "root", "", "student_services");
    if ($conn->connect_error) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $id = intval($_POST['id']);
    $signature = $_POST['signature'] ?? '';
    $remarks = $conn->real_escape_string(trim($_POST['remarks']));
    
    // Save signature
    $signature_file = null;
    if (!empty($signature)) {
        if (!file_exists('uploads/signatures')) {
            mkdir('uploads/signatures', 0777, true);
        }
        $signature_data = str_replace('data:image/png;base64,', '', $signature);
        $signature_data = base64_decode($signature_data);
        $signature_file = 'dean_unscheduled_' . $id . '_' . time() . '.png';
        file_put_contents('uploads/signatures/' . $signature_file, $signature_data);
    }
    
    // Update request as approved
    $stmt = $conn->prepare("UPDATE unscheduled_requests 
        SET status='approved', dean_signature=?, dean_remarks=?, approved_at=NOW() 
        WHERE id=?");
    $stmt->bind_param("ssi", $signature_file, $remarks, $id);
    $stmt->execute();
    $stmt->close();
    
    // Get request details
    $result = $conn->query("SELECT * FROM unscheduled_requests WHERE id=$id");
    if (!$result || !($request = $result->fetch_assoc())) {
        $conn->close();
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }
    
    // Notify student - insert in notifications table
    $notif_msg = $conn->real_escape_string("✅ Your unscheduled subject request has been approved by the Dean. Document is ready for download.");
    $notif_insert = $conn->query("
        INSERT INTO notifications (user_id, message, type, is_read, created_at) 
        VALUES ('{$request['student_id']}', '$notif_msg', 'unscheduled_approved', 0, NOW())
    ");
    if (!$notif_insert) {
        error_log('Notification insert failed: ' . $conn->error);
    }
    
    // =============================
    // EMAIL SENDING TO STUDENT
    // =============================
    if (!empty($request['student_email'])) {

        require_once 'PHPMailer/src/Exception.php';
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        require_once 'email_config.php'; // Make sure constants are correct here
        
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Debug for troubleshooting (logs to error_log)
            global $ENABLE_MAIL_DEBUG;
            if ($ENABLE_MAIL_DEBUG) {
                $mail->SMTPDebug  = 2; // 0 = off, 2 = verbose
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer debug level $level: $str");
                };
            }

            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = SMTP_AUTH;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = 'tls';       // or 'ssl' depende sa provider mo
            $mail->Port       = SMTP_PORT;   // usually 587 for tls, 465 for ssl

            // SSL options
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($request['student_email'], $request['student_name']);

            $mail->Subject = 'Unscheduled Subject Request Approved - LSPU CCS';
            $mail->Body    = 
"Dear {$request['student_name']},

Good news! Your unscheduled subject request has been APPROVED by the Dean.

Subject Code: {$request['subject_code']}
Subject Name: {$request['subject_name']}
Approved Date: " . date('M d, Y h:i A') . "

Your approval document is now ready for download. Please log in to the student portal to download the document and submit it to the Registrar's Office.

Thank you.
LSPU-CCS
Dean's Office";

            if (!$mail->send()) {
                error_log('Mail send failed: ' . $mail->ErrorInfo);
            } else {
                error_log('Mail sent successfully to ' . $request['student_email']);
            }

        } catch (Exception $e) {
            error_log('PHPMailer exception: ' . $e->getMessage());
        }
    } else {
        // Student email is empty – log for debugging
        error_log("No student_email found for unscheduled request id $id (student_id: {$request['student_id']})");
    }
    
    // Notify secretary
    $secRes = $conn->query("SELECT id_number FROM users WHERE is_admin=2 LIMIT 1");
    $secretary = $secRes ? $secRes->fetch_assoc() : null;
    if ($secretary) {
        $sec_notif = $conn->real_escape_string(
            "📋 Unscheduled subject request from {$request['student_name']} has been approved by Dean. Document ready for processing."
        );
        $conn->query("
            INSERT INTO notifications (user_id, message, type, is_read, created_at) 
            VALUES ('{$secretary['id_number']}', '$sec_notif', 'unscheduled_approved', 0, NOW())
        ");
    }
    
    // Notify Dean (for their own records)
    $dean_notif = $conn->real_escape_string(
        "✅ You approved unscheduled subject request from {$request['student_name']} ({$request['subject_code']} - {$request['subject_name']})"
    );
    $conn->query("
        INSERT INTO dean_notifications (message, type, is_read, created_at) 
        VALUES ('$dean_notif', 'unscheduled_approved', 0, NOW())
    ");
    
    // Also resolve UNSCHEDULED alert
    $conn->query("
        UPDATE academic_alerts 
        SET is_resolved=1 
        WHERE student_id='{$request['student_id']}' AND alert_type='UNSCHEDULED'
    ");
    
    $conn->close();
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Request approved successfully']);
    exit;
}

// ==============================
// REGULAR PAGE LOAD (HTML PART)
// ==============================

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] != 3) {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? '';
$last_name  = $_SESSION["last_name"] ?? '';

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Get filter
$filter = $_GET['filter'] ?? 'pending';

// Get requests based on filter
$requests = [];
if ($filter == 'all') {
    $result = $conn->query("SELECT * FROM unscheduled_requests ORDER BY date_submitted DESC");
} elseif ($filter == 'approved') {
    $result = $conn->query("SELECT * FROM unscheduled_requests WHERE status='approved' ORDER BY approved_at DESC");
} else {
    $result = $conn->query("SELECT * FROM unscheduled_requests WHERE status='pending' ORDER BY date_submitted DESC");
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unscheduled Subject Requests - Dean</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem; transition: all 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        .card h2 { font-size: 1.5rem; color: #2d3748; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; color: #4a5568; }
        tr:hover td { background: #f7fafc; }
        .btn { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .btn:hover { background: #5568d3; }
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 16px; max-width: 700px; width: 90%; max-height: 90vh; overflow: hidden; }
        .modal-header { background: #667eea; padding: 1.5rem; color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 2rem; max-height: calc(90vh - 150px); overflow-y: auto; }
        .close-x { background: rgba(255,255,255,0.2); color: white; border: none; width: 35px; height: 35px; border-radius: 50%; font-size: 1.5rem; cursor: pointer; }
        .info-box { background: #f0f4ff; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid #667eea; }
        label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; margin-top: 1rem; }
        textarea { width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; resize: vertical; }
        canvas { border: 2px solid #e2e8f0; border-radius: 8px; cursor: crosshair; display: block; }
        .btn-group { display: flex; gap: 1rem; margin-top: 2rem; }
        .cancel-btn { flex: 1; background: #f1f5f9; color: #475569; border: none; padding: 0.875rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .approve-btn { flex: 1; background: #667eea; color: white; border: none; padding: 0.875rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .loading-modal { display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; }
        .loading-content { background: white; padding: 2rem; border-radius: 16px; text-align: center; min-width: 300px; }
        .loading-spinner { border: 4px solid #f3f4f6; border-top: 4px solid #667eea; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .loading-text { color: #2d3748; font-weight: 600; margin-bottom: 0.5rem; }
        .loading-subtext { color: #718096; font-size: 0.85rem; }
        @media (max-width: 768px) { .main-container { margin-left: 0; padding: 1rem; } }
    </style>
</head>
<body>
    <?php include 'dean_navbar.php'; ?>
    
    <div class="main-container" id="mainContainer">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0;"><i class="fas fa-calendar-alt"></i> Unscheduled Subject Requests</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="?filter=pending" style="padding: 0.5rem 1rem; background: <?php echo $filter == 'pending' ? '#667eea' : '#f1f5f9'; ?>; color: <?php echo $filter == 'pending' ? 'white' : '#475569'; ?>; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Pending</a>
                    <a href="?filter=approved" style="padding: 0.5rem 1rem; background: <?php echo $filter == 'approved' ? '#667eea' : '#f1f5f9'; ?>; color: <?php echo $filter == 'approved' ? 'white' : '#475569'; ?>; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Approved</a>
                    <a href="?filter=all" style="padding: 0.5rem 1rem; background: <?php echo $filter == 'all' ? '#667eea' : '#f1f5f9'; ?>; color: <?php echo $filter == 'all' ? 'white' : '#475569'; ?>; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">All</a>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Subject</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach($requests as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_code'] . ' - ' . $row['subject_name']); ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['date_submitted'])); ?></td>
                            <td>
                                <?php if ($row['status'] == 'approved'): ?>
                                    <span style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">Approved</span>
                                <?php else: ?>
                                    <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'approved'): ?>
                                    <button class="btn" onclick="window.open('generate_unscheduled_document.php?id=<?php echo $row['id']; ?>&action=view', '_blank')" style="background: #10b981;">
                                        <i class="fas fa-file-pdf"></i> View Document
                                    </button>
                                <?php else: ?>
                                    <button class="btn" onclick="reviewRequest(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-eye"></i> Review
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #718096;">No requests found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-check"></i> Review Unscheduled Subject Request</h3>
                <button class="close-x" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="requestId">
                <div class="info-box" id="requestInfo"></div>
                
                <label><i class="fas fa-comment"></i> Dean Remarks</label>
                <textarea id="remarks" rows="3" placeholder="Add your remarks..."></textarea>
                
                <label><i class="fas fa-signature"></i> Digital Signature *</label>
                <canvas id="signaturePad" width="600" height="150"></canvas>
                <div style="margin-top: 0.5rem; text-align: right;">
                    <button onclick="clearSignature()" style="background: #f59e0b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer;">Clear</button>
                </div>
                
                <div class="btn-group">
                    <button class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button class="approve-btn" id="approveBtn" onclick="approveRequest()">
                        <i class="fas fa-check"></i> Approve & Generate Document
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="loading-modal" id="loadingModal">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Processing Approval...</div>
            <div class="loading-subtext">Sending notifications and generating document</div>
        </div>
    </div>

    <script>
        let canvas, ctx, isDrawing = false;
        
        document.addEventListener('DOMContentLoaded', function() {
            canvas = document.getElementById('signaturePad');
            if (canvas) {
                ctx = canvas.getContext('2d');
                
                canvas.addEventListener('mousedown', startDrawing);
                canvas.addEventListener('mousemove', draw);
                canvas.addEventListener('mouseup', stopDrawing);
                canvas.addEventListener('mouseleave', stopDrawing);

                canvas.addEventListener('touchstart', startDrawing);
                canvas.addEventListener('touchmove', draw);
                canvas.addEventListener('touchend', stopDrawing);
            }
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
            if (ctx && canvas) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        }
        
        function isSignatureEmpty() {
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            // Check if any non-transparent pixel exists
            return !imageData.data.some(channel => channel !== 0);
        }
        
        function reviewRequest(id) {
            fetch(`get_unscheduled_details.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('requestId').value = id;
                    document.getElementById('requestInfo').innerHTML = `
                        <div><strong>Student:</strong> ${data.student_name} (${data.student_id})</div>
                        <div><strong>Email:</strong> ${data.student_email || 'N/A'}</div>
                        <div><strong>Date Submitted:</strong> ${data.date_submitted}</div>
                        <div style="margin-top: 1rem;"><strong>Subject Code:</strong> ${data.subject_code || 'N/A'}</div>
                        <div><strong>Subject Name:</strong> ${data.subject_name || 'N/A'}</div>
                        <div style="margin-top: 1rem;"><strong>Reason for Request:</strong></div>
                        <div style="white-space: pre-wrap; background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 0.5rem; max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0;">
                            ${data.reason || 'No reason provided'}
                        </div>
                        <div style="margin-top: 1rem;"><strong>Evaluation of Grades:</strong> 
                            ${data.eval_file 
                                ? `<a href="uploads/evaluations/${data.eval_file}" target="_blank" style="color: #667eea; text-decoration: underline;"><i class="fas fa-file-pdf"></i> View Document</a>` 
                                : 'No file uploaded'
                            }
                        </div>
                    `;
                    document.getElementById('reviewModal').style.display = 'flex';
                })
                .catch(err => {
                    alert('Error loading request details');
                    console.error(err);
                });
        }
        
        function closeModal() {
            document.getElementById('reviewModal').style.display = 'none';
            clearSignature();
            document.getElementById('remarks').value = '';
        }
        
        function approveRequest() {
            const id = document.getElementById('requestId').value;
            const remarks = document.getElementById('remarks').value.trim();
            
            if (!canvas || !ctx) {
                alert('Signature pad is not initialized.');
                return;
            }

            if (isSignatureEmpty()) {
                alert('Please provide your digital signature');
                return;
            }
            
            document.getElementById('reviewModal').style.display = 'none';
            document.getElementById('loadingModal').style.display = 'flex';
            
            const signatureData = canvas.toDataURL();
            const formData = new FormData();
            formData.append('approve_request', '1');
            formData.append('id', id);
            formData.append('remarks', remarks);
            formData.append('signature', signatureData);
            
            fetch('dean_approve_unscheduled.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loadingModal').style.display = 'none';
                if (data.success) {
                    alert('✅ Request approved successfully!\n\n📧 Email notification attempted\n📄 Document ready for download (if configured)');
                    window.open(`generate_unscheduled_document.php?id=${id}&action=view`, '_blank');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert(data.message || 'Error processing request');
                }
            })
            .catch(error => {
                document.getElementById('loadingModal').style.display = 'none';
                alert('Error: ' + error.message);
                console.error('Approval error:', error);
            });
        }
    </script>
</body>
</html>
