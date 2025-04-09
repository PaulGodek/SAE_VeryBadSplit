<?php

namespace App\VeryBadSplit\Controleur;

class ControleurBase extends ControleurGenerique
{
    public static function accueil() {
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Accueil",
            "cheminVueBody" => "base/accueil.php"
        ]);
    }
}