<?php
session_start(); // ✅ AJOUTÉ
require_once 'config.php';

$conn = getConnection();

// Récupérer toutes les pétitions avec signatures ET informations utilisateur
$sql = "SELECT p.*, 
        COUNT(s.IDS) as nb_signatures,
        u.NomUtilisateur,
        u.Nom as NomCreateur,
        u.Prenom as PrenomCreateur
        FROM Petition p 
        LEFT JOIN Signature s ON p.IDP = s.IDP 
        LEFT JOIN Utilisateur u ON p.IDUtilisateur = u.IDUtilisateur
        GROUP BY p.IDP 
        ORDER BY p.DateAjoutP DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Erreur requête : " . $conn->error);
}

$petitions = [];
while ($row = $result->fetch_assoc()) {
    $petitions[] = $row;
}

$result->free();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plateforme de gestion et signature de pétitions citoyennes">
    <title>SpeakOut - Plateforme de Pétitions</title>
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
                    <a href="index.php">Accueil</a>
                    
                    <?php if(isset($_SESSION['IDUtilisateur'])): ?>
                        <!-- Utilisateur connecté -->
                        <span class="user-info">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['NomUtilisateur']); ?>
                        </span>
                        <a href="petition.php" class="btn-nav-primary">Créer une pétition</a>
                        <a href="logout.php" class="btn-nav-secondary">Déconnexion</a>
                    <?php else: ?>
                        <!-- Visiteur -->
                        <a href="login.php" class="btn-nav-secondary">Connexion</a>
                        <a href="register.php" class="btn-nav-secondary">S'inscrire</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="hero">
            <h1>Make a Better World</h1>
            <p class="hero-subtitle">Votre voix compte. Créez ou signez des pétitions pour le changement.</p>
            
            <?php if(isset($_SESSION['IDUtilisateur'])): ?>
                <a href="petition.php" class="btn btn-primary btn-hero">
                    <i class="bi bi-plus-circle"></i> START PETITION
                </a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary btn-hero">
                    <i class="bi bi-person-plus"></i> INSCRIVEZ-VOUS POUR CRÉER
                </a>
            <?php endif; ?>
            
            <p class="hero-tagline">It's free. It's easy. And it results in real change.</p>
        </section>

        <!-- Alert Success -->
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i> 
            <?php 
            if($_GET['success'] == 'signature') {
                echo "Votre signature a été enregistrée avec succès ! Merci pour votre soutien.";
            } elseif($_GET['success'] == 'creation') {
                echo "Votre pétition a été créée avec succès !";
            } elseif($_GET['success'] == 'modification') {
                echo "Votre pétition a été modifiée avec succès !";
            } elseif($_GET['success'] == 'suppression') {
                echo "La pétition a été supprimée avec succès.";
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="section-header">
                <h2><i class="bi bi-card-list"></i> Pétitions en cours</h2>
                <div id="notification" class="notification"></div>
                <!-- Zone pour notifications courtes via AJAX -->
                <div id="notifications" class="notifications-area"></div>
            </div>

            <div class="petitions-grid" data-last-check="<?php echo date('Y-m-d H:i:s'); ?>">
                <?php if(empty($petitions)): ?>
                    <div class="empty-state">
                        <p><i class="bi bi-inbox"></i> Aucune pétition disponible pour le moment.</p>
                        <p>Soyez le premier à créer une pétition !</p>
                    </div>
                <?php else: ?>
                    <?php foreach($petitions as $petition): ?>
                    <div class="petition-card" data-id="<?php echo $petition['IDP']; ?>">
                        <!-- Image (optionnelle) -->
                        <?php if(!empty($petition['ImageP']) && file_exists(__DIR__ . '/uploads/' . $petition['ImageP'])): ?>
                        <div class="petition-image">
                            <img src="uploads/<?php echo htmlspecialchars($petition['ImageP']); ?>" alt="<?php echo htmlspecialchars($petition['TitreP']); ?>">
                        </div>
                        <?php endif; ?>

                        <!-- Header -->
                        <div class="petition-header">
                            <h3><?php echo htmlspecialchars($petition['TitreP']); ?></h3>
                            <span class="badge">
                                <?php echo $petition['nb_signatures']; ?> 
                                <?php echo $petition['nb_signatures'] > 1 ? 'supporters' : 'supporter'; ?>
                            </span>
                        </div>

                        <!-- Description -->
                        <p class="petition-description">
                            <?php 
                            $desc = $petition['DescriptionP'];
                            echo htmlspecialchars(strlen($desc) > 150 ? substr($desc, 0, 150) . '...' : $desc); 
                            ?>
                        </p>

                        <!-- Meta Info -->
                        <div class="petition-meta">
                            <span class="meta-item">
                                <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y', strtotime($petition['DateAjoutP'])); ?>
                            </span>
                            <span class="meta-item">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($petition['NomPorteurP']); ?>
                            </span>
                            <?php if(isset($petition['NomUtilisateur'])): ?>
                            <span class="meta-item">
                                <i class="bi bi-shield-check"></i> Par <?php echo htmlspecialchars($petition['NomUtilisateur']); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Deadline -->
                        <?php if($petition['DateFinP'] && strtotime($petition['DateFinP']) >= time()): ?>
                        <div class="deadline">
                            <i class="bi bi-alarm"></i> Date limite : <?php echo date('d/m/Y', strtotime($petition['DateFinP'])); ?>
                        </div>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="card-actions">
                            <a href="signature.php?id=<?php echo $petition['IDP']; ?>" class="btn btn-primary">
                                <i class="bi bi-pen"></i> Signer
                            </a>
                            <button class="btn btn-secondary" onclick="toggleSignatures(<?php echo $petition['IDP']; ?>)">
                                <i class="bi bi-people"></i> Voir signatures
                            </button>
                            
                            <!-- Boutons pour le créateur uniquement -->
                            <?php if(isset($_SESSION['IDUtilisateur']) && $_SESSION['IDUtilisateur'] == $petition['IDUtilisateur']): ?>
                                <a href="modifier_petition.php?id=<?php echo $petition['IDP']; ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil-square"></i> Modifier
                                </a>
                                <button class="btn btn-danger" onclick="confirmerSuppression(<?php echo $petition['IDP']; ?>)">
                                    <i class="bi bi-trash"></i> Supprimer
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Zone signatures (cachée par défaut) -->
                        <div id="signatures-<?php echo $petition['IDP']; ?>" class="signatures-zone" style="display: none;">
                            <div class="signatures-header">
                                <h4><i class="bi bi-bookmark-check"></i> Dernières signatures</h4>
                                <span class="loading">Chargement...</span>
                            </div>
                            <div class="signatures-list"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2025 SpeakOut - Tous droits réservés</p>
        </footer>
    </div>

    <script src="js/main.js"></script>
    <script>
    // Fonction de confirmation de suppression
    function confirmerSuppression(idPetition) {
        if(confirm('Êtes-vous sûr de vouloir supprimer cette pétition ? Cette action est irréversible.')) {
            window.location.href = 'supprimer_petition.php?id=' + idPetition;
        }
    }
    
    // Vérification des nouvelles pétitions
    console.log('🔍 Page chargée - Vérification des nouvelles pétitions...');
    </script>
</body>
</html>