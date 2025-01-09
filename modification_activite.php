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
    require_once 'db_connection.php'; // Vérifiez le nom exact de votre fichier de connexion

    try {
        // Requête : on sélectionne chaque ligne d'activité,
        // en JOINTURE avec la ressource correspondante.
        // 1 ligne = 1 activité * 1 ressource.
        $sql = "
            SELECT 
            a.name,
            a.date,
            a.startHour,
            a.endHour,
            a.duration,
            GROUP_CONCAT(a.id) AS IDS,                     -- Regroupe les IDs
            GROUP_CONCAT(r.name) AS resources              -- Regroupe les ressources associées
            FROM activities a
            LEFT JOIN activity_resource ar ON a.id = ar.idActivity
            LEFT JOIN ressources r ON ar.idRessource = r.idADE
            WHERE a.name LIKE :searchName
            GROUP BY a.name, a.date, a.startHour, a.endHour, a.duration -- Groupement par les colonnes spécifiques
            ORDER BY a.date, a.startHour;                       -- Optionnel : tri pour lisibilité
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
                
                // ---- Conversion d'un nombre de slots en heures:minutes ----
                // Par ex. 4 slots => 4 x 15 = 60 minutes => "1h00"
                $minutes = $row['duration'] * 15;
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                // Format "1h00" ou "2h15"
                $durationFormatted = $hours . 'h' . str_pad($mins, 2, '0', STR_PAD_LEFT);

                // Ressource associée (peut être NULL si pas de ressource ?)
                $resourceDisplay = !empty($row['resources']) 
                ? str_replace(',', ', ', $row['resources'])  // Ajoute un espace après chaque virgule
                : "Aucune ressource";


                echo "<tr>";
                echo "<td>".htmlspecialchars($row['name'])."</td>";
                echo "<td>".htmlspecialchars($row['date'])."</td>";
                echo "<td>".htmlspecialchars($row['startHour'])."</td>";
                echo "<td>".htmlspecialchars($row['endHour'])."</td>";
                echo "<td>".htmlspecialchars($durationFormatted)."</td>";
                echo "<td>".htmlspecialchars($resourceDisplay)."</td>";

                // On conserve l'ID si on veut modifier CET enregistrement précis
                echo "<td>
                        <form action='modification_activite.php' method='GET' style='margin:0;'>
                            <input type='hidden' name='idActivity' value='".(int)$row['id']."'>
                            <button type='submit' class='btn-modify'>Modifier</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            // Aucune activité trouvée
            echo "<p class='no-result'>Aucune activité trouvée pour le nom : <strong>".htmlspecialchars($activityName)."</strong></p>";
        }
    } catch (PDOException $e) {
        echo "Erreur lors de la requête : " . $e->getMessage();
    }
}

// Si on a cliqué sur "Modifier"
if (!empty($_GET['idActivity'])) {
    $idToModify = (int)$_GET['idActivity'];

    echo "<hr>";
    echo "<h3>Modification de l'activité ID : $idToModify</h3>";
    echo "<p>(Ici, vous pouvez afficher un formulaire pour la replanification, la modification des ressources, etc.)</p>";

    // Exemple minimaliste pour récupérer l’activité
    /*
    $sqlDetail = "SELECT * FROM activities WHERE id = :id";
    $stmtDetail = $pdo->prepare($sqlDetail);
    $stmtDetail->execute(['id' => $idToModify]);
    $activityData = $stmtDetail->fetch(PDO::FETCH_ASSOC);

    // Ensuite, un formulaire pré-rempli, etc.
    */
}
?>

</body>
</html>
