<?php
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $dist = $data['distanza'];
    $coords = $data['coordinate'];

    $stmt = $conn->prepare("INSERT INTO percorsi (distanza, coordinate) VALUES (?, ?)");
    $stmt->bind_param("ds", $dist, $coords);
    
    if ($stmt->execute()) {
        echo "Successo";
    } else {
        echo "Errore";
    }
}
?>