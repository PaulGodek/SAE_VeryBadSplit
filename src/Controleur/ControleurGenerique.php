<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\Exception\ServiceException;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

abstract class ControleurGenerique {

    protected static function afficherVue(string $cheminVue, array $parametres = []): void
    {
        extract($parametres);
        $messagesFlash = MessageFlash::lireTousMessages();
        require __DIR__ . "/../vue/$cheminVue";
    }

    protected static function afficherTwig(string $cheminVue, array $parametres = []): Response
    {
        /** @var Environment $twig */
        $twig = Conteneur::recupererService("twig");
        $corpsReponse = $twig->render($cheminVue, $parametres);
        return new Response($corpsReponse);
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