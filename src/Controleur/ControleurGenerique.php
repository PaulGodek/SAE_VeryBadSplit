<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\Exception\ServiceException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Twig\Environment;

abstract class ControleurGenerique {

    public function __construct(private ContainerInterface $container){}

    protected function afficherVue(string $cheminVue, array $parametres = []): Response
    {
        extract($parametres);
        $messagesFlash = MessageFlash::lireTousMessages();
        ob_start();
        require __DIR__ . "/../vue/$cheminVue";
        $corpsResponse = ob_get_clean();
        return new Response($corpsResponse);
    }

    protected function afficherTwig(string $cheminVue, array $parametres = []): Response
    {
        /** @var Environment $twig */
        $twig = $this->container->get("Twig\Environment");
        $corpsReponse = $twig->render($cheminVue, $parametres);
        return new Response($corpsReponse);
    }

    protected function redirection(string $routeName, array $arguments = []) : RedirectResponse
    {
        $generateurUrl = $this->container->get("Symfony\Component\Routing\Generator\UrlGenerator");

        /** @var UrlGenerator $generateurUrl */
        $url = $generateurUrl->generate($routeName, $arguments);

        return new RedirectResponse($url);
    }

    public function afficherErreur($messageErreur = "",  $statusCode = 400): Response
    {
        $reponse = $this->afficherTwig("erreur.html.twig", [
            "statusCode" => $statusCode,
            "messageErreur" => $messageErreur
        ]);

        $reponse->setStatusCode($statusCode);
        return $reponse;
    }

    /**
     * Gère une exception de type ServiceException en ajoutant un message flash
     * et en redirigeant vers une URL spécifiée.
     *
     * @param ServiceException $e L'exception à gérer. Contient le message et l'URL de redirection.
     * @param string|null $type Le type de message flash (optionnel). Si null, utilise le type de l'exception
     */
    protected function gererException(ServiceException $e, string $type = null): RedirectResponse
    {
        MessageFlash::ajouter($type ?? $e->getTypeMessageFlash(), $e->getMessage());
        return $this->redirection($e->getRedirectionRoute(), $e->getArguments());
    }
}