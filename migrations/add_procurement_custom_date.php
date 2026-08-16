<?php
require_once __DIR__ . '/../config/db.php';

echo "=== Procurement Custom Date Migration ===\n\n";

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Step 1: Add category column
echo "Step 1: Adding category column...\n";
if ($is_postgres) {
    $sql = "ALTER TABLE procurement_posts ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'philgeps'";
} else {
    $sql = "ALTER TABLE procurement_posts ADD COLUMN category VARCHAR(50) DEFAULT 'philgeps' AFTER status";
}

if ($conn->query($sql)) {
    echo "✓ category column added\n";
} else {
    if ($conn instanceof PDO) {
        $check = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'procurement_posts' AND column_name = 'category'");
        if ($check && $check->rowCount() > 0) {
            echo "✓ category column already exists\n";
        } else {
            $error = $conn->errorInfo();
            echo "✗ Error adding column: " . ($error[2] ?? 'Unknown error') . "\n";
            exit(1);
        }
    } else {
        $check = $conn->query("SHOW COLUMNS FROM procurement_posts LIKE 'category'");
        if ($check->num_rows > 0) {
            echo "✓ category column already exists\n";
        } else {
            echo "✗ Error adding column: " . $conn->error . "\n";
            exit(1);
        }
    }
}

// Step 2: Add custom_date column
echo "\nStep 2: Adding custom_date column...\n";
if ($is_postgres) {
    $sql = "ALTER TABLE procurement_posts ADD COLUMN IF NOT EXISTS custom_date DATE NULL";
} else {
    $sql = "ALTER TABLE procurement_posts ADD COLUMN custom_date DATE NULL AFTER category";
}

if ($conn->query($sql)) {
    echo "✓ custom_date column added\n";
} else {
    if ($conn instanceof PDO) {
        $check = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'procurement_posts' AND column_name = 'custom_date'");
        if ($check && $check->rowCount() > 0) {
            echo "✓ custom_date column already exists\n";
        } else {
            $error = $conn->errorInfo();
            echo "✗ Error adding column: " . ($error[2] ?? 'Unknown error') . "\n";
            exit(1);
        }
    } else {
        $check = $conn->query("SHOW COLUMNS FROM procurement_posts LIKE 'custom_date'");
        if ($check->num_rows > 0) {
            echo "✓ custom_date column already exists\n";
        } else {
            echo "✗ Error adding column: " . $conn->error . "\n";
            exit(1);
        }
    }
}

// Step 3: Update existing records
echo "\nStep 3: Populating custom_date for existing records...\n";
$sql = "UPDATE procurement_posts SET custom_date = DATE(created_at) WHERE custom_date IS NULL";
$stmt = $conn->query($sql);
if ($stmt !== FALSE) {
    if ($conn instanceof PDO) {
        $affected = $stmt->rowCount();
    } else {
        $affected = $conn->affected_rows;
    }
    echo "✓ Updated $affected existing records\n";
} else {
    if ($conn instanceof PDO) {
        $error = $conn->errorInfo();
        echo "✗ Error updating records: " . ($error[2] ?? 'Unknown error') . "\n";
    } else {
        echo "✗ Error updating records: " . $conn->error . "\n";
    }
    exit(1);
}

// Step 4: Add index on (category, custom_date DESC)
echo "\nStep 4: Adding index on (category, custom_date DESC)...\n";
if ($is_postgres) {
    $sql = "CREATE INDEX IF NOT EXISTS idx_category_date ON procurement_posts(category, custom_date DESC)";
} else {
    $sql = "ALTER TABLE procurement_posts ADD INDEX idx_category_date (category, custom_date DESC)";
}

if ($conn->query($sql)) {
    echo "✓ Index added\n";
} else {
    if ($is_postgres) {
        echo "✗ Error adding index: " . $conn->error . "\n";
        echo "Note: Index creation failed but migration can continue without it.\n";
    } else {
        $check = $conn->query("SHOW INDEX FROM procurement_posts WHERE Key_name = 'idx_category_date'");
        if ($check->num_rows > 0) {
            echo "✓ Index already exists\n";
        } else {
            echo "✗ Error adding index: " . $conn->error . "\n";
            echo "Note: Index creation failed but migration can continue without it.\n";
        }
    }
}

echo "\n=== Migration Complete ===\n";
?>
