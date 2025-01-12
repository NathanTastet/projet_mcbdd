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

include 'db_connection.php';

// Définir le nombre de résultats par page
$results_per_page = 20;

// Récupération des filtres depuis l'URL
$table_filter     = isset($_GET['table_name']) ? trim($_GET['table_name']) : '';
$operation_filter = isset($_GET['operation']) ? trim($_GET['operation']) : '';
$date_from        = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to          = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$term_filter      = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';

// Déterminer la page actuelle
$page    = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
$offset  = ($page - 1) * $results_per_page;

// Construction des clauses WHERE selon les filtres
$where_clauses = [];
$params = [];

if ($table_filter !== '') {
    $where_clauses[] = "table_name = :table_name";
    $params[':table_name'] = $table_filter;
}

if ($operation_filter !== '') {
    $where_clauses[] = "operation = :operation";
    $params[':operation'] = $operation_filter;
}

if ($date_from !== '') {
    $where_clauses[] = "changed_at >= :date_from";
    $params[':date_from'] = $date_from . ' 00:00:00';
}

if ($date_to !== '') {
    $where_clauses[] = "changed_at <= :date_to";
    $params[':date_to'] = $date_to . ' 23:59:59';
}

if ($term_filter !== '') {
    // Recherche classique sur quelques colonnes (ajustez selon vos besoins)
    $where_clauses[] = "(old_values LIKE :search_term OR new_values LIKE :search_term OR changed_by LIKE :search_term)";
    $params[':search_term'] = '%' . $term_filter . '%';
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Compter le nombre total de résultats
$count_sql = "SELECT COUNT(*) FROM journal $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_results = $count_stmt->fetchColumn();
$total_pages = ceil($total_results / $results_per_page);

// Récupérer les résultats pour la page actuelle
$data_sql = "SELECT * FROM journal $where_sql ORDER BY changed_at DESC LIMIT :limit OFFSET :offset";
$data_stmt = $pdo->prepare($data_sql);

// Lier les paramètres
foreach ($params as $key => &$val) {
    $data_stmt->bindParam($key, $val);
}
$data_stmt->bindValue(':limit', (int)$results_per_page, PDO::PARAM_INT);
$data_stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$data_stmt->execute();
$logs = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les listes distinctes pour les filtres
$table_names = $pdo->query("SELECT DISTINCT table_name FROM journal ORDER BY table_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$operations = $pdo->query("SELECT DISTINCT operation FROM journal ORDER BY operation ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Table de Logs</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Styles spécifiques pour la pagination et les filtres */
        .filters {
            max-width: 90%; /* Augmente la largeur */
            margin: 20px auto;
            background: #ffffff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filters form {
            max-width: 80%; /* Augmente la largeur */
            display: flex;
            flex-wrap: nowrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between; 
        }
        .filters .field-group select,
        .filters .field-group input {
            min-width: 150px; 
            flex: 1; 
        }

        .filters button {
            flex-shrink: 0;
            padding: 10px 20px;
        }
        .filters .field-group {
            margin-right: 10px; 
        }

        #search_term {
            min-width: 400px;
        }


        .date-group {
            display: flex;
            gap: 15px;
            flex: 1;
        }
        .filters button {
            padding: 10px 20px;
        }
        .pagination {
            text-align: center;
            margin: 20px 0;
        }
        .pagination a, .pagination span {
            display: inline-block;
            margin: 0 5px;
            padding: 8px 12px;
            color: #3498db;
            text-decoration: none;
            border: 1px solid #3498db;
            border-radius: 4px;
        }
        .pagination a:hover {
            background-color: #3498db;
            color: #fff;
        }
        .pagination .current-page {
            background-color: #3498db;
            color: #fff;
            border-color: #3498db;
            cursor: default;
        }
    </style>
</head>
<body>
    <h1>Table de Logs</h1>

    <div class="filters">
        <form method="GET" action="logtable.php">
            <div class="field-group">
                <label for="table_name">Table</label>
                <select name="table_name" id="table_name">
                    <option value="">Toutes</option>
                    <?php foreach ($table_names as $table): ?>
                        <option value="<?php echo htmlspecialchars($table); ?>" <?php if ($table === $table_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($table); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-group">
                <label for="operation">Opération</label>
                <select name="operation" id="operation">
                    <option value="">Toutes</option>
                    <?php foreach ($operations as $op): ?>
                        <option value="<?php echo htmlspecialchars($op); ?>" <?php if ($op === $operation_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($op); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="date-group">
                <div class="field-group">
                    <label for="date_from">Date de début</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="field-group">
                    <label for="date_to">Date de fin</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
            </div>

            <div class="field-group">
                <label for="search_term">Recherche par terme</label>
                <input type="text" id="search_term" name="search_term" value="<?php echo htmlspecialchars($term_filter); ?>" placeholder="Entrez un terme de recherche">
            </div>

            <div class="field-group">
                <button type="submit">Filtrer</button>
            </div>
        </form>
    </div>

    <div class="log-table-container">
    <?php
    if ($total_results > 0) {

        // Pagination
        if ($total_pages > 1) {
            echo '<div class="pagination">';
            
            // Lien vers la première page
            if ($page > 1) {
                echo '<a href="?'. http_build_query(array_merge($_GET, ['page' => 1])) .'">&laquo; Première</a>';
            } else {
                echo '<span>&laquo; Première</span>';
            }

            // Lien vers la page précédente
            if ($page > 1) {
                echo '<a href="?'. http_build_query(array_merge($_GET, ['page' => $page - 1])) .'">&lt; Précédente</a>';
            } else {
                echo '<span>&lt; Précédente</span>';
            }

            // Afficher les numéros de page
            $max_links = 5;
            $start = max(1, $page - floor($max_links / 2));
            $end = min($total_pages, $start + $max_links - 1);

            if ($start > 1) {
                echo '<span>...</span>';
            }

            for ($i = $start; $i <= $end; $i++) {
                if ($i == $page) {
                    echo '<span class="current-page">' . $i . '</span>';
                } else {
                    echo '<a href="?'. http_build_query(array_merge($_GET, ['page' => $i])) .'">' . $i . '</a>';
                }
            }

            if ($end < $total_pages) {
                echo '<span>...</span>';
            }

            // Lien vers la page suivante
            if ($page < $total_pages) {
                echo '<a href="?'. http_build_query(array_merge($_GET, ['page' => $page + 1])) .'">Suivante &gt;</a>';
            } else {
                echo '<span>Suivante &gt;</span>';
            }

            // Lien vers la dernière page
            if ($page < $total_pages) {
                echo '<a href="?'. http_build_query(array_merge($_GET, ['page' => $total_pages])) .'">Dernière &raquo;</a>';
            } else {
                echo '<span>Dernière &raquo;</span>';
            }

            echo '</div>';
        }

        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Table</th>
                <th>Opération</th>
                <th>Anciennes Valeurs</th>
                <th>Nouvelles Valeurs</th>
                <th>Date/Heure</th>
                <th>Utilisateur</th>
              </tr>";

        foreach ($logs as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['log_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['table_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['operation']) . "</td>";
            echo "<td>" . nl2br(htmlspecialchars($row['old_values'])) . "</td>";
            echo "<td>" . nl2br(htmlspecialchars($row['new_values'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['changed_at']) . "</td>";
            echo "<td>" . htmlspecialchars($row['changed_by']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='no-result'>Aucun log disponible.</p>";
    }

    // Fermeture de la connexion
    $pdo = null;
    ?>
    </div>

    <footer class="footer">
        <a href="logout.php" class="footer-btn btn-logout">Se déconnecter</a>
        <a href="menu.php" class="footer-btn btn-menu">Menu principal</a>
    </footer>
</body>
</html>
