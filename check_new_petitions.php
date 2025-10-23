<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Récupérer le timestamp du dernier check (envoyé par AJAX)
$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-1 hour'));

// Valider le format de la date
if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $last_check)) {
    echo json_encode([
        'success' => false,
        'error' => 'Format de date invalide'
    ]);
    exit();
}

$conn = getConnection();

// Compter les nouvelles pétitions depuis le dernier check
$sql = "SELECT COUNT(*) as new_count 
        FROM Petition 
        WHERE DateAjoutP > ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur préparation requête'
    ]);
    exit();
}

$stmt->bind_param("s", $last_check);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

// Récupérer la dernière pétition ajoutée
$sql2 = "SELECT IDP, TitreP, NomPorteurP, DateAjoutP 
         FROM Petition 
         ORDER BY DateAjoutP DESC 
         LIMIT 1";

$result2 = $conn->query($sql2);
$latest_petition = null;

if ($result2 && $result2->num_rows > 0) {
    $latest_petition = $result2->fetch_assoc();
    $result2->free();
}

echo json_encode([
    'success' => true,
    'new_count' => (int)$data['new_count'],
    'latest_petition' => $latest_petition,
    'current_time' => date('Y-m-d H:i:s')
]);
?>