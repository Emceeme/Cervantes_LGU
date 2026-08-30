<?php
// Setup upload directories for Render
// This script creates all necessary upload directories

$base_upload_dir = __DIR__ . '/lgu/uploads';

$directories = [
    $base_upload_dir,
    $base_upload_dir . '/news',
    $base_upload_dir . '/scholarship',
    $base_upload_dir . '/resumes',
    $base_upload_dir . '/procurement'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created: $dir\n";
    } else {
        echo "Exists: $dir\n";
    }
}

// Create .htaccess to allow access to uploads
$htaccess_content = "Options -Indexes\n";
file_put_contents($base_upload_dir . '/.htaccess', $htaccess_content);

echo "\nUpload directories setup complete.\n";
echo "Base upload directory: $base_upload_dir\n";
?>
