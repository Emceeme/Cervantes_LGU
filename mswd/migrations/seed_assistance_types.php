<?php
/**
 * MSWD Assistance Types Seed Script
 * Populates assistance_types table with predefined assistance programs
 * 
 * Usage: php mswd/migrations/seed_assistance_types.php
 */

require_once __DIR__ . '/../../config/db.php';

echo "Seeding assistance types...\n\n";

$assistance_types = [
    [
        'name' => 'Financial Assistance',
        'description' => 'Emergency financial support for individuals and families in crisis situations',
        'eligibility_requirements' => 'Must be a resident of the municipality, proof of income or financial hardship, valid ID',
        'process_steps' => '1. Submit application form 2. Interview with social worker 3. Assessment and approval 4. Release of assistance',
        'required_documents' => json_encode([
            'Valid ID (Government-issued)',
            'Barangay Certificate of Indigency',
            'Proof of Income or Certificate of Unemployment',
            'Medical Certificate (if applicable)',
            'Recent 2x2 ID picture'
        ])
    ],
    [
        'name' => 'Medical Assistance',
        'description' => 'Financial support for medical expenses including hospitalization, medicines, and treatments',
        'eligibility_requirements' => 'Must be a resident with medical emergency, hospital bill or medical certificate required',
        'process_steps' => '1. Submit medical documents 2. Social worker assessment 3. Approval based on need 4. Direct payment to hospital or reimbursement',
        'required_documents' => json_encode([
            'Valid ID',
            'Medical Certificate from attending physician',
            'Hospital Bill or Statement of Account',
            'Barangay Certificate of Indigency',
            'Prescription (for medicines)'
        ])
    ],
    [
        'name' => 'Educational Assistance',
        'description' => 'Support for school expenses including tuition, supplies, and uniforms for students',
        'eligibility_requirements' => 'Must be enrolled in school, family income below poverty threshold, good academic standing',
        'process_steps' => '1. Submit enrollment documents 2. Family assessment 3. Approval 4. Release of assistance',
        'required_documents' => json_encode([
            'Valid ID of parent/guardian',
            'Certificate of Enrollment',
            'School ID of student',
            'Report Card (for continuing students)',
            'Barangay Certificate of Indigency',
            'Birth Certificate of student'
        ])
    ],
    [
        'name' => 'Burial Assistance',
        'description' => 'Financial support for funeral expenses of deceased indigent residents',
        'eligibility_requirements' => 'Deceased must be a resident, family must demonstrate financial need',
        'process_steps' => '1. Submit death certificate 2. Family assessment 3. Approval 4. Release of assistance',
        'required_documents' => json_encode([
            'Death Certificate',
            'Valid ID of claimant',
            'Barangay Certificate of Indigency',
            'Proof of relationship to deceased',
            'Funeral contract or bill'
        ])
    ],
    [
        'name' => 'Livelihood Assistance',
        'description' => 'Start-up capital or equipment support for small businesses and livelihood projects',
        'eligibility_requirements' => 'Must have viable business plan, willing to undergo training, resident of municipality',
        'process_steps' => '1. Submit business proposal 2. Interview and assessment 3. Business training 4. Release of assistance',
        'required_documents' => json_encode([
            'Valid ID',
            'Business Plan or Proposal',
            'Barangay Certificate of Residency',
            'DTI Registration (if registered)',
            'Training Certificate (if available)'
        ])
    ],
    [
        'name' => 'Food Assistance',
        'description' => 'Emergency food packs and nutritional support for families in crisis',
        'eligibility_requirements' => 'Must be in immediate need, resident of municipality, priority given to vulnerable groups',
        'process_steps' => '1. Assessment by social worker 2. Approval based on urgency 3. Release of food assistance',
        'required_documents' => json_encode([
            'Valid ID',
            'Barangay Certificate of Indigency',
            'Family Profile Sheet'
        ])
    ],
    [
        'name' => 'Emergency Shelter Assistance',
        'description' => 'Temporary shelter support for families displaced by disasters or emergencies',
        'eligibility_requirements' => 'Must be displaced due to calamity, resident of municipality, no other shelter options',
        'process_steps' => '1. Disaster assessment 2. Shelter assignment 3. Monitoring and support 4. Transition to permanent housing',
        'required_documents' => json_encode([
            'Valid ID',
            'Barangay Certificate of Displacement',
            'Disaster Report from barangay',
            'Family Profile Sheet'
        ])
    ],
    [
        'name' => 'Senior Citizen Support',
        'description' => 'Additional support services and assistance for elderly residents',
        'eligibility_requirements' => 'Must be 60 years or older, resident of municipality, registered senior citizen',
        'process_steps' => '1. Submit senior citizen ID 2. Assessment of needs 3. Provision of appropriate assistance',
        'required_documents' => json_encode([
            'Senior Citizen ID',
            'Valid Government ID',
            'Barangay Certificate of Residency',
            'Medical Certificate (if health-related assistance)'
        ])
    ]
];

try {
    $stmt = $conn->prepare("
        INSERT INTO assistance_types 
        (name, description, eligibility_requirements, process_steps, required_documents, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $count = 0;
    foreach ($assistance_types as $type) {
        $stmt->bind_param(
            "sssss",
            $type['name'],
            $type['description'],
            $type['eligibility_requirements'],
            $type['process_steps'],
            $type['required_documents']
        );
        
        if ($stmt->execute()) {
            echo "✓ Added: {$type['name']}\n";
            $count++;
        } else {
            echo "✗ Failed to add {$type['name']}: " . $stmt->error . "\n";
        }
    }
    
    $stmt->close();
    
    echo "\n✓ Seeded $count assistance types successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
