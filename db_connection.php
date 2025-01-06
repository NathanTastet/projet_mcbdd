<?php
// Informations de connexion à la base de données
$host = 'localhost'; // Adresse du serveur
$dbname = 'SAE_BDD'; // Nom de la base de données
$username = 'API';   // Nom d'utilisateur
$password = 'azerty'; // Mot de passe

try {
    // Connexion à la base de données avec PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Gestion des erreurs de connexion
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
