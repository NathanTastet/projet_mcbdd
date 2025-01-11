<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// On récupère le rôle stocké en session, ou "guest" par défaut
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Menu Principal</title>
    <!-- Import du fichier CSS global -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Barre de statut (top) : affiche si on est connecté en 'admin' ou 'guest' -->
<div class="status-bar">
    <?php
        if ($userRole === 'admin') {
            echo "Vous êtes connecté en tant qu'admin";
        } else {
            echo "Vous êtes connecté en tant que guest";
        }
    ?>
</div>

<div class="container">
    <h1>Menu Principal</h1>
    <div class="menu">
        <a href="creation_activite.php">Créer une nouvelle activité</a>
        <a href="modification_activite.php">Modifier une activité existante</a>

        <!-- Afficher le lien ADMIN uniquement si l'utilisateur est admin -->
        <?php if ($userRole === 'admin'): ?>
            <a href="supervision.php">Supervision (ADMIN)</a>
        <?php else: ?>
            <p class="access-restricted">Supervision (ADMIN) - Accès restreint</p>
        <?php endif; ?>
    </div>

    <!-- Bouton de déconnexion, en rouge -->
    <p class="logout-link">
        <a href="logout.php" class="btn-logout">Se déconnecter</a>
    </p>
</div>

<!-- Footer avec les noms des étudiants et la mention -->
<footer class="credits">
    <p>
        Nathan TASTET, Anis ZOUITER, Daphné RODELET, Faustine BONNEFOY, Guillaume TERRAS
        <br><br>
        Janvier 2025 - IUT Lyon 1
    </p>
</footer>

</body>
</html>
