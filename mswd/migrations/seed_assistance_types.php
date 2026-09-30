<?php
/**
 * Seed assistance types for MSWD
 * 
 * Usage: php mswd/migrations/seed_assistance_types.php
 */

require_once __DIR__ . '/../../config/db.php';

echo "Seeding assistance types...\n\n";

$assistance_types = [
    [
        'name' => 'Financial Assistance',
        'description' => 'Financial support for individuals and families in need',
        'eligibility_requirements' => 'Must be a resident of the municipality for at least 6 months. Income must be below the poverty threshold. Must provide proof of financial hardship.',
        'process_steps' => '1. Submit application form 2. Attend interview 3. Submit required documents 4. Wait for approval 5. Receive assistance',
        'required_documents' => json_encode([
            'Valid ID',
            'Barangay Certificate of Indigency',
            'Proof of Income (if applicable)',
            'Medical Certificate (if for medical assistance)',
            'School Enrollment (if for educational assistance)'
        ])
    ],
    [
        'name' => 'Medical Assistance',
        'description' => 'Medical and healthcare support for indigent patients',
        'eligibility_requirements' => 'Must be a resident of the municipality. Must have no PhilHealth coverage or insufficient coverage. Must provide medical certificate from government hospital.',
        'process_steps' => '1. Submit application with medical certificate 2. Social worker assessment 3. Approval 4. Direct payment to hospital',
        'required_documents' => json_encode([
            'Valid ID',
            'Medical Certificate from Government Hospital',
            'Hospital Bill/Quotation',
            'Barangay Certificate of Indigency',
            'PhilHealth ID/MDR (if applicable)'
        ])
    ],
    [
        'name' => 'Educational Assistance',
        'description' => 'Educational support for students from poor families',
        'eligibility_requirements' => 'Must be enrolled in public school. Family income must be below poverty threshold. Must maintain good academic standing.',
        'process_steps' => '1. Submit enrollment documents 2. Social worker assessment 3. Approval 4. Release of assistance per semester',
        'required_documents' => json_encode([
            'Valid ID',
            'School Enrollment Form',
            'Certificate of Grades',
            'Barangay Certificate of Indigency',
            'Parent/Guardian Valid ID'
        ])
    ],
    [
        'name' => 'Burial Assistance',
        'description' => 'Financial support for burial expenses of indigent families',
        'eligibility_requirements' => 'Deceased must be a resident of the municipality. Family must be indigent. Death must be recent (within 30 days).',
        'process_steps' => '1. Submit death certificate 2. Social worker verification 3. Approval 4. Release of assistance',
        'required_documents' => json_encode([
            'Valid ID of claimant',
            'Death Certificate',
            'Barangay Certificate of Indigency',
            'Proof of relationship to deceased',
            'Funeral home bill/quotation'
        ])
    ],
    [
        'name' => 'Livelihood Assistance',
        'description' => 'Support for starting small businesses or livelihood projects',
        'eligibility_requirements' => 'Must be a resident of the municipality. Must submit viable business plan. Must attend livelihood training.',
        'process_steps' => '1. Submit business proposal 2. Attend training 3. Assessment 4. Approval 5. Release of starter kit/fund',
        'required_documents' => json_encode([
            'Valid ID',
            'Business Proposal',
            'Barangay Certificate of Residency',
            'Training Certificate (if applicable)',
            'DTI Registration (if applicable)'
        ])
    ],
    [
        'name' => 'Food Assistance',
        'description' => 'Food packs and nutritional support for families in crisis',
        'eligibility_requirements' => 'Must be in crisis situation (calamity, emergency, extreme poverty). Must be recommended by barangay official.',
        'process_steps' => '1. Barangay recommendation 2. Social worker assessment 3. Approval 4. Release of food packs',
        'required_documents' => json_encode([
            'Valid ID',
            'Barangay Certificate/Recommendation',
            'Crisis documentation (if applicable)'
        ])
    ]
];

try {
    foreach ($assistance_types as $type) {
        $stmt = $conn->prepare("
            INSERT INTO assistance_types (name, description, eligibility_requirements, process_steps, required_documents, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        if ($conn instanceof PDO) {
            $stmt->execute([
                $type['name'],
                $type['description'],
                $type['eligibility_requirements'],
                $type['process_steps'],
                $type['required_documents']
            ]);
        } else {
            $stmt->bind_param("sssss", 
                $type['name'],
                $type['description'],
                $type['eligibility_requirements'],
                $type['process_steps'],
                $type['required_documents']
            );
            $stmt->execute();
            $stmt->close();
        }
        
        echo "✓ Added: " . $type['name'] . "\n";
    }
    
    echo "\n✓ Assistance types seeded successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
