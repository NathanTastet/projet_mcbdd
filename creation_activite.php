
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration de l'EDT</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .field-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="number"], select {
            width: calc(100% - 20px);
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background: #3498db;
            color: #fff;
            padding: 10px 15px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #2980b9;
        }
        .add-btn {
            background: #2ecc71;
        }
        .add-btn:hover {
            background: #27ae60;
        }
        .remove-btn {
            background: #e74c3c;
        }
        .remove-btn:hover {
            background: #c0392b;
        }
    </style>
    <script>
        function addField(containerId) {
            const container = document.getElementById(containerId);
            const fields = container.querySelectorAll("input[type='text']");
            if (fields.length < 2) {
                const input = document.createElement("input");
                input.type = "text";
                input.name = containerId + "[]";
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
    </script>
</head>
<body>
    <h1>Ajout d'une activité</h1>
    <form action="selection_creneaux.php" method="POST">
        <div class="field-group">
            <label for="name">Nom de l'activité :</label>
            <input type="text" id="name" name="name" placeholder="Entrez le nom de l'activité" required>
        </div>

        <div class="field-group" id="groupes-container">
            <label>Groupes :</label>
            <input type="text" name="groupes[]" placeholder="Entrez un groupe" required>
            <button type="button" class="add-btn" onclick="addField('groupes-container')">+</button>
        </div>

        <div class="field-group" id="profs-container">
            <label>Professeurs :</label>
            <input type="text" name="profs[]" placeholder="Entrez un professeur" required>
            <button type="button" class="add-btn" onclick="addField('profs-container')">+</button>
        </div>

        <div class="field-group" id="salles-container">
            <label>Salles :</label>
            <input type="text" name="salles[]" placeholder="Entrez une salle" required>
            <button type="button" class="add-btn" onclick="addField('salles-container')">+</button>
        </div>

        <div class="field-group">
            <label>Durée :</label>
            <select name="duree" required>
                <option value="" disabled selected>Choisissez une durée</option>
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
            <label>Semaine :</label>
            <select id="semaine" name="semaine" required>
                <option value="" disabled selected>Choisissez une semaine</option>
            </select>
        </div>

        <div class="field-group">
            <label>Année :</label>
            <input type="number" name="annee" value="2025" required>
        </div>

        <button type="submit">Envoyer</button>

        <script>
            const semaineMenu = document.getElementById('semaine');
            for (let i = 1; i <= 52; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `Semaine ${i}`;
                semaineMenu.appendChild(option);
            }
        </script>
    </form>
</body>
</html>
