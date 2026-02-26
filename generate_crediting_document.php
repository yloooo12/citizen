<?php
// Simple HTML output instead of PDF
header('Content-Type: text/html; charset=utf-8');

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? 'view';

// Get request details
$result = $conn->query("SELECT * FROM program_head_crediting WHERE id='$id'");
if (!$result || $result->num_rows == 0) {
    // Fallback: get the latest record
    $result = $conn->query("SELECT * FROM program_head_crediting ORDER BY created_at DESC LIMIT 1");
    if (!$result || $result->num_rows == 0) {
        die("No crediting requests found.");
    }
}

$request = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Subject Crediting Certificate</title>
    <style>
        @page { size: A4; margin: 0; }
        body { 
            font-family: 'Times New Roman', serif; 
            margin: 0; 
            padding: 40px; 
            background: white;
            color: #000;
            line-height: 1.4;
            font-size: 14px;
        }
        .document {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 40px;
            box-sizing: border-box;
        }
        .letterhead {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .letterhead h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .letterhead h2 {
            font-size: 14px;
            margin: 5px 0;
            font-weight: normal;
        }
        .letterhead h3 {
            font-size: 16px;
            margin: 15px 0 0 0;
            font-weight: bold;
            text-decoration: underline;
        }
        .doc-info {
            text-align: right;
            margin-bottom: 30px;
            font-size: 12px;
        }
        .content {
            margin-bottom: 40px;
        }
        .field {
            margin: 15px 0;
        }
        .field strong {
            display: inline-block;
            width: 150px;
            font-weight: bold;
        }
        .subjects-section {
            margin: 25px 0;
        }
        .subjects-box {
            border: 2px solid #000;
            padding: 20px;
            margin: 10px 0;
            background: #f9f9f9;
            min-height: 100px;
        }
        .certification {
            margin: 30px 0;
            text-align: justify;
            font-style: italic;
        }
        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 2px solid #000;
            margin-top: 10px;
            padding-top: 5px;
            font-weight: bold;
        }
        .footer {
            position: absolute;
            bottom: 30px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        @media print {
            body { margin: 0; padding: 0; }
            .document { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="document">
        <div class="letterhead">
            <h1>Republic of the Philippines</h1>
            <h1>Laguna State Polytechnic University</h1>
            <h2>College of Computer Studies</h2>
            <h2>Sta. Cruz, Laguna</h2>
            <h3>Certificate of Subject Crediting</h3>
        </div>
        
        <div class="doc-info">
            <strong>Document No.:</strong> CRED-<?php echo str_pad($id, 6, '0', STR_PAD_LEFT); ?><br>
            <strong>Date Issued:</strong> <?php echo date('F d, Y'); ?>
        </div>
        
        <div class="content">
            <div class="field">
                <strong>Student Name:</strong> <?php echo strtoupper(htmlspecialchars($request['student_name'])); ?>
            </div>
            <div class="field">
                <strong>Student ID:</strong> <?php echo htmlspecialchars($request['student_id']); ?>
            </div>
            <div class="field">
                <strong>Program:</strong> Bachelor of Science in Information Technology
            </div>
            <div class="field">
                <strong>Classification:</strong> <?php echo ucfirst(htmlspecialchars($request['student_type'] ?? 'Transferee')); ?>
            </div>
            
            <div class="subjects-section">
                <h4 style="margin-bottom: 10px; text-decoration: underline;">SUBJECTS CREDITED:</h4>
                <div class="subjects-box">
                    <?php echo nl2br(htmlspecialchars($request['subjects_to_credit'] ?? 'No subjects specified')); ?>
                </div>
            </div>
            
            <div class="field">
                <strong>Previous Institution:</strong><br>
                <div style="margin-left: 150px; margin-top: 5px;">
                    <?php echo nl2br(htmlspecialchars($request['transcript_info'] ?? 'Not specified')); ?>
                </div>
            </div>
            
            <div class="certification">
                This is to certify that the above-mentioned student has been granted credit for the subjects listed above based on the evaluation of transcripts and academic records from the previous institution. This crediting is in accordance with the policies and standards of Laguna State Polytechnic University.
            </div>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div style="position: relative; height: 60px; margin-bottom: 0px; display: flex; align-items: end; justify-content: center;">
                    <?php if (!empty($request['signature_file'])): ?>
                        <img src="uploads/signatures/<?php echo htmlspecialchars($request['signature_file']); ?>" style="max-height: 60px; max-width: 180px; position: absolute; bottom: -5px; left: 50%; transform: translateX(-50%);" alt="Program Head Signature">
                    <?php else: ?>
                        <div style="font-style: italic; color: #666;">Digital Signature</div>
                    <?php endif; ?>
                </div>
                <div class="signature-line">
                    PROGRAM HEAD
                </div>
                <div style="font-size: 12px; margin-top: 5px;">
                    Program Head<br>
                    College of Computer Studies
                </div>
            </div>
            
            <div class="signature-box">
                <div style="position: relative; height: 60px; margin-bottom: 0px; display: flex; align-items: end; justify-content: center;">
                    <?php if (!empty($request['dean_signature_file'])): ?>
                        <img src="uploads/signatures/<?php echo htmlspecialchars($request['dean_signature_file']); ?>" style="max-height: 60px; max-width: 180px; position: absolute; bottom: -5px; left: 50%; transform: translateX(-50%);" alt="Dean Signature">
                    <?php else: ?>
                        <div style="font-style: italic; color: #666;">Digital Signature</div>
                    <?php endif; ?>
                </div>
                <div class="signature-line">
                    DEAN
                </div>
                <div style="font-size: 12px; margin-top: 5px;">
                    Dean<br>
                    College of Computer Studies
                </div>
            </div>
        </div>
        
        <div class="footer">
            This document is computer-generated and issued by the LSPU-CCS Student Services System.<br>
            Generated on <?php echo date('F d, Y \a\t g:i A'); ?> | Document ID: CRED-<?php echo str_pad($id, 6, '0', STR_PAD_LEFT); ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>