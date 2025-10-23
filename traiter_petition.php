<?php
require_once 'config.php';

// Vérifier la méthode POST
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: petition.php');
    exit();
}

// Récupérer et nettoyer les données
$titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$nom_porteur = isset($_POST['nom_porteur']) ? trim($_POST['nom_porteur']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$date_fin = isset($_POST['date_fin']) && !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;

// Vérifier reCAPTCHA
if (isset($_POST['g-recaptcha-response'])) {
    $recaptcha_secret = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Clé secrète de test
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}";
    $verify = @file_get_contents($verify_url);
    
    if ($verify !== false) {
        $captcha_success = json_decode($verify);
        if (!$captcha_success->success) {
            header('Location: petition.php?error=captcha');
            exit();
        }
    }
}

// Validation côté serveur
$errors = [];

if(empty($titre) || strlen($titre) < 5 || strlen($titre) > 255) {
    $errors[] = "Le titre doit contenir entre 5 et 255 caractères";
}

if(empty($description) || strlen($description) < 20 || strlen($description) > 5000) {
    $errors[] = "La description doit contenir entre 20 et 5000 caractères";
}

if(empty($nom_porteur) || strlen($nom_porteur) < 2 || strlen($nom_porteur) > 100) {
    $errors[] = "Le nom du porteur doit contenir entre 2 et 100 caractères";
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $errors[] = "Email invalide";
}

if($date_fin !== null) {
    $date_obj = DateTime::createFromFormat('Y-m-d', $date_fin);
    if(!$date_obj || $date_obj < new DateTime()) {
        $errors[] = "La date limite doit être dans le futur";
    }
}

// Si erreurs
if(!empty($errors)) {
    header("Location: petition.php?error=validation");
    exit();
}

$conn = getConnection();

// Vérifier si une pétition similaire existe déjà (même titre)
$check_sql = "SELECT IDP FROM Petition WHERE TitreP = ?";
$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    error_log("Erreur préparation requête check : " . $conn->error);
    header("Location: petition.php?error=db");
    exit();
}

$check_stmt->bind_param("s", $titre);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if($check_result->num_rows > 0) {
    $check_stmt->close();
    header("Location: petition.php?error=duplicate");
    exit();
}
$check_stmt->close();

// Gestion de l'upload d'image
$image_path = null;
if (isset($_FILES['image_petition']) && $_FILES['image_petition']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5 MB
    
    $file_type = $_FILES['image_petition']['type'];
    $file_size = $_FILES['image_petition']['size'];
    $file_name = $_FILES['image_petition']['name'];
    
    // Vérifier le type et la taille
    if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
        // Créer le dossier uploads s'il n'existe pas
        if (!file_exists('uploads')) {
            mkdir('uploads', 0755, true);
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $filename = uniqid('petition_') . '_' . time() . '.' . $extension;
        $upload_path = 'uploads/' . $filename;
        
        // Déplacer le fichier uploadé
        if (move_uploaded_file($_FILES['image_petition']['tmp_name'], $upload_path)) {
            $image_path = $filename;
        } else {
            error_log("Erreur lors du déplacement du fichier uploadé");
        }
    } else {
        error_log("Type de fichier non autorisé ou taille trop grande");
    }
}

// Insérer la nouvelle pétition (avec ou sans image)
if($date_fin !== null && $image_path !== null) {
    $sql = "INSERT INTO Petition (TitreP, DescriptionP, NomPorteurP, Email, DateFinP, ImageP, DateAjoutP) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Erreur préparation requête insert : " . $conn->error);
        header("Location: petition.php?error=db");
        exit();
    }
    
    $stmt->bind_param("ssssss", $titre, $description, $nom_porteur, $email, $date_fin, $image_path);
} else if($date_fin !== null) {
    $sql = "INSERT INTO Petition (TitreP, DescriptionP, NomPorteurP, Email, DateFinP, DateAjoutP) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Erreur préparation requête insert : " . $conn->error);
        header("Location: petition.php?error=db");
        exit();
    }
    
    $stmt->bind_param("sssss", $titre, $description, $nom_porteur, $email, $date_fin);
} else if($image_path !== null) {
    $sql = "INSERT INTO Petition (TitreP, DescriptionP, NomPorteurP, Email, ImageP, DateAjoutP) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Erreur préparation requête insert : " . $conn->error);
        header("Location: petition.php?error=db");
        exit();
    }
    
    $stmt->bind_param("sssss", $titre, $description, $nom_porteur, $email, $image_path);
} else {
    $sql = "INSERT INTO Petition (TitreP, DescriptionP, NomPorteurP, Email, DateAjoutP) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Erreur préparation requête insert : " . $conn->error);
        header("Location:petition.php?error=db");
        exit();
    }
    
    $stmt->bind_param("ssss", $titre, $description, $nom_porteur, $email);
}

if($stmt->execute()) {
    $new_id = $stmt->insert_id;
    $stmt->close();
    header('Location: index.php?success=petition_created&id=' . $new_id);
    exit();
} else {
    error_log("Erreur insertion pétition : " . $stmt->error);
    $stmt->close();
    header("Location: petition.php?error=db");
    exit();
}
?>