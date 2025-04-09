<?php

use App\VeryBadSplit\Controleur\ControleurGenerique;
use App\VeryBadSplit\Lib\Psr4AutoloaderClass;

require_once __DIR__ . '/../src/Lib/Psr4AutoloaderClass.php';

$chargeurDeClasse = new Psr4AutoloaderClass(false);
$chargeurDeClasse->register();
$chargeurDeClasse->addNamespace('App\VeryBadSplit', __DIR__ . '/../src');

$action = $_REQUEST['action'] ?? 'accueil';

$controleur = "base";
if (isset($_REQUEST['controleur']))
    $controleur = $_REQUEST['controleur'];

$nomDeClasseControleur = 'App\VeryBadSplit\Controleur\Controleur' . ucfirst($controleur);

if (class_exists($nomDeClasseControleur)) {
    $controleur = new $nomDeClasseControleur();
    if (in_array($action, get_class_methods($nomDeClasseControleur))) {
        $nomDeClasseControleur::$action();
    } else {
        ControleurGenerique::afficherErreur("Action inexistante");
    }
} else {
    ControleurGenerique::afficherErreur("Contrôleur inconnu");
}