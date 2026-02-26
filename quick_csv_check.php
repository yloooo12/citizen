<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    $handle = fopen($file, 'r');
    
    echo "<h2>CSV File Analysis</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Row</th><th>Col 0 (Course)</th><th>Col 1 (Program)</th><th>Col 2 (Name)</th><th>Col 3 (Grade)</th><th>Status</th></tr>";
    
    $row_num = 0;
    while (($data = fgetcsv($handle)) !== FALSE && $row_num < 20) {
        $row_num++;
        
        $col0 = isset($data[0]) ? trim($data[0]) : '';
        $col1 = isset($data[1]) ? trim($data[1]) : '';
        $col2 = isset($data[2]) ? trim($data[2]) : '';
        $col3 = isset($data[3]) ? trim($data[3]) : '';
        
        $status = '';
        if (!$col2) $status .= 'NO NAME | ';
        if (!$col0) $status .= 'NO COURSE | ';
        if ($col2 && $col0) $status = 'OK';
        
        echo "<tr>";
        echo "<td>$row_num</td>";
        echo "<td>" . htmlspecialchars($col0) . "</td>";
        echo "<td>" . htmlspecialchars($col1) . "</td>";
        echo "<td>" . htmlspecialchars($col2) . "</td>";
        echo "<td>" . htmlspecialchars($col3) . "</td>";
        echo "<td style='color:" . ($status == 'OK' ? 'green' : 'red') . "'>$status</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    fclose($handle);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Quick CSV Check</title></head>
<body>
    <h1>Quick CSV Check</h1>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="excel_file" accept=".csv" required>
        <button type="submit">Check CSV</button>
    </form>
</body>
</html>
