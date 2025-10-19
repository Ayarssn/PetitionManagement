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
    <title>Gestion des Pétitions</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1>📝 Gestion des Pétitions</h1>
                <p>Plateforme de création et signature de pétitions citoyennes</p>
            </div>
        </header>

        <!-- Alert Success -->
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            ✅ Votre signature a été enregistrée avec succès ! Merci pour votre soutien.
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="section-header">
                <h2>📋 Pétitions en cours</h2>
                <p id="notification" class="notification"></p>
            </div>

            <div class="petitions-grid">
                <?php foreach($petitions as $petition): ?>
                <div class="petition-card" data-id="<?php echo $petition['IDP']; ?>">
                    <!-- Header -->
                    <div class="petition-header">
                        <h3><?php echo e($petition['TitreP']); ?></h3>
                        <span class="badge"><?php echo $petition['nb_signatures']; ?> 
                            <?php echo $petition['nb_signatures'] > 1 ? 'signatures' : 'signature'; ?>
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
                            📅 <?php echo date('d/m/Y', strtotime($petition['DateAjoutP'])); ?>
                        </span>
                        <span class="meta-item">
                            👤 <?php echo e($petition['NomPorteurP']); ?>
                        </span>
                    </div>

                    <!-- Deadline -->
                    <?php if($petition['DateFinP'] && strtotime($petition['DateFinP']) >= time()): ?>
                    <div class="deadline">
                        ⏰ Date limite : <?php echo date('d/m/Y', strtotime($petition['DateFinP'])); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="card-actions">
                        <a href="signature.php?id=<?php echo $petition['IDP']; ?>" class="btn btn-primary">
                            ✍️ Signer
                        </a>
                        <button class="btn btn-secondary" onclick="toggleSignatures(<?php echo $petition['IDP']; ?>)">
                            👥 Voir signatures
                        </button>
                    </div>

                    <!-- Zone signatures (cachée par défaut) -->
                    <div id="signatures-<?php echo $petition['IDP']; ?>" class="signatures-zone" style="display: none;">
                        <div class="signatures-header">
                            <h4>Dernières signatures</h4>
                            <span class="loading">Chargement...</span>
                        </div>
                        <div class="signatures-list"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2025 Gestion des Pétitions - Tous droits réservés</p>
        </footer>
    </div>

    <script src="js/main.js"></script>
</body>
</html>