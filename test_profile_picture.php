<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    die("Please login first");
}

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION["user_id"];

// Check if profile_picture column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
if ($result->num_rows == 0) {
    echo "❌ Column 'profile_picture' does NOT exist in users table<br>";
    echo "Adding column now...<br>";
    if ($conn->query("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL")) {
        echo "✅ Column added successfully!<br>";
    } else {
        echo "❌ Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ Column 'profile_picture' exists<br>";
}

// Check current profile picture value
$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_picture);
$stmt->fetch();
$stmt->close();

echo "<br>Current profile_picture value: " . ($profile_picture ? $profile_picture : "NULL") . "<br>";

// Test upload form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Profile Picture Upload</title>
</head>
<body>
    <h2>Test Profile Picture Upload</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="test_picture" accept="image/*" required>
        <button type="submit" name="test_upload">Upload Test Picture</button>
    </form>

    <?php
    if (isset($_POST['test_upload']) && isset($_FILES['test_picture'])) {
        echo "<h3>Upload Test Results:</h3>";
        
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
            chmod($upload_dir, 0777);
            echo "✅ Created uploads directory<br>";
        }
        
        if ($_FILES['test_picture']['error'] == UPLOAD_ERR_OK) {
            echo "✅ File uploaded successfully<br>";
            echo "File name: " . $_FILES['test_picture']['name'] . "<br>";
            echo "File type: " . $_FILES['test_picture']['type'] . "<br>";
            echo "File size: " . $_FILES['test_picture']['size'] . " bytes<br>";
            
            $new_filename = $upload_dir . 'test_' . time() . '_' . basename($_FILES['test_picture']['name']);
            
            if (move_uploaded_file($_FILES['test_picture']['tmp_name'], $new_filename)) {
                echo "✅ File moved to: " . $new_filename . "<br>";
                
                // Update database
                $stmt = $conn->prepare("UPDATE users SET profile_picture=? WHERE id=?");
                if ($stmt) {
                    $stmt->bind_param("si", $new_filename, $user_id);
                    if ($stmt->execute()) {
                        echo "✅ Database updated successfully!<br>";
                        echo "Affected rows: " . $stmt->affected_rows . "<br>";
                    } else {
                        echo "❌ Database update failed: " . $stmt->error . "<br>";
                    }
                    $stmt->close();
                } else {
                    echo "❌ Prepare failed: " . $conn->error . "<br>";
                }
                
                echo "<br><img src='" . $new_filename . "' style='max-width: 200px;'><br>";
            } else {
                echo "❌ Failed to move uploaded file<br>";
            }
        } else {
            echo "❌ Upload error: " . $_FILES['test_picture']['error'] . "<br>";
        }
    }
    ?>
</body>
</html>
<?php
$conn->close();
?>
