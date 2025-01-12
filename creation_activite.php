<?php
session_start();
// Vérifier si l'utilisateur est connecté, sinon le renvoyer sur login.php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

require 'db_connection.php';

// On récupère name, type_id depuis la table ressources
try {
    $stmt = $pdo->prepare("SELECT name, type_id FROM ressources");
    $stmt->execute();
    $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erreur lors de la récupération des ressources : " . $e->getMessage());
}

// On trie chaque nom en fonction du type_id
$resourcesGroupes = [];
$resourcesProfs   = [];
$resourcesSalles  = [];

foreach ($allRows as $row) {
    switch ($row['type_id']) {
        case 1:
            $resourcesGroupes[] = $row['name'];
            break;
        case 2:
            $resourcesProfs[]   = $row['name'];
            break;
        case 3:
            $resourcesSalles[]  = $row['name'];
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Configuration de l'EDT</title>

    <link rel="stylesheet" href="styles.css">

    <!-- Awesomplete : Autocomplétion inline -->
    <link rel="stylesheet" href="libs/awesomeplete.min.css" />
    <script src="libs/awesomeplete.min.js"></script>

    <script>
    // On prépare nos tableaux JS pour la vérification ET l'autocomplétion
    const resourcesGroupes = <?php echo json_encode($resourcesGroupes); ?>;
    const resourcesProfs   = <?php echo json_encode($resourcesProfs); ?>;
    const resourcesSalles  = <?php echo json_encode($resourcesSalles); ?>;

    /**
     * @param {HTMLInputElement} input 
     * @param {"groupes"|"profs"|"salles"} type
     */
    function initAwesomplete(input, type) {
        let list;
        if (type === "groupes") list = resourcesGroupes;
        else if (type === "profs") list = resourcesProfs;
        else if (type === "salles") list = resourcesSalles;

        // Crée l'objet Awesomplete attaché à `input`
        new Awesomplete(input, {
            list: list,
            minChars: 1,       // nb caractères avant d'afficher la liste
            autoFirst: true    // sélectionne automatiquement le 1er item
        });
    }

    /**
     * Ajoute un champ texte supplémentaire (max 2 par container).
     * @param {string} containerId - ex: "groupes-container"
     * @param {string} type        - ex: "groupes", "profs", "salles"
     */
    function addField(containerId, type) {
        const container = document.getElementById(containerId);
        const fields = container.querySelectorAll("input[type='text']");

        if (fields.length < 2) {
            // Crée un conteneur pour l'input et le bouton de suppression
            const fieldContainer = document.createElement("div");
            fieldContainer.classList.add("field-container");

            const input = document.createElement("input");
            input.type = "text";
            input.placeholder = "Entrez une 2ème valeur";
            input.required = true;

            const removeBtn = document.createElement("button");
            removeBtn.type = "button";
            removeBtn.textContent = "-";
            removeBtn.classList.add("remove-btn");
            removeBtn.onclick = function() {
                container.removeChild(fieldContainer); // Supprime tout le conteneur
            };

            // Ajoute les éléments au conteneur
            fieldContainer.appendChild(input);
            fieldContainer.appendChild(removeBtn);

            // Ajoute le conteneur au DOM
            container.appendChild(fieldContainer);

            // Initialise Awesomplete pour le nouvel input
            initAwesomplete(input, type);
        }
    }


    /**
     * Vérifie si la valeur saisie est dans le tableau correspondant au type.
     * @param {string} value
     * @param {"groupes"|"profs"|"salles"} type
     * @returns {boolean}
     */
    function isResourceValid(value, type) {
        let list;
        if (type === "groupes") list = resourcesGroupes;
        else if (type === "profs") list = resourcesProfs;
        else list = resourcesSalles;
        return list.includes(value.trim());
    }

    // Fonction appelée juste avant l'envoi (submit) du formulaire
    function fusionnerRessources(event) {
        // Récupération de tous les inputs de chaque container
        const groupesInputs = document.querySelectorAll("#groupes-container input[type='text']");
        const profsInputs   = document.querySelectorAll("#profs-container input[type='text']");
        const sallesInputs  = document.querySelectorAll("#salles-container input[type='text']");

        // Pour la vérification, on doit savoir à quel type chaque input correspond.
        // Ici, comme tous les inputs de #groupes-container sont "groupes", etc.
        // on va faire un check commun.
        
        let groupes = [];
        for (let input of groupesInputs) {
            const val = input.value.trim();
            // Vérif
            if (!isResourceValid(val, "groupes")) {
                alert("Le groupe \"" + val + "\" n'existe pas parmi les Groupes.\nVeuillez ressaisir.");
                event.preventDefault();
                return;
            }
            groupes.push(val);
        }

        let profs = [];
        for (let input of profsInputs) {
            const val = input.value.trim();
            if (!isResourceValid(val, "profs")) {
                alert("Le professeur \"" + val + "\" n'existe pas dans la base.\nVeuillez ressaisir.");
                event.preventDefault();
                return;
            }
            profs.push(val);
        }

        let salles = [];
        for (let input of sallesInputs) {
            const val = input.value.trim();
            if (!isResourceValid(val, "salles")) {
                alert("La salle \"" + val + "\" n'existe pas dans la base.\nVeuillez ressaisir.");
                event.preventDefault();
                return;
            }
            salles.push(val);
        }

        // Si tout est OK, on fusionne
        const allEntries = [...groupes, ...profs, ...salles];
        document.getElementById('ressources').value = allEntries.join(',');
    }

    // Mise en place des écoutes
    window.addEventListener('DOMContentLoaded', () => {
        // Init Awesomplete pour les champs initiaux
        initAwesomplete(document.querySelector("#groupes-container input[type='text']"), "groupes");
        initAwesomplete(document.querySelector("#profs-container  input[type='text']"), "profs");
        initAwesomplete(document.querySelector("#salles-container input[type='text']"), "salles");

        // Attache l'événement sur le formulaire
        document.getElementById('form-activite').addEventListener('submit', fusionnerRessources);

        // Limitation de l'input date aux jours ouvrés (lundi à vendredi)
        const dateInput = document.getElementById('date');
        dateInput.addEventListener('input', () => {
            const selectedDate = new Date(dateInput.value);
            const dayOfWeek = selectedDate.getUTCDay(); // 0=dimanche, 6=samedi
            if (dayOfWeek === 0 || dayOfWeek === 6) {
                alert("Veuillez sélectionner un jour ouvré (lundi à vendredi).");
                dateInput.value = ""; // Réinitialiser l'input
            }
        });
    });
    </script>
</head>
<body>

    <h1>Ajout d'une activité</h1>

    <!-- Le formulaire -->
    <form id="form-activite" action="selection_creneaux.php" method="POST">
        
        <div class="field-group">
            <label for="name">Nom de l'activité :</label>
            <input type="text" id="name" name="name" placeholder="Entrez le nom de l'activité" required>
        </div>

        <!-- Groupes (type_id = 1) -->
        <div class="field-group" id="groupes-container">
            <label>Groupes :</label>
            <input type="text" placeholder="Entrez un groupe" required>
            <button type="button" class="add-btn" onclick="addField('groupes-container','groupes')">+</button>
        </div>

        <!-- Professeurs (type_id = 2) -->
        <div class="field-group" id="profs-container">
            <label>Professeurs :</label>
            <input type="text" placeholder="Entrez un professeur" required>
            <button type="button" class="add-btn" onclick="addField('profs-container','profs')">+</button>
        </div>
        
        <!-- Salles (type_id = 3) -->
        <div class="field-group" id="salles-container">
            <label>Salles :</label>
            <input type="text" placeholder="Entrez une salle" required>
            <button type="button" class="add-btn" onclick="addField('salles-container','salles')">+</button>
        </div>

        <!-- Le champ caché qui contiendra la fusion de toutes les ressources validées -->
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
                <option value="120" selected>2h 0min</option> <!-- Valeur par défaut -->    
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
    </form>
    <footer class="footer">
    <a href="logout.php" class="footer-btn btn-logout">Se déconnecter</a>
    <a href="menu.php" class="footer-btn btn-menu">Menu principal</a>
    </footer>
</body>
</html>
