<?php
session_start();
require_once 'config.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['IDUtilisateur'])) {
    header('Location: login.php?error=unauthorized');
    exit();
}

// Vérifier l'ID de la pétition
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=invalid_id');
    exit();
}

$petition_id = (int)$_GET['id'];
$id_utilisateur = $_SESSION['IDUtilisateur'];
$conn = getConnection();

// Récupérer la pétition et vérifier que l'utilisateur en est le créateur
$sql = "SELECT IDP, ImageP FROM Petition WHERE IDP = ? AND IDUtilisateur = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erreur préparation : " . $conn->error);
}

$stmt->bind_param("ii", $petition_id, $id_utilisateur);
$stmt->execute();
$result = $stmt->get_result();
$petition = $result->fetch_assoc();

if (!$petition) {
    $stmt->close();
    header('Location: index.php?error=unauthorized');
    exit();
}

$stmt->close();

// Supprimer l'image si elle existe
if (!empty($petition['ImageP']) && file_exists('uploads/' . $petition['ImageP'])) {
    unlink('uploads/' . $petition['ImageP']);
}

// Supprimer la pétition (les signatures seront supprimées automatiquement grâce à ON DELETE CASCADE)
$delete_sql = "DELETE FROM Petition WHERE IDP = ? AND IDUtilisateur = ?";
$delete_stmt = $conn->prepare($delete_sql);

if (!$delete_stmt) {
    die("Erreur préparation suppression : " . $conn->error);
}

$delete_stmt->bind_param("ii", $petition_id, $id_utilisateur);

if ($delete_stmt->execute()) {
    $delete_stmt->close();
    header('Location: index.php?success=suppression');
    exit();
} else {
    $delete_stmt->close();
    header('Location: index.php?error=delete_failed');
    exit();
}
?>