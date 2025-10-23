<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_utilisateur = trim($_POST['nom_utilisateur'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe"; // Clé de test Google

    // Vérifier le captcha
    if (!empty($recaptchaResponse)) {
        $response = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse");
        if ($response !== false) {
            $responseData = json_decode($response);
            if (!$responseData->success) {
                $error = "Captcha invalide! Veuillez réessayer";
            }
        }
    }

    // Validation des champs
    if (empty($error)) {
        if ($nom_utilisateur === '' || $nom === '' || $prenom === '' || $email === '' || $password === '') {
            $error = "Veuillez remplir tous les champs obligatoires";
        } elseif (strlen($nom_utilisateur) < 3 || strlen($nom_utilisateur) > 50) {
            $error = "Le nom d'utilisateur doit contenir entre 3 et 50 caractères";
        } elseif (strlen($nom) < 2 || strlen($nom) > 50) {
            $error = "Le nom doit contenir entre 2 et 50 caractères";
        } elseif (strlen($prenom) < 2 || strlen($prenom) > 50) {
            $error = "Le prénom doit contenir entre 2 et 50 caractères";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format d'email invalide";
        } elseif (strlen($password) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caractères";
        } elseif ($password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas";
        } else {
            $conn = getConnection();
            
            // Vérifier que le nom d'utilisateur n'existe pas déjà
            $check = $conn->prepare("SELECT IDUtilisateur FROM Utilisateur WHERE NomUtilisateur = ?");
            $check->bind_param("s", $nom_utilisateur);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "Ce nom d'utilisateur est déjà pris";
                $check->close();
            } else {
                $check->close();
                
                // Vérifier que l'email n'existe pas déjà
                $check_email = $conn->prepare("SELECT IDUtilisateur FROM Utilisateur WHERE Email = ?");
                $check_email->bind_param("s", $email);
                $check_email->execute();
                $check_email->store_result();

                if ($check_email->num_rows > 0) {
                    $error = "Un compte existe déjà avec cet email";
                    $check_email->close();
                } else {
                    $check_email->close();
                    
                    // Hasher le mot de passe
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Insérer le nouvel utilisateur
                    $stmt = $conn->prepare("INSERT INTO Utilisateur (NomUtilisateur, Email, MotDePasse, Nom, Prenom, DateInscription) VALUES (?, ?, ?, ?, ?, NOW())");
                    
                    if (!$stmt) {
                        $error = "Erreur lors de la création du compte : " . $conn->error;
                    } else {
                        $stmt->bind_param("sssss", $nom_utilisateur, $email, $hashedPassword, $nom, $prenom);
                        
                        if (!$stmt->execute()) {
                            $error = "Erreur lors de l'insertion : " . $stmt->error;
                        } else {
                            $newIdUtilisateur = $conn->insert_id;
                            $stmt->close();
                            
                            // Connecter automatiquement l'utilisateur
                            $_SESSION['IDUtilisateur'] = $newIdUtilisateur;
                            $_SESSION['NomUtilisateur'] = $nom_utilisateur;
                            $_SESSION['Email'] = $email;
                            $_SESSION['Nom'] = $nom;
                            $_SESSION['Prenom'] = $prenom;

                            // Redirection vers la page d'accueil
                            header("Location: index.php?success=register");
                            exit;
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - SpeakOut</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
                    <a href="index.php" class="btn-nav-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à l'accueil
                    </a>
                </nav>
            </div>
        </header>

        <div class="hero-small">
            <h1><i class="bi bi-person-plus"></i> Créer un compte</h1>
            <p>Rejoignez SpeakOut et faites entendre votre voix</p>
        </div>

        <!-- Form Card -->
        <div class="form-card" style="max-width: 600px; margin: 2rem auto;">
            <h3><i class="bi bi-clipboard-check"></i> Formulaire d'inscription</h3>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nom_utilisateur">Nom d'utilisateur *</label>
                    <input id="nom_utilisateur" name="nom_utilisateur" type="text" required 
                           minlength="3" maxlength="50"
                           placeholder="Ex: johndoe123"
                           value="<?php echo htmlspecialchars($_POST['nom_utilisateur'] ?? ''); ?>">
                    <small class="form-text">Ce nom sera visible publiquement</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input id="nom" name="nom" type="text" required 
                               minlength="2" maxlength="50"
                               placeholder="Votre nom"
                               value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input id="prenom" name="prenom" type="text" required 
                               minlength="2" maxlength="50"
                               placeholder="Votre prénom"
                               value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Adresse email *</label>
                    <input id="email" name="email" type="email" required 
                           maxlength="100"
                           placeholder="votre.email@exemple.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <input id="password" name="password" type="password" required minlength="6"
                               placeholder="Minimum 6 caractères">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe *</label>
                        <input id="confirm_password" name="confirm_password" type="password" required minlength="6"
                               placeholder="Retapez le mot de passe">
                    </div>
                </div>

                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-large">
                    <i class="bi bi-person-plus"></i> Créer mon compte
                </button>

                <div class="text-center" style="margin-top: 1rem;">
                    <p>Vous avez déjà un compte ? <a href="login.php" style="color: #2563eb; font-weight: 500;">Se connecter</a></p>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2025 SpeakOut - Tous droits réservés</p>
        </footer>
    </div>

    <script>
        // Vérification côté client que les mots de passe correspondent
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas !');
                document.getElementById('confirm_password').focus();
            }
        });
    </script>
</body>
</html>