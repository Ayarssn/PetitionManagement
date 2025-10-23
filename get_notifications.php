<?php
/**
 * Endpoint léger pour vérifier s'il existe une nouvelle pétition.
 *
 * Mode d'emploi:
 * - si le client envoie ?last_seen=ID, le script renvoie { success:true, latest_id: X, new: true|false, petition: {...} }
 *   where new=true iff latest_id > last_seen and petition contains the latest petition row
 * - si le client n'envoie pas last_seen, le script renvoie { success:true, latest_id: X, new: false }
 *
 * Cette approche évite tout état côté serveur (pas de fichier last_petition_count.txt)
 * et laisse le client décider s'il considère la dernière pétition comme "nouvelle".
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$conn = getConnection();

// Récupérer la dernière pétition (par ID décroissant)
$q = "SELECT IDP, TitreP, DateAjoutP FROM Petition ORDER BY IDP DESC LIMIT 1";
$res = $conn->query($q);

if (!$res) {
    echo json_encode(['success' => false, 'error' => 'Erreur requête']);
    exit();
}

if ($res->num_rows === 0) {
    echo json_encode(['success' => true, 'latest_id' => 0, 'new' => false]);
    $res->free();
    $conn->close();
    exit();
}

$latest = $res->fetch_assoc();
$res->free();

$latest_id = (int)$latest['IDP'];

// Client may send last_seen
$last_seen = 0;
if (isset($_GET['last_seen'])) {
    $last_seen = (int)$_GET['last_seen'];
}

$payload = [
    'success' => true,
    'latest_id' => $latest_id,
    'new' => false
];

if ($last_seen > 0 && $latest_id > $last_seen) {
    // New petition available
    $payload['new'] = true;
    $payload['petition'] = [
        'IDP' => $latest_id,
        'TitreP' => $latest['TitreP'],
        'DateAjoutP' => $latest['DateAjoutP']
    ];
} else {
    // If client didn't provide last_seen, send latest_id but don't mark as new
    // If client provided last_seen and no new -> new=false
}

echo json_encode($payload);

$conn->close();

?>