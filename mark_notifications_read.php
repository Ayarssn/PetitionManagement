<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier la méthode POST
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les IDs des notifications à marquer comme lues
$notification_ids = isset($_POST['ids']) ? $_POST['ids'] : '';

if (empty($notification_ids)) {
    echo json_encode(['success' => false, 'error' => 'Aucun ID fourni']);
    exit();
}

$conn = getConnection();

// Marquer les notifications comme lues
$sql = "UPDATE Notification SET LueN = TRUE WHERE IDN IN (" . $notification_ids . ")";

if ($conn->query($sql)) {
    echo json_encode([
        'success' => true,
        'message' => 'Notifications marquées comme lues'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour'
    ]);
}
?>