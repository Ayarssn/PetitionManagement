<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier l'ID de la pétition
if (!isset($_GET['petition_id']) || !is_numeric($_GET['petition_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'ID de pétition invalide'
    ]);
    exit();
}

$petition_id = (int)$_GET['petition_id'];
$conn = getConnection();

// Récupérer les 5 dernières signatures
$sql = "SELECT NomS, PrenomS, PaysS, DateS 
        FROM Signature 
        WHERE IDP = ? 
        ORDER BY DateS DESC 
        LIMIT 5";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur préparation requête'
    ]);
    exit();
}

$stmt->bind_param("i", $petition_id);
$stmt->execute();
$result = $stmt->get_result();

$signatures = [];
while ($row = $result->fetch_assoc()) {
    $signatures[] = [
        'nom' => $row['NomS'],
        'prenom' => $row['PrenomS'],
        'pays' => $row['PaysS'],
        'date' => $row['DateS']
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'signatures' => $signatures,
    'count' => count($signatures)
]);