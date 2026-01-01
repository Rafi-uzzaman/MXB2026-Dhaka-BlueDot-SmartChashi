<?php
$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$cropName = $_POST['cropName'] ?? '';
$variety = $_POST['variety'] ?? '';
$area = $_POST['area'] ?? 0;
$plantedDate = $_POST['plantedDate'] ?? date('Y-m-d');
$expectedHarvest = $_POST['expectedHarvest'] ?? null;

$db = new Database();
$query = "INSERT INTO crop_data (farmer_id, crop_name, variety, area_hectares, planted_date, expected_harvest, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
$db->query($query);
$db->bind(1, $_SESSION['user_id']);
$db->bind(2, $cropName);
$db->bind(3, $variety);
$db->bind(4, $area);
$db->bind(5, $plantedDate);
$db->bind(6, $expectedHarvest);
$db->bind(7, 'planning');

if ($db->execute()) {
    echo json_encode(['success' => true, 'message' => 'Crop added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add crop']);
}
?>
