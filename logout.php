<?php
session_start();

// Supprimer toutes les variables de session
$_SESSION = array();

// // Si on veut détruire complètement la session, on supprime aussi le cookie de session
// if (isset($_COOKIE[session_name()])) {
//     setcookie(session_name(), '', time() - 42000, '/');
// }

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil avec message de confirmation
header("Location: index.php?success=logout");
exit();
?>