<?php
// On appelle ton script de filtrage
include('api_calendrier.php'); 
// api_calendrier.php contient déjà le code qui fait l'extraction JSON

// On récupère le résultat généré par api_calendrier.php
$jsonData = ob_get_contents(); 

// On l'enregistre dans un fichier statique
file_put_contents('data.json', $jsonData);

echo "Base de données mise à jour avec succès.";
?>