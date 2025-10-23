<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Pétition - SpeakOut</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1><i class="bi bi-plus-circle"></i> Créer une Nouvelle Pétition</h1>
                <p>Lancez votre pétition et mobilisez les citoyens</p>
                <a href="index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Retour à la liste</a>
            </div>
        </header>

        <!-- Form Card -->
        <div class="form-card">
            <h3><i class="bi bi-file-earmark-text"></i> Formulaire de création</h3>
            
            <div id="message" class="message" style="display: none;"></div>

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
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom_porteur">Votre nom *</label>
                        <input type="text" id="nom_porteur" name="nom_porteur" required minlength="2" maxlength="100" 
                               placeholder="Nom du porteur">
                    </div>
                    <div class="form-group">
                        <label for="email">Votre email *</label>
                        <input type="email" id="email" name="email" required maxlength="100" 
                               placeholder="votre.email@exemple.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="date_fin">Date limite (optionnelle)</label>
                    <input type="date" id="date_fin" name="date_fin" 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="image_petition">Image de la pétition (optionnelle)</label>
                    <input type="file" id="image_petition" name="image_petition" accept="image/*">
                    <small class="form-text">Formats acceptés: JPG, PNG, GIF (Max 5MB)</small>
                </div>

                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>
                    <small class="form-text">Clé de test Google reCAPTCHA - À remplacer par votre propre clé en production</small>
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

    <script src="js/main.js"></script>
    <script src="js/petition.js"></script>
</body>
</html>