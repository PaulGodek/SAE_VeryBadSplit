<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Lib\MessageFlash;
use JetBrains\PhpStorm\NoReturn;

abstract class ControleurGenerique {

    protected static function afficherVue(string $cheminVue, array $parametres = []): void
    {
        extract($parametres);
        $messagesFlash = MessageFlash::lireTousMessages();
        require __DIR__ . "/../vue/$cheminVue";
    }
    
    #[NoReturn]
    protected static function redirection(string $url) : void
    {
        header("Location: " . Helper::url($url));
        exit();
    }

    public static function afficherErreur($messageErreur = ""): void
    {
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Problème",
            "cheminVueBody" => "erreur.php",
            "messageErreur" => $messageErreur
        ]);
    }

    public static function issetAndNotNull(array $requestParams) : bool {
        foreach ($requestParams as $param) {
            if(!(isset($_REQUEST[$param]) && $_REQUEST[$param] != null)) {
                return false;
            }
        }
        return true;
    }
}