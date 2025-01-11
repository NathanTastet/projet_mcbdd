<?php
session_start();
//erreurs
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/


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
    <script src="libs/index.global.min.js"></script>
    
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
            
            // affichage des données POST
            //print_r($_POST);

            // Récupération des données POST

            $name = $_POST['name'];
            $ressources = $_POST['ressources'] ?? null;
            $duree = intval($_POST['duree']); // Durée en minutes
            $date = $_POST['date'];
            $id = intval($_POST['id']) ?? 0; //0 = nouvelle activité
            $id_ressources = $_POST['id_ressources'] ?? null;

            
            // Si on a pas d'IDADE pour les ressources (ressources entrées lors de la création de l'activité)
            // on va chercher les IDADE correspondants aux noms des ressources

            if ($id_ressources == null && $ressources) {
                // Séparer les noms des ressources
                $resource_names = explode(',', $ressources);
            
                // Initialiser un tableau pour les IDs des ressources
                $id_ressources_array = [];
            
                // Préparer la requête pour récupérer un idADE pour chaque ressource
                $sql = "SELECT idADE FROM ressources WHERE name = ?";
                $stmt = $pdo->prepare($sql);
            
                // Parcourir chaque ressource
                foreach ($resource_names as $resource_name) {
                    // Exécuter la requête pour cette ressource
                    $stmt->execute([$resource_name]);
                    $idADE = $stmt->fetchColumn();
            
                    // Si un ID est trouvé, l'ajouter au tableau, sinon ajouter 0
                    if ($idADE !== false) {
                        $id_ressources_array[] = $idADE;
                    } else {
                        $id_ressources_array[] = 0; // Aucun ID trouvé, ajouter 0
                    }
                }
            
                // Convertir le tableau d'IDs en une chaîne séparée par des virgules
                $id_ressources = implode(',', $id_ressources_array);
            }

            // Afficher le résultat (pour débogage)
            /*if ($id_ressources) {
                echo "ID des ressources trouvées : $id_ressources";
            } else {
                echo "Erreur ! Aucune ressource trouvée ou aucune ressource fournie.";
            }*/



            // Charger et préparer le fichier SQL de sélection des créneaux libres
            $sql_file = 'Commande_Creneaux_Libres.sql';
            if (!file_exists($sql_file)) {
                throw new Exception("Fichier SQL introuvable : $sql_file");
            }
            $sql = file_get_contents($sql_file);
            
            
            // Remplacement des variables dans le SQL
            $sql = str_replace('@ressources', "'$ressources'", $sql);
            $sql = str_replace('@selected_date', "'$date'", $sql);


            // Exécuter la requête SQL
            $query = $pdo->query($sql);
            $resultats = $query->fetchAll(PDO::FETCH_ASSOC);


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
        <footer>
            <button onclick="window.location='index.php'">Retour au menu principal</button>
            <button onclick="window.history.back()">Retour à la page précédente</button>
        </footer>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const calendarEl = document.getElementById('calendar');
                const infoEl = document.getElementById('slot-info');
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'fr', // Localisation française
                    initialView: 'timeGridWeek',
                    headerToolbar: false, // Bloque la navigation
                    initialDate: '<?php echo $date; ?>',
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
                            activityId: "<?php echo $id; ?>",
                            name: "<?php echo $name; ?>",
                            date: info.event.startStr.split('T')[0],
                            startHour: info.event.startStr.split('T')[1],
                            endHour: info.event.endStr.split('T')[1],
                            id_ressources: "<?php echo $id_ressources; ?>"
                        };


                        console.log('Données envoyées :', JSON.stringify(activityData));
                        

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
                                window.location.href = 'menu.php'; // Redirection après l'alerte
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