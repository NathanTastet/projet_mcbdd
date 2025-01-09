<?php

// Vérifier si l'utilisateur est connecté, sinon le renvoyer sur login.php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// Connexion à la base de données
require_once 'db_connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logFile = __DIR__ . '/debug/debug_log.txt';
    file_put_contents($logFile, "Début du script\n", FILE_APPEND);

    // Récupération des données sous forme d'objet JSON
    $data = json_decode(file_get_contents('php://input'), true);

    file_put_contents($logFile, print_r($data, true)); // Inspecter les données JSON reçues
    
    $activityId = $data['activityId'] ?? null;
    $name = $data['name'] ?? null;
    $date = $data['date'] ?? null;
    $startHour = $data['startHour'] ?? null;
    $endHour = $data['endHour'] ?? null;
    $resources = $data['resources'] ?? [];

    $startHour = explode('+', $startHour)[0];
    $endHour = explode('+', $endHour)[0];

    file_put_contents($logFile, print_r(compact('activityId', 'name', 'date', 'startHour', 'endHour', 'resources'), true), FILE_APPEND);
    // test de la conversion



    if ($activityId && $name && $date && $startHour && $endHour) {
        try {
            // Insertion dans temp_activities
            $stmt = $pdo->prepare("INSERT INTO temp_activities (activityId, name, date, startHour, endHour) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$activityId, $name, $date, $startHour, $endHour]);

            // Récupération de l'ID de l'activité temporaire insérée
            $tempActivityId = $pdo->lastInsertId();

            // Insertion des ressources associées
            $stmtResource = $pdo->prepare("INSERT INTO temp_activity_resources (idActivity, idRessource) VALUES (?, ?)");
            foreach ($resources as $resourceId) {
                $stmtResource->execute([$tempActivityId, $resourceId]);
            }

            echo json_encode(['success' => true, 'message' => 'Activité temporaire enregistrée avec succès.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'insertion.', 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
