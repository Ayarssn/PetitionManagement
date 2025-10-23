<?php
require_once 'config.php';

// Vérifier l'ID
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$petition_id = (int)$_GET['id'];
$conn = getConnection();

// Récupérer la pétition avec le nombre de signatures
$sql = "SELECT p.*, COUNT(s.IDS) as nb_signatures 
        FROM Petition p 
        LEFT JOIN Signature s ON p.IDP = s.IDP 
        WHERE p.IDP = ? 
        GROUP BY p.IDP";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erreur préparation : " . $conn->error);
}

$stmt->bind_param("i", $petition_id);
$stmt->execute();
$result = $stmt->get_result();
$petition = $result->fetch_assoc();

if(!$petition) {
    $stmt->close();
    header('Location: index.php');
    exit();
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Signer la pétition - <?php echo e($petition['TitreP']); ?>">
    <title>Signer - <?php echo e($petition['TitreP']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1><i class="bi bi-pen"></i> Signer une Pétition</h1>
                <a href="index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Retour à la liste</a>
            </div>
        </header>

        <!-- Petition Details -->
        <div class="petition-detail-card">
            <div class="detail-header">
                <h2><?php echo e($petition['TitreP']); ?></h2>
                <span class="badge-large">
                    <?php echo $petition['nb_signatures']; ?> 
                    <?php echo $petition['nb_signatures'] > 1 ? 'signatures' : 'signature'; ?>
                </span>
            </div>
            
            <p class="detail-description">
                <?php echo nl2br(e($petition['DescriptionP'])); ?>
            </p>
            
            <div class="detail-meta">
                <span><i class="bi bi-calendar3"></i> Créée le <?php echo date('d/m/Y', strtotime($petition['DateAjoutP'])); ?></span>
                <span><i class="bi bi-person"></i> Par <?php echo e($petition['NomPorteurP']); ?></span>
                <span><i class="bi bi-envelope"></i> <?php echo e($petition['Email']); ?></span>
                <?php if($petition['DateFinP']): ?>
                <span><i class="bi bi-alarm"></i> Date limite : <?php echo date('d/m/Y', strtotime($petition['DateFinP'])); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Signature Form -->
        <div class="form-card">
            <h3><i class="bi bi-pen-fill"></i> Ajoutez votre signature</h3>
            
            <div id="message" class="message" style="display: none;"></div>

            <form id="signatureForm" method="POST" action="ajouter_signature.php">
                <input type="hidden" name="petition_id" value="<?php echo $petition_id; ?>">

                <div class="form-group">
                    <label for="titre">Titre de la pétition</label>
                    <input type="text" id="titre" value="<?php echo e($petition['TitreP']); ?>" disabled>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" required minlength="2" maxlength="100" placeholder="Votre nom">
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" required minlength="2" maxlength="100" placeholder="Votre prénom">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required maxlength="100" placeholder="votre.email@exemple.com">
                </div>

                <div class="form-group">
                    <label for="pays">Pays *</label>
                    <input type="text" id="pays" name="pays" value="Maroc" required minlength="2" maxlength="100" placeholder="Votre pays">
                </div>

                <button type="submit" class="btn btn-primary btn-large" id="submitBtn">
                    <i class="bi bi-check-circle"></i> Envoyer ma signature
                </button>
                
                <div id="loadingSpinner" class="spinner" style="display: none;"></div>
            </form>
        </div>

        <!-- Zone des 5 dernières signatures -->
        <div class="recent-signatures-card">
            <h3><i class="bi bi-clock-history"></i> Les 5 dernières signatures</h3>
            <div id="recentSignatures" class="recent-signatures-list">
                <div class="loading-signatures">
                    <i class="bi bi-hourglass-split"></i> Chargement des signatures...
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2025 SpeakOut - Tous droits réservés</p>
        </footer>
    </div>

    <script src="js/main.js"></script>
    <script>
        // ============================================
        // CHARGEMENT DES 5 DERNIÈRES SIGNATURES
        // ============================================
        
        const petitionId = <?php echo $petition_id; ?>;
        
        /**
         * Charger les 5 dernières signatures via XMLHttpRequest
         */
        function loadRecentSignatures() {
            const xhr = new XMLHttpRequest();
            const url = 'get_recent_signatures.php?petition_id=' + petitionId;
            
            xhr.open('GET', url, true);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            displayRecentSignatures(response.signatures);
                        } else {
                            showSignaturesError(response.error || 'Erreur lors du chargement');
                        }
                    } catch (e) {
                        console.error('Erreur parsing JSON:', e);
                        showSignaturesError('Erreur de traitement des données');
                    }
                } else {
                    showSignaturesError('Erreur serveur (' + xhr.status + ')');
                }
            };
            
            xhr.onerror = function() {
                showSignaturesError('Erreur de connexion réseau');
            };
            
            xhr.send();
        }
        
        /**
         * Afficher les signatures dans la zone
         */
        function displayRecentSignatures(signatures) {
            const container = document.getElementById('recentSignatures');
            
            if (!container) return;
            
            if (signatures.length === 0) {
                container.innerHTML = '<div class="no-signatures"><i class="bi bi-inbox"></i> Aucune signature pour le moment. Soyez le premier à signer !</div>';
                return;
            }
            
            let html = '';
            signatures.forEach(function(sig) {
                const date = new Date(sig.date);
                const dateStr = date.toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += '<div class="recent-signature-item">';
                html += '  <div class="signature-info">';
                html += '    <i class="bi bi-person-check-fill"></i>';
                html += '    <strong>' + escapeHtml(sig.prenom) + ' ' + escapeHtml(sig.nom) + '</strong>';
                html += '  </div>';
                html += '  <div class="signature-details">';
                html += '    <span><i class="bi bi-geo-alt"></i> ' + escapeHtml(sig.pays) + '</span>';
                html += '    <span><i class="bi bi-clock"></i> ' + dateStr + '</span>';
                html += '  </div>';
                html += '</div>';
            });
            
            container.innerHTML = html;
        }
        
        /**
         * Afficher une erreur
         */
        function showSignaturesError(message) {
            const container = document.getElementById('recentSignatures');
            if (container) {
                container.innerHTML = '<div class="signatures-error"><i class="bi bi-exclamation-triangle"></i> ' + message + '</div>';
            }
        }
        
        /**
         * Échapper le HTML pour éviter les failles XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Charger les signatures au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentSignatures();
            
            // Recharger toutes les 10 secondes
            setInterval(loadRecentSignatures, 10000);
        });
        
        // Recharger après soumission réussie du formulaire
        const originalSubmit = document.getElementById('signatureForm');
        if (originalSubmit) {
            originalSubmit.addEventListener('submit', function() {
                // Recharger les signatures après 2 secondes (temps de traitement)
                setTimeout(loadRecentSignatures, 2000);
            });
        }
    </script>
</body>
</html>