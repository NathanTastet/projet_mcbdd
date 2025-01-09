<?php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le renvoyer sur login.php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
?>

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
            require_once 'db_connection.php'; 

        try {

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
            
            $logFile = __DIR__ . '/debug/debug_log.txt';
            file_put_contents($logFile, print_r($resultats, true));

            // $resultats est votre tableau de slots libres, exemple :
            // [ ['date' => '2025-01-06', 'slot' => 4 ], ['date' => '2025-01-06', 'slot' => 5 ], ... ]

            // ----- 1) Construire le tableau des slots libres par date -----
            $freeSlotsByDate = [];
            foreach ($resultats as $row) {
                $date = $row['date'];
                $slot = (int)$row['slot'];
                
                if (!isset($freeSlotsByDate[$date])) {
                    $freeSlotsByDate[$date] = [];
                }
                $freeSlotsByDate[$date][] = $slot;
            }

            // ----- 2) Initialiser la liste des blocks (créneaux) -----
            $blocks = [];
            $currentStart = null;
            $currentEnd = null;

            // Durée de votre créneau souhaité (en minutes) - assurez-vous que $duree est défini
            // Par exemple, si vous voulez des créneaux de 2h (120 minutes), alors:
            $duree = 120; // A adapter selon votre besoin

            // ----- 3) Parcourir les dates dans l'ordre -----
            ksort($freeSlotsByDate); // Tri par date
            foreach ($freeSlotsByDate as $date => $slots) {
                // Trier les slots pour cette date
                sort($slots);

                // ----- 3a) On va parcourir les slots disponibles pour détecter vos créneaux -----
                // Petite boucle : on essaie chaque slot comme "début" potentiel
                for ($i = 0; $i < count($slots); $i++) {
                    $slotStart = $slots[$i];
                    
                    // Nombre de slots nécessaires pour $duree (en minutes), slots de 15 minutes
                    $nbSlotsNeeded = $duree / 15;
                    // Calcul du slot de fin (non inclus)
                    $slotEnd = $slotStart + $nbSlotsNeeded;
                    
                    // Vérifier que tous les slots [slotStart .. slotEnd-1] sont libres
                    $allSlotsAvailable = true;
                    for ($s = $slotStart; $s < $slotEnd; $s++) {
                        if (!in_array($s, $slots)) {
                            $allSlotsAvailable = false;
                            break;
                        }
                    }

                    if ($allSlotsAvailable) {
                        // ----- 3b) Calcul de l'heure de début/fin -----
                        //   slot 4  -> 8h00
                        //   slot 5  -> 8h15...
                        $startHour = 8 + floor(($slotStart - 4) / 4);
                        $startMinutes = (($slotStart - 4) % 4) * 15;
                        
                        $endHour = 8 + floor(($slotEnd - 4) / 4);
                        $endMinutes = (($slotEnd - 4) % 4) * 15;
                        
                        // ----- 3c) Vérifier la contrainte horaire : 8h <= start < 19h, et end <= 19h -----
                        //   endHour < 19  ou (endHour == 19 && endMinutes == 0)
                        //   startHour >= 8
                        if (
                            $startHour >= 8 
                            && (
                                $endHour < 19 
                                || ($endHour == 19 && $endMinutes == 0)
                            )
                        ) {
                            // Construire les dates/horaires en format YYYY-MM-DDTHH:MM
                            $start = sprintf(
                                "%sT%02d:%02d", 
                                $date, 
                                $startHour, 
                                $startMinutes
                            );
                            $end = sprintf(
                                "%sT%02d:%02d", 
                                $date, 
                                $endHour, 
                                $endMinutes
                            );
                            
                            // ----- 3d) Fusion ou ajout de bloc -----
                            if ($currentStart === null) {
                                // Premier créneau
                                $currentStart = $start;
                                $currentEnd = $end;
                            } else {
                                // Vérifier si ce nouveau créneau commence pile à l'heure de fin du précédent
                                if ($start === $currentEnd) {
                                    // Alors on fusionne : on étend la fin de l'ancien créneau
                                    $currentEnd = $end;
                                } else {
                                    // Sinon, on "ferme" le créneau précédent et on en ouvre un nouveau
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
                        } // end if contrainte horaire
                    } // end if allSlotsAvailable
                } // end for slots
            } // end foreach date

            // ----- 4) Ajouter le dernier créneau si on en a un en cours -----
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
        <button onclick="window.location='index.php'">Retour au menu principal</button>
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
                        const activityData = {
                            activityId: 1, // ID fictif, remplacer dynamiquement
                            name: "Activité utilisateur", // Remplacer dynamiquement
                            date: info.event.startStr.split('T')[0],
                            startHour: info.event.startStr.split('T')[1],
                            endHour: info.event.endStr.split('T')[1],
                            resources: [1, 2] // Remplacer dynamiquement avec les ressources réelles
                        };

                        fetch('insert_temp_activity.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(activityData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Créneau ajouté à la table avec succès !');
                            } else {
                                alert('Erreur lors de l\'ajout : ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur :', error);
                            alert('Une erreur est survenue lors de la communication avec le serveur.');
                        });
                    }
                });
                calendar.render();
            });
        </script>
    </fieldset>
</body>
</html>