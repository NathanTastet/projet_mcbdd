<?php
session_start();       // On reprend la session en cours
session_destroy();     // On détruit toutes les variables de session
header("Location: index.php");  // On redirige l’utilisateur, par exemple sur la page d’accueil
exit;
?>