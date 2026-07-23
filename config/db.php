<?php
// Make mysqli throw exceptions on errors instead of silently returning false.
// This guarantees database failures are propagated rather than swallowed by
// unchecked prepare()/execute()/query() calls throughout the app.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Any exception that is not handled closer to where it happened is logged
// server-side and reported to the user with a generic message, so internal
// details (SQL, credentials, stack traces) are never leaked and errors are
// never silently ignored.
set_exception_handler(function (Throwable $e) {
    error_log("Unhandled exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo "An unexpected error occurred. Please try again later.";
});

try {
    $conn = new mysqli("localhost", "root", "", "db_test");
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Database connection failed. Please try again later.");
}
?>
