
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats</title>
    <script src="fullcalendar/index.global.min.js"></script>
    
    <style>
        #calendar {
            max-width: 900px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .info {
            text-align: center;
            margin: 20px auto;
            font-size: 16px;
            font-weight: bold;
        }
        button {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            font-size: 16px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <fieldset>
        <legend>Résultats</legend>
        <div class="info" id="slot-info">Survolez un créneau pour voir les horaires. Cliquez pour confirmer votre choix.</div>
        <?php
        $host = 'localhost';
        $dbname = 'SAE_BDD';
        $username = 'API';
        $password = 'azerty';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Récupération des données POST
            $groupes = $_POST['groupes'] ?? [];
            $profs = $_POST['profs'] ?? [];
            $salles = $_POST['salles'] ?? [];
            $duree = intval($_POST['duree'] ?? 15); // Durée en minutes
            $ressourcesArray = array_merge($groupes, $profs, $salles);
            $ressources = implode(',', $ressourcesArray);
            $semaine = intval($_POST['semaine']);
            $annee = intval($_POST['annee']);

            // Charger et préparer le fichier SQL
            $sql_file = 'Commande_Creneaux_Libres.sql';
            if (!file_exists($sql_file)) {
                throw new Exception("Fichier SQL introuvable : $sql_file");
            }
            $sql = file_get_contents($sql_file);

            // Remplacement des variables dans le SQL
            $sql = str_replace('@ressources', "'$ressources'", $sql);
            $sql = str_replace('@semaine', $semaine, $sql);
            $sql = str_replace('@annee', $annee, $sql);

            // Exécuter la requête SQL
            $query = $pdo->query($sql);
            $resultats = $query->fetchAll(PDO::FETCH_ASSOC);

            // Préparer les créneaux pour le calendrier
            $blocks = [];
            $currentStart = null;
            $currentEnd = null;

            foreach ($resultats as $row) {
                $slotStart = intval($row['slot']);
                $slotEnd = $slotStart + ($duree / 15); // Convertir la durée en slots
                $startHour = 8 + floor(($slotStart - 4) / 4); // Convert slots to hour ranges
                $startMinutes = (($slotStart - 4) % 4) * 15;
                $endHour = 8 + floor(($slotEnd - 4) / 4);
                $endMinutes = (($slotEnd - 4) % 4) * 15;

                // Vérifier la contrainte : l'heure de fin doit être <= 19h00
                if ($startHour >= 8 && $endHour < 19 || ($endHour == 19 && $endMinutes == 0)) {
                    $start = "{$row['date']}T" . str_pad($startHour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($startMinutes, 2, '0', STR_PAD_LEFT);
                    $end = "{$row['date']}T" . str_pad($endHour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($endMinutes, 2, '0', STR_PAD_LEFT);

                    if ($currentStart === null) {
                        $currentStart = $start;
                        $currentEnd = $end;
                    } elseif ($start === $currentEnd) {
                        $currentEnd = $end;
                    } else {
                        $blocks[] = [
                            'start' => $currentStart,
                            'end' => $currentEnd,
                            'backgroundColor' => 'green',
                            'borderColor' => 'darkgreen',
                            'textColor' => 'white',
                        ];
                        $currentStart = $start;
                        $currentEnd = $end;
                    }
                }
            }

            if ($currentStart !== null) {
                $blocks[] = [
                    'start' => $currentStart,
                    'end' => $currentEnd,
                    'backgroundColor' => 'green',
                    'borderColor' => 'darkgreen',
                    'textColor' => 'white',
                ];
            }

            echo "<script>const blocks = " . json_encode($blocks) . ";</script>";
        } catch (PDOException $e) {
            echo "Erreur PDO : " . $e->getMessage();
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
        }
        ?>
        <div id="calendar"></div>
        <button onclick="window.location='index.html'">Retour au menu principal</button>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const calendarEl = document.getElementById('calendar');
                const infoEl = document.getElementById('slot-info');
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'fr', // Localisation française
                    initialView: 'timeGridWeek',
                    headerToolbar: false, // Bloque la navigation
                    slotMinTime: "08:00:00",
                    slotMaxTime: "19:00:00",
                    hiddenDays: [0, 6],
                    allDaySlot: false,
                    events: blocks,
                    eventMouseEnter: function(info) {
                        infoEl.textContent = `Créneau sélectionné : ${info.event.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })} - ${info.event.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`;
                        info.el.style.backgroundColor = 'darkgreen';
                    },
                    eventMouseLeave: function(info) {
                        infoEl.textContent = "Survolez un créneau pour voir les horaires. Cliquez pour confirmer votre choix.";
                        info.el.style.backgroundColor = 'green';
                    },
                    eventClick: function(info) {
                        alert(`Créneau confirmé : ${info.event.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })} - ${info.event.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`);
                    }
                });
                calendar.render();
            });
        </script>
    </fieldset>
</body>
</html>
