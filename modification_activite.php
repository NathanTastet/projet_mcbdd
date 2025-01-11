<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// Connexion à la base de données
require_once 'db_connection.php'; 

// On va préparer la liste de tous les noms d’activités pour l’auto-complétion
$allActivityNames = [];
try {
    // On récupère tous les noms distincts d'activités
    $stmt = $pdo->prepare("SELECT DISTINCT name FROM activities ORDER BY name");
    $stmt->execute();
    $allActivityNames = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (Exception $e) {
    die("Erreur lors de la récupération des noms d’activités : " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modification d'une activité</title>

    <!-- Import du CSS Awesomplete pour l'autocomplétion -->
    <link rel="stylesheet" href="libs/awesomeplete.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>Modification d'une activité</h1>

<fieldset>
    <legend>Recherche d'activité à modifier</legend>
    <form method="POST" action="modification_activite.php">
        <div class="search-field">
            <label for="activity_name">Nom de l'activité :</label>
            <input 
                type="text" 
                name="activity_name" 
                id="activity_name" 
                placeholder="Entrez le nom de l'activité"
            >
            <button type="submit">Rechercher</button>
        </div>
    </form>
</fieldset>

<?php
// Si l'utilisateur a saisi un nom d'activité
if (!empty($_POST['activity_name'])) {
    $activityName = trim($_POST['activity_name']);

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
                GROUP_CONCAT(r.name) AS ressources,
                GROUP_CONCAT(r.idADE) AS id_ressources
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
                
                // Conversion d'un nombre de slots (duration) en heures:minutes
                $minutes = $row['duration'] * 15;
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                $durationFormatted = $hours . 'h' . str_pad($mins, 2, '0', STR_PAD_LEFT);

                // Ressources associées
                $resourceDisplay = !empty($row['ressources']) 
                    ? str_replace(',', ', ', $row['ressources'])
                    : "Aucune ressource";

                echo "<tr>";
                echo "<td>".htmlspecialchars($row['name'])."</td>";
                echo "<td>".htmlspecialchars($row['date'])."</td>";
                echo "<td>".htmlspecialchars($row['startHour'])."</td>";
                echo "<td>".htmlspecialchars($row['endHour'])."</td>";
                echo "<td>".htmlspecialchars($durationFormatted)."</td>";
                echo "<td>".htmlspecialchars($resourceDisplay)."</td>";

                // FORMULAIRE POST pour aller vers selection_creneaux.php
                // en envoyant les infos nécessaires à la modification
                echo "<td>
                        <form action='selection_creneaux.php' method='POST' style='margin:0;'>
                            <input type='hidden' name='name' value='".htmlspecialchars($row['name'], ENT_QUOTES)."'>
                            <input type='hidden' name='date' value='".htmlspecialchars($row['date'], ENT_QUOTES)."'>
                            <input type='hidden' name='ressources' value='".htmlspecialchars($resourceDisplay, ENT_QUOTES)."'>
                            <input type='hidden' name='duree' value='".htmlspecialchars($minutes, ENT_QUOTES)."'>
                            <input type='hidden' name='id' value='".htmlspecialchars($row['id'], ENT_QUOTES)."'>
                            <input type='hidden' name='id_ressources' value='".htmlspecialchars($row['id_ressources'], ENT_QUOTES)."'>
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
?>

<!-- Import du JS Awesomplete pour l'autocomplétion -->
<script src="libs/awesomeplete.min.js"></script>
<script>
    // Liste de tous les noms d'activités récupérés en PHP
    const allActivities = <?php echo json_encode($allActivityNames); ?>;

    // On initialise Awesomplete sur le champ de saisie
    const inputActivity = document.getElementById('activity_name');
    new Awesomplete(inputActivity, {
        list: allActivities,
        minChars: 1,
        autoFirst: true
    });
</script>
<footer class="footer">
    <a href="logout.php" class="footer-btn btn-logout">Se déconnecter</a>
    <a href="menu.php" class="footer-btn btn-menu">Menu principal</a>
</footer>

</body>
</html>
