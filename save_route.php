<?php
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $dist   = floatval($data['distanza']);
    $durata = htmlspecialchars($data['durata'] ?? '');
    $coords = $data['coordinate'];

    $stmt = $conn->prepare("INSERT INTO percorsi (distanza, durata, coordinate) VALUES (?, ?, ?)");
    $stmt->bind_param("dss", $dist, $durata, $coords);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
?>