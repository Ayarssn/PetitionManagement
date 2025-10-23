<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_petitions');
define('DB_USER', 'root');
define('DB_PASS', '');

// Variable globale pour la connexion
$conn = null;

//Fonction de connexion à la base de données avec MySQLi
function getConnection() {
    global $conn;
    
    if ($conn === null) {
        // Créer une nouvelle connexion MySQLi
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Vérifier la connexion
        if ($conn->connect_error) {
            die("Erreur de connexion : " . $conn->connect_error);
        }
        
        // Définir le charset UTF-8
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

//Fonction pour sécuriser les sorties HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Fonction pour échapper les chaînes SQL (mysqli_real_escape_string)
function escape($string) {
    $conn = getConnection();
    return $conn->real_escape_string($string);
}

//Fermer la connexion
function closeConnection() {
    global $conn;
    if ($conn !== null) {
        $conn->close();
        $conn = null;
    }
}

?>