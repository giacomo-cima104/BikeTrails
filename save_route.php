<?php
date_default_timezone_set('Europe/Rome');
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dati mancanti']);
    exit;
}

$dist   = floatval($data['distanza'] ?? 0);
$durata = htmlspecialchars(trim($data['durata'] ?? ''), ENT_QUOTES, 'UTF-8');
$coords_raw = $data['coordinate'] ?? '[]';

// Validate coordinate JSON
$coords_test = json_decode(is_string($coords_raw) ? $coords_raw : json_encode($coords_raw));
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Coordinate non valide']);
    exit;
}

$coords_str = is_string($coords_raw) ? $coords_raw : json_encode($coords_raw);

$stmt = $conn->prepare("INSERT INTO percorsi (distanza, durata, coordinate) VALUES (?, ?, ?)");
$stmt->bind_param("dss", $dist, $durata, $coords_str);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
