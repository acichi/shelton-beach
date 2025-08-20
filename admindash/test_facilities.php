<?php
// Simple test file to check database connection and facility data
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

echo "<h2>Database Connection Test</h2>";

try {
    echo "<p>✅ Database connection successful</p>";
    
    // Test query - using correct table name 'facility'
    $query = "SELECT COUNT(*) as total FROM facility";
    $result = $conn->query($query);
    $count = $result->fetch_assoc();
    
    echo "<p>✅ Total facilities in database: " . $count['total'] . "</p>";
    
    // Show sample data
    $query = "SELECT * FROM facility LIMIT 3";
    $result = $conn->query($query);
    $facilities = [];
    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }
    
    echo "<h3>Sample Facilities:</h3>";
    echo "<pre>";
    print_r($facilities);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
