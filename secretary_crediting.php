<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "secretary") {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Handle send to dean
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_to_dean"])) {
    $id = $_POST['id'];
    
    // Create dean_crediting table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS dean_crediting (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secretary_request_id INT,
        student_id VARCHAR(50),
        student_name VARCHAR(100),
        credited_subjects TEXT,
        evaluation_remarks TEXT,
        signature_file VARCHAR(255),
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        dean_approved_at TIMESTAMP NULL
    )");
    
    // Update status to sent to dean
    $stmt = $conn->prepare("UPDATE secretary_crediting SET status='sent_to_dean', sent_to_dean_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Insert into dean_crediting table
    $stmt = $conn->prepare("INSERT INTO dean_crediting (secretary_request_id, student_id, student_name, credited_subjects, evaluation_remarks, signature_file, status, created_at) SELECT id, student_id, student_name, credited_subjects, evaluation_remarks, signature_file, 'pending', NOW() FROM secretary_crediting WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Documents prepared and sent to Dean']);
    exit;
}

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

// Get pending requests
$result = $conn->query("SELECT * FROM secretary_crediting WHERE status='pending' ORDER BY created_at DESC");
$requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Debug: Check total records and show all data
$debug_result = $conn->query("SELECT COUNT(*) as total FROM secretary_crediting");
$total_count = $debug_result ? $debug_result->fetch_assoc()['total'] : 0;

$all_result = $conn->query("SELECT * FROM secretary_crediting ORDER BY created_at DESC");
$all_requests = $all_result ? $all_result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Crediting Management - LSPU CCS</title>
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
        .btn { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }
        .btn:hover { background: #5568d3; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        @media (max-width: 768px) { .main-container { margin-left: 0; padding: 1rem; } }
    </style>
</head>
<body>
    <?php include 'secretary_navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="card">
            <h2><i class="fas fa-clipboard-check"></i> Crediting Management</h2>
            <p style="color: #718096; margin-bottom: 1.5rem; font-size: 0.95rem;">Prepare documents and send to Dean for final approval</p>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Credited Subjects</th>
                        <th>Program Head Remarks</th>
                        <th>Date Approved</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach($requests as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['credited_subjects'], 0, 50)) . '...'; ?></td>
                            <td><?php echo htmlspecialchars($row['evaluation_remarks'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <button class="btn" onclick="viewRequestDetails(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-eye"></i> Review & Send to Dean
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #718096;">
                                No pending requests (Total records: <?php echo $total_count; ?>)<br>
                                <?php if (!empty($all_requests)): ?>
                                    <small>All records: 
                                    <?php foreach($all_requests as $req): ?>
                                        <?php echo $req['student_name'] . ' (' . $req['status'] . '), '; ?>
                                    <?php endforeach; ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal for request details -->
    <div id="requestModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; max-width: 800px; width: 90%; max-height: 90vh; overflow: hidden; box-shadow: 0 25px 80px rgba(0,0,0,0.4);">
            <div style="background: #667eea; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color: white; font-size: 1.4rem; font-weight: 700; margin: 0;"><i class="fas fa-file-alt"></i> Review Crediting Request</h3>
                <button onclick="closeModal()" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 35px; height: 35px; border-radius: 50%; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div style="padding: 2rem; max-height: calc(90vh - 150px); overflow-y: auto;">
                <input type="hidden" id="currentRequestId">
                
                <div style="background: #f0f4ff; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid #667eea;">
                    <div><strong>Student Name:</strong> <span id="modalStudentName"></span></div>
                    <div><strong>Student ID:</strong> <span id="modalStudentId"></span></div>
                    <div><strong>Program:</strong> Bachelor of Science in Information Technology</div>
                    <div><strong>Classification:</strong> <span id="modalStudentType"></span></div>
                </div>
                
                <div style="margin: 1.5rem 0;">
                    <h4 style="margin-bottom: 10px; text-decoration: underline;">SUBJECTS CREDITED:</h4>
                    <div id="modalCreditedSubjects" style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 8px; white-space: pre-wrap;"></div>
                </div>
                
                <div style="margin: 1.5rem 0;">
                    <h4 style="margin-bottom: 10px;">Program Head Remarks:</h4>
                    <div id="modalRemarks" style="background: #f8f9fa; padding: 10px; border-radius: 6px;"></div>
                </div>
                
                <div style="margin: 1.5rem 0;">
                    <h4 style="margin-bottom: 10px;">Program Head Signature:</h4>
                    <div id="modalSignature" style="text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: white;"></div>
                </div>
                
                <div style="text-align: center; margin: 2rem 0;">
                    <button class="btn" onclick="viewFullDocument()" style="margin-right: 1rem;">
                        <i class="fas fa-file-pdf"></i> View Full Document
                    </button>
                    <button class="btn btn-success" onclick="confirmSendToDean()">
                        <i class="fas fa-paper-plane"></i> Send to Dean
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewRequestDetails(id) {
            fetch(`get_secretary_crediting_details.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('currentRequestId').value = id;
                    document.getElementById('modalStudentName').textContent = data.student_name;
                    document.getElementById('modalStudentId').textContent = data.student_id;
                    document.getElementById('modalStudentType').textContent = data.student_type || 'N/A';
                    document.getElementById('modalCreditedSubjects').textContent = data.credited_subjects || 'N/A';
                    document.getElementById('modalRemarks').textContent = data.evaluation_remarks || 'No remarks';
                    
                    // Show signature if available
                    const signatureDiv = document.getElementById('modalSignature');
                    if (data.signature_file) {
                        signatureDiv.innerHTML = `<img src="uploads/signatures/${data.signature_file}" style="max-height: 80px; max-width: 200px;" alt="Program Head Signature">`;
                    } else {
                        signatureDiv.innerHTML = '<em style="color: #666;">No signature available</em>';
                    }
                    
                    window.currentDocumentId = data.request_id;
                    document.getElementById('requestModal').style.display = 'flex';
                });
        }
        
        function closeModal() {
            document.getElementById('requestModal').style.display = 'none';
        }
        
        function viewFullDocument() {
            window.open(`generate_crediting_document.php?id=${window.currentDocumentId}&action=view`, '_blank');
        }
        
        function confirmSendToDean() {
            const id = document.getElementById('currentRequestId').value;
            if (confirm('Send this crediting request to Dean for final approval?')) {
                const formData = new FormData();
                formData.append('send_to_dean', '1');
                formData.append('id', id);

                fetch('secretary_crediting.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Documents sent to Dean successfully!');
                        closeModal();
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>