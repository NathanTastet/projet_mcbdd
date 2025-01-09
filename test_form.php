<?php
// test_form.php
if (!empty($_POST)) {
    echo "<pre>"; print_r($_POST); echo "</pre>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test Form</title>
</head>
<body>
    <form method="POST" action="test_form.php">
        <input type="text" name="activity_name" placeholder="Entrez le nom de l'activité">
        <button type="submit">Rechercher</button>
    </form>
</body>
</html>
