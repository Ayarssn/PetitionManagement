<?php
session_start();
require_once 'config.php';

// ✅ VÉRIFIER QUE L'UTILISATEUR EST CONNECTÉ
if (!isset($_SESSION['IDUtilisateur'])) {
    header('Location: login.php?error=unauthorized');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Pétition - SpeakOut</title>
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
            <h1><i class="bi bi-plus-circle"></i> Créer une Nouvelle Pétition</h1>
            <p>Lancez votre pétition et mobilisez les citoyens</p>
        </div>

        <!-- Messages d'erreur -->
        <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php 
            switch($_GET['error']) {
                case 'validation': echo "Erreur de validation des données"; break;
                case 'duplicate': echo "Une pétition avec ce titre existe déjà"; break;
                case 'db': echo "Erreur de base de données"; break;
                case 'unauthorized': echo "Vous devez être connecté pour créer une pétition"; break;
                default: echo "Une erreur est survenue";
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="form-card">
            <h3><i class="bi bi-file-earmark-text"></i> Formulaire de création</h3>

            <form id="petitionForm" method="POST" action="traiter_petition.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titre">Titre de la pétition *</label>
                    <input type="text" id="titre" name="titre" required minlength="5" maxlength="255" 
                           placeholder="Ex: Sauvons les océans">
                </div>

                <div class="form-group">
                    <label for="description">Description complète *</label>
                    <textarea id="description" name="description" required minlength="20" maxlength="5000" 
                              rows="8" placeholder="Décrivez en détail votre pétition et ses objectifs..."></textarea>
                    <small class="form-text"><span id="charCount">0</span>/5000 caractères</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom_porteur">Votre nom *</label>
                        <input type="text" id="nom_porteur" name="nom_porteur" required minlength="2" maxlength="100" 
                               placeholder="Nom du porteur"
                               value="<?php echo htmlspecialchars($_SESSION['Nom'] . ' ' . $_SESSION['Prenom']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Votre email *</label>
                        <input type="email" id="email" name="email" required maxlength="100" 
                               placeholder="votre.email@exemple.com"
                               value="<?php echo htmlspecialchars($_SESSION['Email']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="date_fin">Date limite (optionnelle)</label>
                    <input type="date" id="date_fin" name="date_fin" 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    <small class="form-text">La pétition restera active jusqu'à cette date</small>
                </div>

                <div class="form-group">
                    <label for="image_petition">Image de la pétition (optionnelle)</label>
                    <input type="file" id="image_petition" name="image_petition" accept="image/*">
                    <small class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (Max 5MB)</small>
                </div>

                <button type="submit" class="btn btn-primary btn-large">
                    <i class="bi bi-check-circle"></i> Créer la pétition
                </button>
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