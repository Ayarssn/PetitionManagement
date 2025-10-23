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
    header('Location: index.php');
    exit();
}

$petition_id = (int)$_GET['id'];
$id_utilisateur = $_SESSION['IDUtilisateur'];
$conn = getConnection();

// Récupérer la pétition et vérifier que l'utilisateur en est le créateur
$sql = "SELECT * FROM Petition WHERE IDP = ? AND IDUtilisateur = ?";
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

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $nom_porteur = trim($_POST['nom_porteur'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $date_fin = isset($_POST['date_fin']) && !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
    
    // Validation
    $errors = [];
    
    if (empty($titre) || strlen($titre) < 5 || strlen($titre) > 255) {
        $errors[] = "Le titre doit contenir entre 5 et 255 caractères";
    }
    
    if (empty($description) || strlen($description) < 20 || strlen($description) > 5000) {
        $errors[] = "La description doit contenir entre 20 et 5000 caractères";
    }
    
    if (empty($nom_porteur) || strlen($nom_porteur) < 2 || strlen($nom_porteur) > 100) {
        $errors[] = "Le nom du porteur doit contenir entre 2 et 100 caractères";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors[] = "Email invalide";
    }
    
    if ($date_fin !== null) {
        $date_obj = DateTime::createFromFormat('Y-m-d', $date_fin);
        if (!$date_obj || $date_obj < new DateTime()) {
            $errors[] = "La date limite doit être dans le futur";
        }
    }
    
    if (empty($errors)) {
        // Gestion de l'upload d'image
        $image_path = $petition['ImageP']; // Garder l'ancienne image par défaut
        
        if (isset($_FILES['image_petition']) && $_FILES['image_petition']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5 MB
            
            $file_type = $_FILES['image_petition']['type'];
            $file_size = $_FILES['image_petition']['size'];
            $file_name = $_FILES['image_petition']['name'];
            
            if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                if (!file_exists('uploads')) {
                    mkdir('uploads', 0755, true);
                }
                
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $filename = uniqid('petition_') . '_' . time() . '.' . $extension;
                $upload_path = 'uploads/' . $filename;
                
                if (move_uploaded_file($_FILES['image_petition']['tmp_name'], $upload_path)) {
                    // Supprimer l'ancienne image si elle existe
                    if (!empty($petition['ImageP']) && file_exists('uploads/' . $petition['ImageP'])) {
                        unlink('uploads/' . $petition['ImageP']);
                    }
                    $image_path = $filename;
                }
            }
        }
        
        // Mise à jour de la pétition
        if ($date_fin !== null && $image_path !== null) {
            $update_sql = "UPDATE Petition 
                          SET TitreP = ?, DescriptionP = ?, NomPorteurP = ?, Email = ?, DateFinP = ?, ImageP = ?
                          WHERE IDP = ? AND IDUtilisateur = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssssii", $titre, $description, $nom_porteur, $email, $date_fin, $image_path, $petition_id, $id_utilisateur);
        } elseif ($date_fin !== null) {
            $update_sql = "UPDATE Petition 
                          SET TitreP = ?, DescriptionP = ?, NomPorteurP = ?, Email = ?, DateFinP = ?
                          WHERE IDP = ? AND IDUtilisateur = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssssii", $titre, $description, $nom_porteur, $email, $date_fin, $petition_id, $id_utilisateur);
        } elseif ($image_path !== null) {
            $update_sql = "UPDATE Petition 
                          SET TitreP = ?, DescriptionP = ?, NomPorteurP = ?, Email = ?, ImageP = ?
                          WHERE IDP = ? AND IDUtilisateur = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssssii", $titre, $description, $nom_porteur, $email, $image_path, $petition_id, $id_utilisateur);
        } else {
            $update_sql = "UPDATE Petition 
                          SET TitreP = ?, DescriptionP = ?, NomPorteurP = ?, Email = ?
                          WHERE IDP = ? AND IDUtilisateur = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssii", $titre, $description, $nom_porteur, $email, $petition_id, $id_utilisateur);
        }
        
        if ($update_stmt && $update_stmt->execute()) {
            $update_stmt->close();
            header('Location: index.php?success=modification');
            exit();
        } else {
            $errors[] = "Erreur lors de la mise à jour";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la pétition - SpeakOut</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>SpeakOut</span>
                </div>
                <nav class="header-nav">
                    <span class="user-info">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['NomUtilisateur']); ?>
                    </span>
                    <a href="index.php" class="btn-nav-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </nav>
            </div>
        </header>

        <div class="hero-small">
            <h1><i class="bi bi-pencil-square"></i> Modifier la pétition</h1>
            <p>Modifiez les informations de votre pétition</p>
        </div>

        <!-- Messages d'erreur -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="form-card">
            <h3><i class="bi bi-file-earmark-text"></i> Formulaire de modification</h3>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titre">Titre de la pétition *</label>
                    <input type="text" id="titre" name="titre" required minlength="5" maxlength="255" 
                           value="<?php echo htmlspecialchars($petition['TitreP']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description complète *</label>
                    <textarea id="description" name="description" required minlength="20" maxlength="5000" 
                              rows="8"><?php echo htmlspecialchars($petition['DescriptionP']); ?></textarea>
                    <small class="form-text"><span id="charCount"><?php echo strlen($petition['DescriptionP']); ?></span>/5000 caractères</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom_porteur">Nom du porteur *</label>
                        <input type="text" id="nom_porteur" name="nom_porteur" required minlength="2" maxlength="100" 
                               value="<?php echo htmlspecialchars($petition['NomPorteurP']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required maxlength="100" 
                               value="<?php echo htmlspecialchars($petition['Email']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="date_fin">Date limite (optionnelle)</label>
                    <input type="date" id="date_fin" name="date_fin" 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           value="<?php echo $petition['DateFinP']; ?>">
                    <small class="form-text">Laissez vide pour aucune date limite</small>
                </div>

                <div class="form-group">
                    <label for="image_petition">Changer l'image (optionnelle)</label>
                    <?php if (!empty($petition['ImageP'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="uploads/<?php echo htmlspecialchars($petition['ImageP']); ?>" 
                                 alt="Image actuelle" 
                                 style="max-width: 200px; border-radius: 8px; border: 2px solid #e5e7eb;">
                            <p style="margin-top: 5px; font-size: 0.9rem; color: #6b7280;">Image actuelle</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image_petition" name="image_petition" accept="image/*">
                    <small class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (Max 5MB) - Laissez vide pour conserver l'image actuelle</small>
                </div>

                <div class="form-row">
                    <button type="submit" class="btn btn-primary btn-large">
                        <i class="bi bi-check-circle"></i> Enregistrer les modifications
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-large">
                        <i class="bi bi-x-circle"></i> Annuler
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2025 SpeakOut - Tous droits réservés</p>
        </footer>
    </div>

    <script>
        // Compteur de caractères
        const description = document.getElementById('description');
        const charCount = document.getElementById('charCount');
        
        description.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    </script>
</body>
</html>