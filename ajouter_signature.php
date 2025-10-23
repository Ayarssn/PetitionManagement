<?php
require_once 'config.php';

// Vérifier la méthode POST
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Récupérer et nettoyer les données
$petition_id = isset($_POST['petition_id']) ? (int)$_POST['petition_id'] : 0;
$nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
$prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$pays = isset($_POST['pays']) ? trim($_POST['pays']) : '';

// Vérifier si c'est une requête AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Validation côté serveur
$errors = [];

if($petition_id <= 0) {
    $errors[] = "Pétition invalide";
}

if(empty($nom) || strlen($nom) < 2 || strlen($nom) > 100) {
    $errors[] = "Le nom doit contenir entre 2 et 100 caractères";
}

if(empty($prenom) || strlen($prenom) < 2 || strlen($prenom) > 100) {
    $errors[] = "Le prénom doit contenir entre 2 et 100 caractères";
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $errors[] = "Email invalide";
}

if(empty($pays) || strlen($pays) < 2 || strlen($pays) > 100) {
    $errors[] = "Le pays doit contenir entre 2 et 100 caractères";
}

// Si erreurs de validation
if(!empty($errors)) {
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit();
    }
    header("Location: signature.php?id=$petition_id&error=validation");
    exit();
}

$conn = getConnection();

// Vérifier si la pétition existe
$check_sql = "SELECT IDP FROM Petition WHERE IDP = ?";
$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    error_log("Erreur préparation requête check : " . $conn->error);
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
        exit();
    }
    header("Location: signature.php?id=$petition_id&error=db");
    exit();
}

$check_stmt->bind_param("i", $petition_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if($check_result->num_rows === 0) {
    $check_stmt->close();
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Pétition non trouvée']);
        exit();
    }
    header('Location: index.php?error=petition_not_found');
    exit();
}
$check_stmt->close();

// Vérifier si l'email n'a pas déjà signé cette pétition
$duplicate_sql = "SELECT IDS FROM Signature WHERE IDP = ? AND EmailS = ?";
$duplicate_stmt = $conn->prepare($duplicate_sql);

if (!$duplicate_stmt) {
    error_log("Erreur préparation requête duplicate : " . $conn->error);
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
        exit();
    }
    header("Location: signature.php?id=$petition_id&error=db");
    exit();
}

$duplicate_stmt->bind_param("is", $petition_id, $email);
$duplicate_stmt->execute();
$duplicate_result = $duplicate_stmt->get_result();

if($duplicate_result->num_rows > 0) {
    $duplicate_stmt->close();
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'duplicate', 'message' => 'Vous avez déjà signé cette pétition']);
        exit();
    }
    header("Location: signature.php?id=$petition_id&error=duplicate");
    exit();
}
$duplicate_stmt->close();

// Insérer la signature
$sql = "INSERT INTO Signature (IDP, NomS, PrenomS, EmailS, PaysS, DateS, HeureS) 
        VALUES (?, ?, ?, ?, ?, NOW(), CURTIME())";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Erreur préparation requête insert : " . $conn->error);
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
        exit();
    }
    header("Location: signature.php?id=$petition_id&error=db");
    exit();
}

$stmt->bind_param("issss", $petition_id, $nom, $prenom, $email, $pays);

if($stmt->execute()) {
    $signature_id = $stmt->insert_id;
    $stmt->close();
    
    // Récupérer le nombre total de signatures
    $count_sql = "SELECT COUNT(*) as total FROM Signature WHERE IDP = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $petition_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    $count_stmt->close();
    
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Signature ajoutée avec succès',
            'signature_id' => $signature_id,
            'total_signatures' => $count_data['total']
        ]);
        exit();
    }
    
    header('Location: index.php?success=1');
    exit();
} else {
    error_log("Erreur insertion signature : " . $stmt->error);
    $stmt->close();
    
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'insertion']);
        exit();
    }
    
    header("Location: signature.php?id=$petition_id&error=db");
    exit();
}
?>