<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modification d'une activité</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        fieldset {
            margin-bottom: 20px;
        }
        legend {
            font-weight: bold;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        tr:nth-child(even){
            background-color: #f2f2f2;
        }
        .search-field {
            margin-bottom: 10px;
        }
        .search-field input[type="text"] {
            padding: 5px;
            width: 200px;
        }
        .search-field button {
            padding: 5px 10px;
            cursor: pointer;
        }
        .btn-modify {
            background-color: #3498db;
            color: #fff;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
        }
        .btn-modify:hover {
            background-color: #2980b9;
        }
        .no-result {
            color: red;
        }
    </style>
</head>
<body>

<fieldset>
    <legend>Recherche d'activité à modifier</legend>
    <form method="POST" action="modification_activite.php">
        <div class="search-field">
            <label for="activity_name">Nom de l'activité&nbsp;:</label>
            <input type="text" name="activity_name" id="activity_name" placeholder="Entrez le nom de l'activité">
            <button type="submit">Rechercher</button>
        </div>
    </form>
</fieldset>

<?php
// Si l'utilisateur a saisi un nom d'activité
if (!empty($_POST['activity_name'])) {
    $activityName = trim($_POST['activity_name']);

    // Connexion à la base de données
    require_once 'db_connection.php'; // Assurez-vous du nom exact

    try {
        // Requête : on sélectionne chaque (groupe d')activité(s)
        $sql = "
            SELECT 
                a.name,
                a.date,
                a.startHour,
                a.endHour,
                a.duration,
                a.id,
                GROUP_CONCAT(r.name) AS resources
            FROM activities a
            LEFT JOIN activity_resource ar ON a.id = ar.idActivity
            LEFT JOIN ressources r ON ar.idRessource = r.idADE
            WHERE a.name LIKE :searchName
            GROUP BY a.name, a.date, a.startHour, a.endHour, a.duration, a.id
            ORDER BY a.date, a.startHour
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['searchName' => '%'.$activityName.'%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($results && count($results) > 0) {
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

            foreach ($results as $row) {
                
                // Conversion d'un nombre de slots en heures:minutes
                $minutes = $row['duration'] * 15;
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                $durationFormatted = $hours . 'h' . str_pad($mins, 2, '0', STR_PAD_LEFT);

                // Ressources associées
                $resourceDisplay = !empty($row['resources']) 
                    ? str_replace(',', ', ', $row['resources'])
                    : "Aucune ressource";

                echo "<tr>";
                echo "<td>".htmlspecialchars($row['name'])."</td>";
                echo "<td>".htmlspecialchars($row['date'])."</td>";
                echo "<td>".htmlspecialchars($row['startHour'])."</td>";
                echo "<td>".htmlspecialchars($row['endHour'])."</td>";
                echo "<td>".htmlspecialchars($durationFormatted)."</td>";
                echo "<td>".htmlspecialchars($resourceDisplay)."</td>";

                // FORMULAIRE POST pour aller vers selection_creneaux.php
                // en envoyant le nom de l'activité, les ressources, la semaine et l'année
                echo "<td>
                        <form action='selection_creneaux.php' method='POST' style='margin:0;'>
                            <input type='hidden' name='name' value='".htmlspecialchars($row['name'], ENT_QUOTES)."'>
                            <input type='hidden' name='date' value='".htmlspecialchars($row['date'], ENT_QUOTES)."'>
                            <input type='hidden' name='ressources' value='".htmlspecialchars($resourceDisplay, ENT_QUOTES)."'>
                            <input type='hidden' name='duree' value='".htmlspecialchars($minutes, ENT_QUOTES)."'>
                            <input type='hidden' name='id' value='".htmlspecialchars($row['id'], ENT_QUOTES)."'>
                            <button type='submit' class='btn-modify'>Modifier</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            // Aucune activité trouvée
            echo "<p class='no-result'>Aucune activité trouvée pour : <strong>".htmlspecialchars($activityName)."</strong></p>";
        }
    } catch (PDOException $e) {
        echo "Erreur lors de la requête : " . $e->getMessage();
    }
}

// Ici, on n'a plus nécessairement besoin du GET['idActivity'],
// car on redirige la modification vers selection_creneaux.php en POST.
// Mais vous pouvez laisser un code similaire si vous gérez deux scénarios différents.
?>

</body>
</html>
