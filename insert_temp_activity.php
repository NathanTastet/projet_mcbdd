<?php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le renvoyer sur login.php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

// Connexion à la base de données
require_once 'db_connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logFile = __DIR__ . '/debug/debug_log.txt';
    file_put_contents($logFile, "Début du script\n", FILE_APPEND);

    // Récupération des données sous forme d'objet JSON
    $data = json_decode(file_get_contents('php://input'), true);
    file_put_contents($logFile, print_r($data, true), FILE_APPEND);

    $activityId = $data['activityId'] ?? null;
    $name = $data['name'] ?? null;
    $date = $data['date'] ?? null;
    $startHour = $data['startHour'] ?? null;
    $endHour = $data['endHour'] ?? null;
    $id_ressources = $data['id_ressources'] ?? null;

    // Retirer les fuseaux horaires pour simplifier
    $startHour = explode('+', $startHour)[0];
    $endHour = explode('+', $endHour)[0];

    if ($activityId && $name && $date && $startHour && $endHour) {
        try {
            // Calcul de la semaine scolaire
            $startSchoolDate = new DateTime('2024-09-03');
            $currentDate = new DateTime($date);
            $weekInterval = $startSchoolDate->diff($currentDate)->days / 7;
            $week = floor($weekInterval) + 2; // Semaine 2 commence au 2024-09-03
            // Calcul du jour (1 = lundi, ..., 7 = dimanche)
            $day = $currentDate->format('N');

            // Calcul des slots
            $startTime = new DateTime($startHour);
            $endTime = new DateTime($endHour);

            $baseSlot = 4; // 8h correspond au slot 4
            $startSlot = $baseSlot + (int)(($startTime->format('H') * 60 + $startTime->format('i')) / 15 - (8 * 60 / 15));
            $absoluteSlot = $startSchoolDate->diff($currentDate)->days * 96 + $startSlot; // 96 slots par jour

            // Calcul de la durée en minutes
            $duration = $startTime->diff($endTime)->h * 60 + $startTime->diff($endTime)->i;

            // Valeurs par défaut
            $repetition = 0;
            $session = 0;
            $color = '255,255,255';

            // Insertion dans temp_activities
            $stmt = $pdo->prepare("INSERT INTO temp_activities (repetition, session, activityId, name, week, day, slot, absoluteSlot, date, startHour, endHour, duration, color) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$repetition, $session, $activityId, $name, $week, $day, $startSlot, $absoluteSlot, $date, $startHour, $endHour, $duration, $color]);

            // Insertion des ressources associées
            $stmtResource = $pdo->prepare("INSERT INTO temp_activity_resources (idActivity, idRessource) VALUES (?, ?)");
            $resourceList = explode(',', $id_ressources);
            foreach ($resourceList as $resourceId) {
                $stmtResource->execute([$activityId, trim($resourceId)]);
            }

            echo json_encode(['success' => true, 'message' => 'Activité temporaire enregistrée avec succès.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'insertion.' . $e->getMessage(), 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
