<?php
require_once 'config.php';

$conn = getConnection();

// Récupérer toutes les pétitions avec le nombre de signatures
$sql = "SELECT p.*, COUNT(s.IDS) as nb_signatures 
        FROM Petition p 
        LEFT JOIN Signature s ON p.IDP = s.IDP 
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
                    <a href="petition.php" class="btn-nav-primary">Créer une pétition</a>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="hero">
            <h1>Make a Better World</h1>
            <p class="hero-subtitle">Votre voix compte. Créez ou signez des pétitions pour le changement.</p>
            <a href="petition.php" class="btn btn-primary btn-hero">
                <i class="bi bi-plus-circle"></i> START PETITION
            </a>
            <p class="hero-tagline">It's free. It's easy. And it results in real change.</p>
        </section>

        <!-- Alert Success -->
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i> Votre signature a été enregistrée avec succès ! Merci pour votre soutien.
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="section-header">
                <h2><i class="bi bi-card-list"></i> Pétitions en cours</h2>
                <div id="notification" class="notification"></div>
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
                        <!-- Header -->
                        <div class="petition-header">
                            <h3><?php echo e($petition['TitreP']); ?></h3>
                            <span class="badge">
                                <?php echo $petition['nb_signatures']; ?> 
                                <?php echo $petition['nb_signatures'] > 1 ? 'supporters' : 'supporter'; ?>
                            </span>
                        </div>

                        <!-- Description -->
                        <p class="petition-description">
                            <?php 
                            $desc = $petition['DescriptionP'];
                            echo e(strlen($desc) > 150 ? substr($desc, 0, 150) . '...' : $desc); 
                            ?>
                        </p>

                        <!-- Meta Info -->
                        <div class="petition-meta">
                            <span class="meta-item">
                                <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y', strtotime($petition['DateAjoutP'])); ?>
                            </span>
                            <span class="meta-item">
                                <i class="bi bi-person"></i> <?php echo e($petition['NomPorteurP']); ?>
                            </span>
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
    // ✅ Forcer la vérification immédiate au chargement
    console.log('🔍 Page chargée - Vérification des nouvelles pétitions...');
    </script>
</body>
</html>