<?php

namespace App\VeryBadSplit\Controleur;

use Symfony\Component\Routing\Attribute\Route;

class ControleurBase extends ControleurGenerique
{
    
    #[Route(path: '/', name: 'accueil', methods: ['GET'])]
    public static function accueil(): void
    {
        /*self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Accueil",
            "cheminVueBody" => "base/accueil.php"
        ]);*/
        self::afficherTwig('base/accueil.html.twig');
    }
}