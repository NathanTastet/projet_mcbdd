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
    <title>Configuration de l'EDT</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script>
        function addField(containerId) {
            const container = document.getElementById(containerId);
            const fields = container.querySelectorAll("input[type='text']");
            if (fields.length < 2) {
                const input = document.createElement("input");
                input.type = "text";
                input.placeholder = "Entrez une valeur";
                input.required = true;

                const removeBtn = document.createElement("button");
                removeBtn.type = "button";
                removeBtn.textContent = "-";
                removeBtn.classList.add("remove-btn");
                removeBtn.onclick = function() {
                    container.removeChild(input);
                    container.removeChild(removeBtn);
                };

                container.appendChild(input);
                container.appendChild(removeBtn);
            }
        }

        // Fonction appelée juste avant l'envoi (submit) du formulaire
        function fusionnerRessources(event) {
            // Récupération de tous les inputs de chaque container
            const groupesInputs = document.querySelectorAll("#groupes-container input[type='text']");
            const profsInputs   = document.querySelectorAll("#profs-container input[type='text']");
            const sallesInputs  = document.querySelectorAll("#salles-container input[type='text']");

            // Transformation en tableaux simples (pour .map)
            const groupes = Array.from(groupesInputs).map(input => input.value.trim());
            const profs   = Array.from(profsInputs).map(input => input.value.trim());
            const salles  = Array.from(sallesInputs).map(input => input.value.trim());

            // Fusion en un seul tableau
            const ressourcesArray = [...groupes, ...profs, ...salles];

            // Conversion en chaîne de caractères (ex: "groupe1,groupe2,prof1,salle1")
            const ressources = ressourcesArray.join(',');

            // Injection dans le champ caché
            document.getElementById('ressources').value = ressources;
        }

        // Dès que la page est chargée, on attache notre écouteur d'événement "submit"
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('form-activite').addEventListener('submit', fusionnerRessources);
        });
    </script>
</head>
<body>
    <h1>Ajout d'une activité</h1>
    <!-- Notez l'id="form-activite" pour le script -->
    <form id="form-activite" action="selection_creneaux.php" method="POST">
        
        <div class="field-group">
            <label for="name">Nom de l'activité :</label>
            <input type="text" id="name" name="name" placeholder="Entrez le nom de l'activité" required>
        </div>

        <div class="field-group" id="groupes-container">
            <label>Groupes :</label>
            <!-- On enlève name="groupes[]" -->
            <input type="text" placeholder="Entrez un groupe" required>
            <button type="button" class="add-btn" onclick="addField('groupes-container')">+</button>
        </div>

        <div class="field-group" id="profs-container">
            <label>Professeurs :</label>
            <!-- On enlève name="profs[]" -->
            <input type="text" placeholder="Entrez un professeur" required>
            <button type="button" class="add-btn" onclick="addField('profs-container')">+</button>
        </div>
        
        <div class="field-group" id="salles-container">
            <label>Salles :</label>
            <!-- On enlève name="salles[]" -->
            <input type="text" placeholder="Entrez une salle" required>
            <button type="button" class="add-btn" onclick="addField('salles-container')">+</button>
        </div>

        <!-- Le champ caché qui contiendra la fusion -->
        <input type="hidden" id="ressources" name="ressources" />

        <div class="field-group">
            <label>Durée :</label>
            <select name="duree" required>
                <option value="15">15min</option>
                <option value="30">30min</option>
                <option value="45">45min</option>
                <option value="60">1h 0min</option>
                <option value="75">1h 15min</option>
                <option value="90">1h 30min</option>
                <option value="105">1h 45min</option>
                <option value="120">2h 0min</option>
                <option value="135">2h 15min</option>
                <option value="150">2h 30min</option>
                <option value="165">2h 45min</option>
                <option value="180">3h 0min</option>
                <option value="195">3h 15min</option>
                <option value="210">3h 30min</option>
                <option value="225">3h 45min</option>
                <option value="240">4h 0min</option>
                <option value="255">4h 15min</option>
                <option value="270">4h 30min</option>
                <option value="285">4h 45min</option>
                <option value="300">5h 0min</option>
                <option value="315">5h 15min</option>
                <option value="330">5h 30min</option>
                <option value="345">5h 45min</option>
                <option value="360">6h 0min</option>
                <option value="375">6h 15min</option>
                <option value="390">6h 30min</option>
                <option value="405">6h 45min</option>
                <option value="420">7h 0min</option>
                <option value="435">7h 15min</option>
                <option value="450">7h 30min</option>
                <option value="465">7h 45min</option>
                <option value="480">8h 0min</option>
            </select>
        </div>

        <div class="field-group">
            <label for="date">Date (jour ouvré) :</label>
            <input type="date" id="date" name="date" required>
        </div>

        <button type="submit">Envoyer</button>

        <script>
            // Limitation de l'input date aux jours ouvrés (lundi à vendredi)
            const dateInput = document.getElementById('date');

            dateInput.addEventListener('input', () => {
                const selectedDate = new Date(dateInput.value);
                const dayOfWeek = selectedDate.getUTCDay(); // 0 = dimanche, 6 = samedi

                if (dayOfWeek === 0 || dayOfWeek === 6) {
                    // Afficher une alerte si c'est un jour non ouvré
                    alert("Veuillez sélectionner un jour ouvré (lundi à vendredi).");
                    dateInput.value = ""; // Réinitialiser l'input
                }
            });
        </script>
    </form>
</body>
</html>