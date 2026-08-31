<?php
// Setup upload directories for Render
// This script creates all necessary upload directories

require_once __DIR__ . '/config/upload_helper.php';

echo "Creating upload directories...\n";

$base_upload_dir = __DIR__ . '/lgu/uploads';

$directories = [
    'news',
    'scholarship',
    'resumes',
    'procurement'
];

foreach ($directories as $dir) {
    $path = UploadHelper::getUploadDir($dir);
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    echo "Created: $path\n";
}

// Create .htaccess to allow access to uploads
$htaccess_content = "Options -Indexes\n";
file_put_contents($base_upload_dir . '/.htaccess', $htaccess_content);

echo "\n✅ All upload directories created successfully.\n";
echo "\nUpload directories setup complete.\n";
echo "Base upload directory: $base_upload_dir\n";
?>
