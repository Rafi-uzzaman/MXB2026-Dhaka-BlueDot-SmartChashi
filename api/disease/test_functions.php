<?php
// Standalone test for disease detection functions
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Disease Detection Functions ===\n\n";

// Test 1: Check if config loads
echo "1. Loading config...\n";
try {
    require_once __DIR__ . '/../../config/config.php';
    echo "   ✓ Config loaded\n\n";
} catch (Exception $e) {
    echo "   ✗ Config error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Create test image
echo "2. Creating test image...\n";
$testImage = tempnam(sys_get_temp_dir(), 'test') . '.jpg';
$img = imagecreatetruecolor(200, 200);
$green = imagecolorallocate($img, 0, 255, 0);
imagefilledrectangle($img, 0, 0, 200, 200, $green);
imagejpeg($img, $testImage, 90);
echo "   ✓ Test image created: $testImage\n";
echo "   File exists: " . (file_exists($testImage) ? 'Yes' : 'No') . "\n";
echo "   File size: " . filesize($testImage) . " bytes\n\n";

// Test 3: Define mock function inline
echo "3. Testing mock detection function...\n";

function testDetectDiseaseAdvancedMock($imagePath, $cropId) {
    // Simple test version
    $diseaseDatabase = [
        'general' => [
            [
                'name' => 'Test Disease',
                'severity' => 'medium',
                'confidence' => 0.85,
                'causes' => 'Test causes',
                'treatment' => 'Test treatment instructions'
            ]
        ]
    ];
    
    $diseases = $diseaseDatabase['general'];
    $disease = $diseases[0];
    
    return [
        'disease' => $disease['name'],
        'severity' => $disease['severity'],
        'confidence' => $disease['confidence'],
        'treatment' => $disease['treatment'],
        'causes' => $disease['causes'],
        'api_used' => 'Test Mock Detection'
    ];
}

try {
    $result = testDetectDiseaseAdvancedMock($testImage, null);
    echo "   ✓ Mock detection executed\n";
    echo "   Result:\n";
    foreach ($result as $key => $value) {
        echo "     - $key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
    }
    echo "\n";
    
    // Validate result
    $requiredFields = ['disease', 'severity', 'confidence', 'treatment'];
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($result[$field])) {
            $missing[] = $field;
        }
    }
    
    if (empty($missing)) {
        echo "   ✓ All required fields present\n\n";
    } else {
        echo "   ✗ Missing fields: " . implode(', ', $missing) . "\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n\n";
}

// Test 4: Check if Plant.id API key is configured
echo "4. Checking Plant.id API configuration...\n";
if (defined('PLANTID_API_KEY')) {
    $keyLength = strlen(PLANTID_API_KEY);
    echo "   ✓ API key defined (length: $keyLength)\n";
    echo "   Key starts with: " . substr(PLANTID_API_KEY, 0, 10) . "...\n\n";
} else {
    echo "   ✗ PLANTID_API_KEY not defined\n\n";
}

// Cleanup
unlink($testImage);
echo "=== Test Complete ===\n";
?>
