<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$conn = getConnection();

// Récupérer la pétition avec le plus de signatures
$sql = "SELECT p.IDP, p.TitreP, COUNT(s.IDS) as nb_signatures 
        FROM Petition p 
        LEFT JOIN Signature s ON p.IDP = s.IDP 
        GROUP BY p.IDP 
        HAVING nb_signatures > 0
        ORDER BY nb_signatures DESC 
        LIMIT 1";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur requête'
    ]);
    exit();
}

if($result->num_rows > 0) {
    $top_petition = $result->fetch_assoc();
    $result->free();
    
    echo json_encode([
        'success' => true,
        'petition' => $top_petition
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Aucune pétition signée'
    ]);
}
?>