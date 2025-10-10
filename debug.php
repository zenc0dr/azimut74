<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "PHP Version: " . phpversion() . "\n";
echo "Document Root: " . $_SERVER["DOCUMENT_ROOT"] . "\n";
echo "Script Filename: " . $_SERVER["SCRIPT_FILENAME"] . "\n";

// Test database connection
try {
    $pdo = new PDO("mysql:host=db;dbname=azimut", "azimut", "azimut");
    echo "Database connection: OK\n";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "\n";
}

// Test file permissions
$storage_path = "/var/www/html/storage";
if (is_writable($storage_path)) {
    echo "Storage directory is writable: OK\n";
} else {
    echo "Storage directory is writable: FAILED\n";
}

// Test Laravel bootstrap
try {
    require_once "/var/www/html/bootstrap/autoload.php";
    echo "Laravel autoload: OK\n";
    
    $app = require_once "/var/www/html/bootstrap/app.php";
    echo "Laravel app bootstrap: OK\n";
} catch (Exception $e) {
    echo "Laravel bootstrap: FAILED - " . $e->getMessage() . "\n";
}

echo "Debug completed.\n";
