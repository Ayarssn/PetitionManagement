<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
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

    // Si pas d'erreur captcha, continuer
    if (empty($error)) {
        // Vérifier si les champs sont remplis
        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
        } else {
            $conn = getConnection();
            
            // Vérifier si l'utilisateur existe
            $sql = "SELECT IDUtilisateur, MotDePasse, NomUtilisateur, Email, Nom, Prenom 
                    FROM Utilisateur 
                    WHERE Email = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                $error = "Erreur de connexion à la base de données";
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();

                    // Vérification du mot de passe hashé
                    if (password_verify($password, $row['MotDePasse'])) {
                        // Démarrer la session et enregistrer les infos de l'utilisateur
                        $_SESSION['IDUtilisateur'] = $row['IDUtilisateur'];
                        $_SESSION['NomUtilisateur'] = $row['NomUtilisateur'];
                        $_SESSION['Email'] = $row['Email'];
                        $_SESSION['Nom'] = $row['Nom'];
                        $_SESSION['Prenom'] = $row['Prenom'];

                        // Rediriger vers la page d'accueil
                        header('Location: index.php?success=login');
                        exit;
                    } else {
                        $error = "Mot de passe incorrect";
                    }
                } else {
                    $error = "Aucun compte trouvé avec cet email";
                }
                $stmt->close();
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
    <title>Connexion - SpeakOut</title>
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
            <h1><i class="bi bi-box-arrow-in-right"></i> Connexion</h1>
            <p>Connectez-vous pour créer et gérer vos pétitions</p>
        </div>

        <!-- Form Card -->
        <div class="form-card" style="max-width: 500px; margin: 2rem auto;">
            <h3><i class="bi bi-person-circle"></i> Accédez à votre compte</h3>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Adresse email *</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           placeholder="votre.email@exemple.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <input type="password" id="password" name="password" class="form-control" required
                           placeholder="Votre mot de passe">
                </div>

                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-large">
                    <i class="bi bi-box-arrow-in-right"></i> Se connecter
                </button>

                <div class="text-center" style="margin-top: 1rem;">
                    <p>Pas encore de compte ? <a href="register.php" style="color: #2563eb; font-weight: 500;">S'inscrire</a></p>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2025 SpeakOut - Tous droits réservés</p>
        </footer>
    </div>
</body>
</html>