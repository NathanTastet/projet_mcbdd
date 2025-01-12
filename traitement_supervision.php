<?php
session_start();

// 1) Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// 2) Vérifier si l'utilisateur a le rôle "admin"
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Connexion à la base de données
require_once 'db_connection.php'; 

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les paramètres envoyés par le formulaire
    $tempId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Vérifications de base
    if ($tempId <= 0 || ($action !== 'accept' && $action !== 'refuse')) {
        header("Location: supervision.php");
        exit;
    }

    try {
        // Sélectionner la ligne correspondante dans temp_activities
        $sqlSelectTemp = "
            SELECT *
            FROM temp_activities
            WHERE id = :tempId
        ";
        $stmtSelectTemp = $pdo->prepare($sqlSelectTemp);
        $stmtSelectTemp->execute([':tempId' => $tempId]);
        $tempRow = $stmtSelectTemp->fetch(PDO::FETCH_ASSOC);

        if (!$tempRow) {
            header("Location: supervision.php");
            exit;
        }

        if ($action === 'accept') {
            // Récupérer l'id de l'activité depuis la ligne temporaire
            $id = $tempRow['id'];

            // Vérifier si cette activité existe déjà dans la table "activities"
            $stmtCheck = $pdo->prepare("SELECT id FROM activities WHERE id = :id");
            $stmtCheck->execute([':id' => $id]);
            $existingActivity = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            // Récupérer les nouvelles valeurs depuis la ligne temporaire
            $newName      = $tempRow['name'];
            $newDate      = $tempRow['date'];
            $newStartHour = $tempRow['startHour'];
            $newEndHour   = $tempRow['endHour'];
            $newDuration  = $tempRow['duration'];

            if ($existingActivity) {
                // **Cas Modification** : l'activité existe déjà dans "activities"

                // a) Mettre à jour la table "activities"
                $sqlUpdate = "
                    UPDATE activities
                    SET
                        name      = :name,
                        date      = :date,
                        startHour = :startHour,
                        endHour   = :endHour,
                        duration  = :duration
                    WHERE id = :id
                ";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':name'      => $newName,
                    ':date'      => $newDate,
                    ':startHour' => $newStartHour,
                    ':endHour'   => $newEndHour,
                    ':duration'  => $newDuration,
                    ':id'        => $id
                ]);

                // b) Gérer les ressources :

                // Récupérer les ressources actuelles liées à l'activité
                $sqlSelectCurrentRes = "
                SELECT idRessource 
                FROM activity_resource
                WHERE idActivity = :id
                ";
                $stmtSelectCurrentRes = $pdo->prepare($sqlSelectCurrentRes);
                $stmtSelectCurrentRes->execute([':id' => $id]);
                $currentResources = $stmtSelectCurrentRes->fetchAll(PDO::FETCH_COLUMN);

                // Récupérer les nouvelles ressources depuis "temp_activity_resources"
                $sqlSelectTempRes = "
                SELECT idRessource 
                FROM temp_activity_resources
                WHERE idActivity = :tempId
                ";
                $stmtSelectTempRes = $pdo->prepare($sqlSelectTempRes);
                $stmtSelectTempRes->execute([':tempId' => $tempId]);
                $newResources = $stmtSelectTempRes->fetchAll(PDO::FETCH_COLUMN);

                // Déterminer les ressources à supprimer (actuelles mais absentes des nouvelles)
                $resourcesToDelete = array_diff($currentResources, $newResources);
                if (!empty($resourcesToDelete)) {
                    $sqlDelRes = "
                        DELETE FROM activity_resource 
                        WHERE idActivity = :id 
                        AND idRessource IN (" . implode(',', array_fill(0, count($resourcesToDelete), '?')) . ")
                    ";
                    $stmtDelRes = $pdo->prepare($sqlDelRes);
                    $stmtDelRes->execute(array_merge([$id], $resourcesToDelete));
                }

                // Déterminer les ressources à ajouter (nouvelles mais absentes des actuelles)
                $resourcesToAdd = array_diff($newResources, $currentResources);
                if (!empty($resourcesToAdd)) {
                    $sqlInsertRes = "
                        INSERT INTO activity_resource (idActivity, idRessource)
                        VALUES (:idActivity, :idRessource)
                    ";
                    $stmtInsertRes = $pdo->prepare($sqlInsertRes);

                    foreach ($resourcesToAdd as $resId) {
                        $stmtInsertRes->execute([
                            ':idActivity'  => $id,
                            ':idRessource' => $resId
                        ]);
                    }
                }
            } else {
                // **Cas Création** : l'activité n'existe pas encore dans "activities"
                $sqlInsertAct = "
                INSERT INTO activities 
                (id, name, repetition, session, activityID, week, day, slot, absoluteSlot, date, startHour, endHour, duration, color)
                VALUES 
                (:id, :name, :repetition, :session, :activityID, :week, :day, :slot, :absoluteSlot, :date, :startHour, :endHour, :duration, :color)
                ";
                $stmtInsertAct = $pdo->prepare($sqlInsertAct);
                $stmtInsertAct->execute([
                ':id'   => $id,
                ':name'         => $tempRow['name'],
                ':activityID'   => 0,
                ":repetition"   => 0,
                ":session"      => 0,
                ':week'         => $tempRow['week'],
                ':day'          => $tempRow['day'],
                ':slot'         => $tempRow['slot'],
                ':absoluteSlot' => $tempRow['absoluteSlot'],
                ':date'         => $tempRow['date'],
                ':startHour'    => $tempRow['startHour'],
                ':endHour'      => $tempRow['endHour'],
                ':duration'     => $tempRow['duration'],
                ':color'        => '255,255,255'
                ]);
                
                // b) Insérer les ressources dans "activity_resource"
                $sqlSelectTempRes = "
                    SELECT idRessource 
                    FROM temp_activity_resources
                    WHERE idActivity = :id
                ";
                $stmtSelectTempRes = $pdo->prepare($sqlSelectTempRes);
                $stmtSelectTempRes->execute([':id' => $id]);
                $resources = $stmtSelectTempRes->fetchAll(PDO::FETCH_COLUMN);

                if ($resources) {
                    $sqlInsertRes = "
                        INSERT INTO activity_resource (idActivity, idRessource)
                        VALUES (:idActivity, :idRessource)
                    ";
                    $stmtInsertRes = $pdo->prepare($sqlInsertRes);

                    foreach ($resources as $resId) {
                        $stmtInsertRes->execute([
                            ':idActivity'  => $id,
                            ':idRessource' => $resId
                        ]);
                    }
                }
            }

            // Supprimer la ligne temporaire
            $sqlDeleteTemp = "DELETE FROM temp_activities WHERE id = :tempId";
            $stmtDeleteTemp = $pdo->prepare($sqlDeleteTemp);
            $stmtDeleteTemp->execute([':tempId' => $id]);

            header("Location: supervision.php");
            exit;

        } elseif ($action === 'refuse') {
            // Refus : supprimer simplement la ligne temporaire
            $sqlDeleteTemp = "DELETE FROM temp_activities WHERE id = :tempId";
            $stmtDeleteTemp = $pdo->prepare($sqlDeleteTemp);
            $stmtDeleteTemp->execute([':tempId' => $tempId]);

            header("Location: supervision.php");
            exit;
        }

    } catch (PDOException $e) {
        echo "Erreur : " . htmlspecialchars($e->getMessage());
        exit;
    }
} else {
    header("Location: supervision.php");
    exit;
}
?>
