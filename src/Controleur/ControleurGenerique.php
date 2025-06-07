<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\Exception\ServiceException;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\Routing\Generator\UrlGenerator;

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
    
    #[NoReturn]
    protected static function redirectionVersRoute(string $nomRoute, array $parametres = []) : void
    {
        /** @var UrlGenerator $generateurUrl */
        $generateurUrl = Conteneur::recupererService("generateurUrl");
        $url = $generateurUrl->generate($nomRoute, $parametres);
        header("Location: " . $url);
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

    public static function isNotNull(array $array) : bool {
        foreach ($array as $value) {
            if(!(isset($value) && $value != null)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gère une exception de type ServiceException en ajoutant un message flash
     * et en redirigeant vers une URL spécifiée.
     *
     * @param ServiceException $e L'exception à gérer. Contient le message et l'URL de redirection.
     * @param string|null $type Le type de message flash (optionnel). Si null, utilise le type de l'exception.
     *
     * @return void
     */
    #[NoReturn]
    protected static function gererException(ServiceException $e, string $type = null): void
    {
        MessageFlash::ajouter($type ?? $e->getTypeMessageFlash(), $e->getMessage());
        self::redirection($e->getRedirectionUrl());
    }
}