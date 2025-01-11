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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supervision - Temp Activities</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles_super.css">
    
</head>
<body>

<h1>Écran de supervision</h1>

<!-- Barre d'onglets -->
<div class="tab">
  <button class="tablinks active" onclick="openTab(event, 'modifications')">
    Activités modifiées
  </button>
  <button class="tablinks" onclick="openTab(event, 'creations')">
    Activités créées
  </button>
</div>

<!-- Onglet "Modifications" -->
<div id="modifications" class="tabcontent" style="display:block;"><!-- affiché par défaut -->
<fieldset>
    <legend>Activités modifiées</legend>
    <?php
    try {
        // Requête pour les modifications
        $sqlModif = "
            SELECT 
                tA.id                 AS tempId,
                tA.activityId         AS activityId,
                tA.name               AS newName,
                tA.date               AS newDate,
                tA.startHour          AS newStartHour,
                tA.endHour            AS newEndHour,
                tA.duration           AS newDuration,
                GROUP_CONCAT(r.name)  AS newRessources,

                a.name               AS oldName,
                a.date               AS oldDate,
                a.startHour          AS oldStartHour,
                a.endHour            AS oldEndHour,
                a.duration           AS oldDuration,
                GROUP_CONCAT(rOld.name) AS oldRessources

            FROM temp_activities tA
            JOIN activities a ON tA.activityId = a.activityId
            LEFT JOIN temp_activity_resources tAR ON tA.activityId = tAR.idActivity
            LEFT JOIN ressources r ON tAR.idRessource = r.idADE

            -- pour les anciennes ressources
            LEFT JOIN activity_resource arOld ON a.activityId = arOld.idActivity
            LEFT JOIN ressources rOld ON arOld.idRessource = rOld.idADE

            GROUP BY 
                tA.id, tA.activityId, 
                tA.name, tA.date, tA.startHour, tA.endHour, tA.duration,
                a.name, a.date, a.startHour, a.endHour, a.duration
            ORDER BY tA.date, tA.startHour
        ";


        $stmt = $pdo->prepare($sqlModif);
        $stmt->execute();
        $modifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($modifications && count($modifications) > 0) {
            echo "<table>";
            echo "<tr>
                    <th>Nom</th>
                    <th>Date</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Durée</th>
                    <th>Ressources</th>
                    <th>Action</th>
                  </tr>";

            foreach ($modifications as $row) {
                // Ancienne durée
                $oldMinutes = $row['oldDuration'] * 15;
                $oldH = floor($oldMinutes / 60);
                $oldM = $oldMinutes % 60;
                $oldDurationFormatted = $oldH . 'h' . str_pad($oldM, 2, '0', STR_PAD_LEFT);

                // Nouvelle durée
                $newMinutes = $row['newDuration'] * 15;
                $newH = floor($newMinutes / 60);
                $newM = $newMinutes % 60;
                $newDurationFormatted = $newH . 'h' . str_pad($newM, 2, '0', STR_PAD_LEFT);

                // Ressources
                $oldRes = !empty($row['oldRessources']) 
                            ? str_replace(',', ', ', $row['oldRessources']) 
                            : "Aucune";
                $newRes = !empty($row['newRessources']) 
                            ? str_replace(',', ', ', $row['newRessources']) 
                            : "Aucune";

                echo "<tr>";
                
                // Nom : si différent => barrer l'ancien, sinon afficher qu'une fois
                echo "<td>";
                if ($row['oldName'] !== $row['newName']) {
                    echo "<s>" . htmlspecialchars($row['oldName']) . "</s><br>" 
                         . htmlspecialchars($row['newName']);
                } else {
                    echo htmlspecialchars($row['newName']);
                }
                echo "</td>";

                // Date
                echo "<td>";
                if ($row['oldDate'] !== $row['newDate']) {
                    echo "<s>" . htmlspecialchars($row['oldDate']) . "</s><br>"
                         . htmlspecialchars($row['newDate']);
                } else {
                    echo htmlspecialchars($row['newDate']);
                }
                echo "</td>";

                // Début
                echo "<td>";
                if ($row['oldStartHour'] !== $row['newStartHour']) {
                    echo "<s>" . htmlspecialchars($row['oldStartHour']) . "</s><br>"
                         . htmlspecialchars($row['newStartHour']);
                } else {
                    echo htmlspecialchars($row['newStartHour']);
                }
                echo "</td>";

                // Fin
                echo "<td>";
                if ($row['oldEndHour'] !== $row['newEndHour']) {
                    echo "<s>" . htmlspecialchars($row['oldEndHour']) . "</s><br>"
                         . htmlspecialchars($row['newEndHour']);
                } else {
                    echo htmlspecialchars($row['newEndHour']);
                }
                echo "</td>";

                // Durée
                echo "<td>";
                if ($row['oldDuration'] != $row['newDuration']) {
                    echo "<s>" . htmlspecialchars($oldDurationFormatted) . "</s><br>"
                         . htmlspecialchars($newDurationFormatted);
                } else {
                    echo htmlspecialchars($newDurationFormatted);
                }
                echo "</td>";

                // Ressources
                echo "<td>";
                if ($oldRes !== $newRes) {
                    echo "<s>" . htmlspecialchars($oldRes) . "</s><br>"
                         . htmlspecialchars($newRes);
                } else {
                    echo htmlspecialchars($newRes);
                }
                echo "</td>";

                // Actions (Accepter / Refuser)
                echo "<td>
                        <form action='traitement_temp_activities.php' method='POST' style='display:inline-block; margin-right:5px;'>
                            <input type='hidden' name='id' value='" . (int)$row['tempId'] . "'>
                            <input type='hidden' name='action' value='accept'>
                            <button type='submit' class='btn-accept'>Accepter</button>
                        </form>

                        <form action='traitement_temp_activities.php' method='POST' style='display:inline-block;'>
                            <input type='hidden' name='id' value='" . (int)$row['tempId'] . "'>
                            <input type='hidden' name='action' value='refuse'>
                            <button type='submit' class='btn-refuse'>Refuser</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Aucune activité modifiée n'a été trouvée.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Erreur lors de la requête : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
</fieldset>
</div><!-- fin onglet modifications -->

<!-- Onglet "Créations" -->
<div id="creations" class="tabcontent"><!-- masqué au chargement -->
<fieldset>
    <legend>Activités créées</legend>
    <?php
    try {
        // Requête pour les créations
        $sqlCreate = "
            SELECT 
                tA.id                 AS tempId,
                tA.activityId         AS activityId,
                tA.name               AS name,
                tA.date               AS date,
                tA.startHour          AS startHour,
                tA.endHour            AS endHour,
                tA.duration           AS duration,
                GROUP_CONCAT(r.name)  AS ressources
            FROM temp_activities tA
            LEFT JOIN activities a ON tA.activityId = a.activityId
            LEFT JOIN temp_activity_resources tAR ON tA.activityId = tAR.idActivity
            LEFT JOIN ressources r ON tAR.idRessource = r.idADE
            WHERE a.activityId IS NULL
            GROUP BY 
                tA.id, tA.activityId, 
                tA.name, tA.date, tA.startHour, tA.endHour, tA.duration
            ORDER BY tA.date, tA.startHour
        ";


        $stmt = $pdo->prepare($sqlCreate);
        $stmt->execute();
        $creations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($creations && count($creations) > 0) {
            echo "<table>";
            echo "<tr>
                    <th>Nom</th>
                    <th>Date</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Durée</th>
                    <th>Ressources</th>
                    <th>Action</th>
                  </tr>";

            foreach ($creations as $row) {
                // Durée en format h:m
                $minutes = $row['duration'] * 15;
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                $durationFormatted = $hours . 'h' . str_pad($mins, 2, '0', STR_PAD_LEFT);

                $resourceDisplay = !empty($row['ressources'])
                    ? str_replace(',', ', ', $row['ressources'])
                    : "Aucune ressource";

                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['startHour']) . "</td>";
                echo "<td>" . htmlspecialchars($row['endHour']) . "</td>";
                echo "<td>" . htmlspecialchars($durationFormatted) . "</td>";
                echo "<td>" . htmlspecialchars($resourceDisplay) . "</td>";

                // Boutons Accepter / Refuser
                echo "<td>
                        <form action='traitement_temp_activities.php' method='POST' style='display:inline-block; margin-right:5px;'>
                            <input type='hidden' name='id' value='" . (int)$row['tempId'] . "'>
                            <input type='hidden' name='action' value='accept'>
                            <button type='submit' class='btn-accept'>Accepter</button>
                        </form>

                        <form action='traitement_temp_activities.php' method='POST' style='display:inline-block;'>
                            <input type='hidden' name='id' value='" . (int)$row['tempId'] . "'>
                            <input type='hidden' name='action' value='refuse'>
                            <button type='submit' class='btn-refuse'>Refuser</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Aucune activité créée en attente.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Erreur lors de la requête : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
</fieldset>
</div><!-- fin onglet creations -->

<footer class="footer">
    <a href="logout.php" class="footer-btn btn-logout">Se déconnecter</a>
    <a href="menu.php" class="footer-btn btn-menu">Menu principal</a>
</footer>

<!-- Script pour gérer l’affichage des onglets -->
<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    
    // 1) On masque tous les contenus d'onglet
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
       tabcontent[i].style.display = "none";
    }

    // 2) On enlève la classe "active" de tous les boutons
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
       tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // 3) On affiche celui qu'on a choisi
    document.getElementById(tabName).style.display = "block";

    // 4) On marque le bouton cliqué comme "actif"
    evt.currentTarget.className += " active";
}
</script>

</body>
</html>
