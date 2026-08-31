<?php
// HTTP Super Admin Creator for PostgreSQL
// Access this file via: https://your-app.onrender.com/run_super_admin.php
// SECURITY: Delete this file after creating super admin in production

require_once 'config/db.php';

header('Content-Type: text/plain');

echo "=== LGU System Super Admin Creator ===\n\n";

// Check if this is a POST request (for security)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "ERROR: This endpoint requires POST request for security.\n";
    echo "Use: curl -X POST -d 'auth_key=run_migrations_secure_key' https://your-app.onrender.com/run_super_admin.php\n";
    exit(1);
}

// Simple security check
$auth_key = $_POST['auth_key'] ?? '';
if ($auth_key !== 'run_migrations_secure_key') {
    echo "ERROR: Invalid authentication key.\n";
    exit(1);
}

// Get credentials from POST or use defaults
$username = $_POST['username'] ?? 'admin';
$password = $_POST['password'] ?? 'admin123';
$email = $_POST['email'] ?? 'admin@lgu.gov.ph';
$first_name = $_POST['first_name'] ?? 'Super';
$last_name = $_POST['last_name'] ?? 'Admin';

echo "Creating super admin account...\n";
echo "Username: $username\n";
echo "Email: $email\n\n";

// Check if user already exists
if ($conn instanceof PDO) {
    // PostgreSQL
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "✗ User already exists with username or email.\n";
        exit(1);
    }
    
    // Insert super admin
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, role, department) VALUES (?, ?, ?, ?, ?, 'SUPER_ADMIN', 'LGU')");
    $result = $stmt->execute([$username, $password_hash, $email, $first_name, $last_name]);
    
    if ($result) {
        echo "✓ Super admin created successfully!\n";
        echo "Username: $username\n";
        echo "Password: $password\n";
        echo "Email: $email\n";
        echo "\nIMPORTANT: Change this password immediately after first login!\n";
        echo "\nLogin at: https://your-app.onrender.com/login.php\n";
    } else {
        echo "✗ Failed to create super admin: " . $stmt->errorInfo()[2] . "\n";
        exit(1);
    }
} else {
    // MySQLi
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "✗ User already exists with username or email.\n";
        exit(1);
    }
    
    // Insert super admin
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, role, department) VALUES (?, ?, ?, ?, ?, 'SUPER_ADMIN', 'LGU')");
    $stmt->bind_param("ssssss", $username, $password_hash, $email, $first_name, $last_name);
    $result = $stmt->execute();
    
    if ($result) {
        echo "✓ Super admin created successfully!\n";
        echo "Username: $username\n";
        echo "Password: $password\n";
        echo "Email: $email\n";
        echo "\nIMPORTANT: Change this password immediately after first login!\n";
        echo "\nLogin at: https://your-app.onrender.com/login.php\n";
    } else {
        echo "✗ Failed to create super admin: " . $stmt->error . "\n";
        exit(1);
    }
}
?>
