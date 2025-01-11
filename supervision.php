<?php
session_start();

//Vérifier si l'utilisateur est connecté, sinon le renvoyer sur login.php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location : index.php"); 
    exit;
}
?>

<!DOCTYPE html>
<html lang = "fr">
<head>
    <meta charset = "UTF-8">
    <title>Supervision</title>
    <style>
        body {
            font-family : "Comic Sans MS";
            background-color :rgb(242, 237, 231);
            margin : 0; 
            padding : 0; 
        }
        h1 {
            font-family : Arial, sans-serif;
            text-align : center;
        }
        table {
            border-collapse : collapse;
            width : 100%;
            margin-top : 10px;
        }
        th, td {
            border : 1px solid #ccc;
            padding : 8px; 
            text-align : center;
        }
        tr:nth-child(even){
            background-color : #f2f2f2;
        }
        tr:nth-child(odd){
            background-color :rgb(170, 242, 248);
        }
        .btn-accept {
            background-color =rgb(115, 227, 124); 
            color: #fff;
            border : 5px; 
            padding : 6px 12px;
            cursor : pointer;
        }
        .btn-accept:hover {
            background-color :rgb(69, 147, 81);
        }
        .btn-refuse {
            background-color :rgb(220, 43, 43);
            color : #fff;
            border : 30px; 
            padding : 6px 12px;
            cursor : pointer;
        }
        .btn-refuse:hover {
            background-color : rgb(142, 29, 29);
        }
    </style>
</head>
<body>
<h1> Ecran de supervision</h1>
<fieldset>
    <legend> Demande de créneaux </legend>
        <table>
            <thead>
                <tr>
                    <th scope = "col">Matière</th>
                    <th scope = "col">Professeur</th>
                    <th scope = "col">Groupe</th>
                    <th scope = "col">Salle</th>
                    <th scope = "col">Choix</th>
                </tr>
                <tr>
                    <th scope = "row">Test-Anglais</th>
                    <th scope = "row">Test-Izem</th>
                    <th scope = "row">Test-504</th>
                    <th scope = "row">Test-B18</th>
                    <th scope = "row"><button type = submit class="btn-accept">Accepter</button><button type = "submit" class = "btn-refuse">Refuser</buton></th>
                </tr>
            </thead>
        </table>
</fieldset>

    


