<?php
// Seed Assistance Types for MSWD
// Access this file with ?secret=YOUR_SECRET_KEY to seed data

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

// Secret key for security
$SECRET_KEY = 'seed_assistance_2024';

// Check for secret key
if (!isset($_GET['secret']) || $_GET['secret'] !== $SECRET_KEY) {
    http_response_code(403);
    die("Access denied. Invalid secret key.");
}

echo "<h1>Seed MSWD Assistance Types</h1>";
echo "<pre>";

$assistance_types = [
    [
        'name' => 'Financial Assistance',
        'description' => 'Financial support for individuals and families in need',
        'eligibility_requirements' => 'Must be a resident of Cervantes, Ilocos Sur with proof of indigency',
        'process_steps' => '1. Submit application form\n2. Attend interview\n3. Wait for approval',
        'required_documents' => json_encode(['Barangay Certificate', 'ID', 'Income Statement'])
    ],
    [
        'name' => 'Medical Assistance',
        'description' => 'Medical and health-related financial support',
        'eligibility_requirements' => 'Must have medical emergency or chronic illness requiring treatment',
        'process_steps' => '1. Submit medical records\n2. Social worker assessment\n3. Approval and release',
        'required_documents' => json_encode(['Medical Certificate', 'Hospital Bill', 'ID', 'Barangay Certificate'])
    ],
    [
        'name' => 'Educational Assistance',
        'description' => 'Financial support for education-related expenses',
        'eligibility_requirements' => 'Must be enrolled in school with good academic standing',
        'process_steps' => '1. Submit enrollment proof\n2. Assessment\n3. Release of assistance',
        'required_documents' => json_encode(['Certificate of Enrollment', 'School ID', 'Report Card', 'ID'])
    ],
    [
        'name' => 'Burial Assistance',
        'description' => 'Financial support for funeral expenses',
        'eligibility_requirements' => 'Family member of deceased must be a resident',
        'process_steps' => '1. Submit death certificate\n2. Assessment\n3. Release of assistance',
        'required_documents' => json_encode(['Death Certificate', 'ID of claimant', 'Burial Receipt', 'Barangay Certificate'])
    ],
    [
        'name' => 'Livelihood Assistance',
        'description' => 'Support for starting or improving small businesses',
        'eligibility_requirements' => 'Must have viable business plan and capability to manage',
        'process_steps' => '1. Submit business proposal\n2. Training\n3. Release of assistance',
        'required_documents' => json_encode(['Business Plan', 'DTI Registration', 'ID', 'Barangay Certificate'])
    ],
    [
        'name' => 'Food Assistance',
        'description' => 'Emergency food support for families in crisis',
        'eligibility_requirements' => 'Must be in immediate need due to emergency or crisis',
        'process_steps' => '1. Assessment\n2. Immediate release',
        'required_documents' => json_encode(['Barangay Certificate', 'ID'])
    ]
];

$success_count = 0;
$error_count = 0;

foreach ($assistance_types as $type) {
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare("
            INSERT INTO assistance_types (name, description, eligibility_requirements, process_steps, required_documents, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        try {
            $stmt->execute([
                $type['name'],
                $type['description'],
                $type['eligibility_requirements'],
                $type['process_steps'],
                $type['required_documents']
            ]);
            echo "✓ Inserted: " . htmlspecialchars($type['name']) . "\n";
            $success_count++;
        } catch (PDOException $e) {
            echo "✗ Failed: " . htmlspecialchars($type['name']) . " - " . $e->getMessage() . "\n";
            $error_count++;
        }
    } else {
        $stmt = $conn->prepare("
            INSERT INTO assistance_types (name, description, eligibility_requirements, process_steps, required_documents, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->bind_param("sssss",
            $type['name'],
            $type['description'],
            $type['eligibility_requirements'],
            $type['process_steps'],
            $type['required_documents']
        );
        
        if ($stmt->execute()) {
            echo "✓ Inserted: " . htmlspecialchars($type['name']) . "\n";
            $success_count++;
        } else {
            echo "✗ Failed: " . htmlspecialchars($type['name']) . " - " . $stmt->error . "\n";
            $error_count++;
        }
        $stmt->close();
    }
}

echo "</pre>";
echo "<h2>Seeding Summary</h2>";
echo "<p>Successful: $success_count</p>";
echo "<p>Failed: $error_count</p>";

if ($error_count === 0) {
    echo "<p style='color: green; font-weight: bold;'>All assistance types seeded successfully!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>Some assistance types failed to seed. Please check the errors above.</p>";
}
?>
