<?php
session_start();

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
if (!empty($_POST['activity_name'])) {
    $activityName = trim($_POST['activity_name']);

    require_once 'db_connection.php';

    try {
        // Requête qui fusionne les activités identiques (même nom, date, horaires, durée)
        $sql = "
            SELECT
                a.name,
                a.date,
                a.startHour,
                a.endHour,
                a.duration,
                GROUP_CONCAT(DISTINCT r.name SEPARATOR ', ') AS resource_list
            FROM activities a
            LEFT JOIN activity_resource ar ON a.id = ar.idActivity
            LEFT JOIN ressources r ON ar.idRessource = r.id
            WHERE a.name LIKE :searchName
            GROUP BY a.name,
                     a.date,
                     a.startHour,
                     a.endHour,
                     a.duration
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
                    <th>Durée (slots)</th>
                    <th>Ressources associées</th>
                    <th>Action</th>
                  </tr>";

            foreach ($results as $row) {
                
                // 1 slot = 15 min (si vous voulez l'afficher en HH:MM, faites la conversion)
                // Ici, on laisse le champ brut, ou on convertit si besoin
                $durationSlots = (int) $row['duration'];
                $resourceDisplay = (!empty($row['resource_list']))
                    ? $row['resource_list']
                    : "Aucune ressource associée";

                echo "<tr>";
                echo "<td>".htmlspecialchars($row['name'])."</td>";
                echo "<td>".htmlspecialchars($row['date'])."</td>";
                echo "<td>".htmlspecialchars($row['startHour'])."</td>";
                echo "<td>".htmlspecialchars($row['endHour'])."</td>";
                echo "<td>".$durationSlots."</td>"; 
                // ou conversion $hours = floor($durationSlots*15 / 60) ...
                echo "<td>".htmlspecialchars($resourceDisplay)."</td>";

                // Problème : on n’a plus d’ID unique à modifier (plusieurs activités fusionnées).
                // Soit on retire le bouton, soit on gère un "Modifier" global.
                echo "<td>
                        <form action='modification_activite.php' method='GET' style='margin:0;'>
                            <button type='submit' class='btn-modify'>Modifier</button>
                        </form>
                      </td>";
                
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='no-result'>Aucune activité trouvée pour le nom : <strong>".htmlspecialchars($activityName)."</strong></p>";
        }

    } catch (PDOException $e) {
        echo "Erreur lors de la requête : " . $e->getMessage();
    }
}

// Si on clique sur "Modifier" alors qu’on n’a plus d’ID unique… à vous de décider la logique !
// Par exemple, vous pourriez demander d'abord à l'utilisateur de sélectionner laquelle des ID
// il veut vraiment modifier.
if (!empty($_GET['idActivity'])) {
    // ...
}

?>

</body>
</html>
