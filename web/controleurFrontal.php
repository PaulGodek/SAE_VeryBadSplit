<?php

////////////////////
// Initialisation //
////////////////////
require_once __DIR__ . "/../vendor/autoload.php";

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

/////////////
// Routage //
/////////////

$requete = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$reponse = \App\VeryBadSplit\Controleur\RouteurURL::traiterRequete($requete);
$reponse->send();