<?php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le renvoyer sur login.php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Menu Principal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .menu {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .menu a {
            text-decoration: none;
            text-align: center;
            display: block;
            padding: 10px;
            color: white;
            background: #3498db;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        .menu a:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Menu Principal</h1>
        <div class="menu">
            <a href="creation_activite.php">Créer une nouvelle activité</a>
            <a href="modification_activite.php">Modifier une activité existante</a>

            <!-- On peut afficher le lien ADMIN uniquement si l'utilisateur est admin -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === "admin"): ?>
                <a href="supervision.php">Supervision (ADMIN)</a>
            <?php else: ?>
                <!-- Soit ne rien afficher, soit afficher un lien grisé, etc. -->
                <p style="color: #888; text-align:center;">Supervision (ADMIN) - Accès restreint</p>
            <?php endif; ?>
        </div>

        <p style="text-align:center; margin-top:20px;">
            <a href="logout.php" style="color:red;">Se déconnecter</a>
        </p>
    </div>
</body>
</html>
