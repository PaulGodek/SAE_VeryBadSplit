<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\Exception\ServiceException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Twig\Environment;

abstract class ControleurGenerique {

    protected static function afficherVue(string $cheminVue, array $parametres = []): Response
    {
        extract($parametres);
        $messagesFlash = MessageFlash::lireTousMessages();
        ob_start();
        require __DIR__ . "/../vue/$cheminVue";
        $corpsResponse = ob_get_clean();
        return new Response($corpsResponse);
    }

    protected static function afficherTwig(string $cheminVue, array $parametres = []): Response
    {
        /** @var Environment $twig */
        $twig = Conteneur::recupererService("twig");
        $corpsReponse = $twig->render($cheminVue, $parametres);
        return new Response($corpsReponse);
    }

    protected static function redirection(string $routeName, array $arguments = []) : RedirectResponse
    {
        $generateurUrl = Conteneur::recupererService("generateurUrl");

        /** @var UrlGenerator $generateurUrl */
        $url = $generateurUrl->generate($routeName, $arguments);

        return new RedirectResponse($url);
    }

    public static function afficherErreur($messageErreur = "",  $statusCode = 400): Response
    {
        $reponse = self::afficherTwig("erreur.html.twig", [
            "statusCode" => $statusCode,
            "messageErreur" => $messageErreur
        ]);

        $reponse->setStatusCode($statusCode);
        return $reponse;
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
     * @param string|null $type Le type de message flash (optionnel). Si null, utilise le type de l'exception
     */
    protected static function gererException(ServiceException $e, string $type = null): RedirectResponse
    {
        MessageFlash::ajouter($type ?? $e->getTypeMessageFlash(), $e->getMessage());
        return self::redirection($e->getRedirectionRoute(), $e->getArguments());
    }
}