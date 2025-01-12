<?php
session_start();



// On définit des identifiants ADMIN en dur (a hasher et stocker dans une base de données pour + de sécurité)
$adminUsername = "root";
$adminPassword = "secret";

// Si le formulaire "invité" est soumis
if (isset($_POST['guest_login'])) {
    $_SESSION['loggedin'] = true;
    $_SESSION['role'] = "guest";
    // Redirection vers le menu
    header("Location: menu.php");
    exit;
}

// Si le formulaire "admin" est soumis
if (isset($_POST['admin_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Vérification simplifiée
    if ($username === $adminUsername && $password === $adminPassword) {
        $_SESSION['loggedin'] = true;
        $_SESSION['role'] = "admin";
        header("Location: menu.php");
        exit;
    } else {
        $error = "Identifiants admin invalides.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Connexion</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f2f2f2;
        }

        .wrapper {
            max-width: 600px;
            margin: 50px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
        }

        .credits {
            text-align: center;
            font-size: 0.9em;
            color: #666;
            margin-bottom: 30px;
        }

        .description {
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.5em;
            color: #444;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .login-block {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-block form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }

        label {
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            cursor: pointer;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            background: #3498db;
            color: #fff;
            font-size: 1em;
            transition: background 0.3s ease;
        }

        input[type="submit"]:hover {
            background: #2980b9;
        }

        hr {
            margin: 30px 0;
            border: none;
            border-top: 2px solid #ddd;
        }

        .error {
            color: red;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .form-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <h1>Bienvenue</h1>
    <div class="description">
        <p>Bienvenue sur notre plateforme de gestion d'activités. 
           Veuillez choisir votre mode de connexion pour accéder au menu principal.</p>
    </div>

    <!-- Affichage de l'erreur éventuelle -->
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <!-- Connexion en tant qu'invité -->
        <div class="login-block">
            <form method="post">
                <input type="hidden" name="guest_login" value="1">
                <input type="submit" value="Connexion en tant qu'invité">
            </form>
        </div>

        <hr><!-- Trait de séparation -->

        <!-- Connexion admin -->
        <div class="login-block">
            <form method="post">
                <label for="username">Identifiant Admin :</label>
                <input type="text" name="username" id="username" placeholder="Votre login admin..." required>

                <label for="password">Mot de passe :</label>
                <input type="password" name="password" id="password" placeholder="Votre mot de passe..." required>

                <input type="hidden" name="admin_login" value="1">
                <input type="submit" value="Connexion Admin">
            </form>
        </div>
    </div>
</div>

<div class="credits">IUT Lyon 1 - Janvier 2025</div>

</body>
</html>
