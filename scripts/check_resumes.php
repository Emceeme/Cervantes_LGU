<?php
require_once 'config/db.php';
require_once 'config/upload_helper.php';

echo "=== Checking resume files ===\n\n";

// Check applicants table for resume files
$stmt = $conn->prepare("SELECT id, first_name, last_name, resume FROM applicants");
$stmt->execute();

if ($conn instanceof PDO) {
    $applicants = $stmt->fetchAll();
    echo "Total applicants: " . count($applicants) . "\n\n";
    
    foreach ($applicants as $row) {
        echo "ID: " . $row['id'] . "\n";
        echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
        echo "Resume file: " . ($row['resume'] ?? 'NULL') . "\n";
        
        if (!empty($row['resume'])) {
            $exists = UploadHelper::fileExists($row['resume'], 'resumes');
            echo "File exists: " . ($exists ? 'YES' : 'NO') . "\n";
            echo "Upload dir: " . UploadHelper::getUploadDir('resumes') . "\n";
        }
        echo "---\n";
    }
} else {
    $result = $stmt->get_result();
    echo "Total applicants: " . $result->num_rows . "\n\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
        echo "Resume file: " . ($row['resume'] ?? 'NULL') . "\n";
        
        if (!empty($row['resume'])) {
            $exists = UploadHelper::fileExists($row['resume'], 'resumes');
            echo "File exists: " . ($exists ? 'YES' : 'NO') . "\n";
            echo "Upload dir: " . UploadHelper::getUploadDir('resumes') . "\n";
        }
        echo "---\n";
    }
}

if (!($conn instanceof PDO)) {
    $stmt->close();
}

echo "\nUpload directory: " . UploadHelper::getUploadDir('resumes') . "\n";
echo "Directory exists: " . (is_dir(UploadHelper::getUploadDir('resumes')) ? 'YES' : 'NO') . "\n";
?>
