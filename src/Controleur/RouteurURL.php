<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\MessageFlash;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use App\VeryBadSplit\Lib\AttributeRouteControllerLoader;
use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Modele\Repository\UtilisateurRepository;
use App\VeryBadSplit\Modele\Repository\EvenementRepository;
use App\VeryBadSplit\Modele\Repository\DepenseRepository;
use App\VeryBadSplit\Service\UtilisateurService;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\DepenseService;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class RouteurURL
{
    public static function traiterRequete(): void
    {
        $requete = Request::createFromGlobals();

        // Chargement des routes
        $fileLocator = new FileLocator(__DIR__);
        $attrClassLoader = new AttributeRouteControllerLoader();
        $routes = (new AttributeDirectoryLoader($fileLocator, $attrClassLoader))->load(__DIR__);
        
        // Contexte de la requête
        $contexteRequete = (new RequestContext())->fromRequest($requete);

        // Association de l'URL à une route
        $associateurUrl = new UrlMatcher($routes, $contexteRequete);
        $donneesRoute = $associateurUrl->match($requete->getPathInfo());
        $requete->attributes->add($donneesRoute);

        // Résolution du contrôleur et des arguments
        $resolveurDeControleur = new ControllerResolver();
        $controleur = $resolveurDeControleur->getController($requete);

        $resolveurDArguments = new ArgumentResolver();
        $arguments = $resolveurDArguments->getArguments($requete, $controleur);
        
        // Ajout des services au conteneur
        $generateurUrl = new UrlGenerator($routes, $contexteRequete);
        $assistantUrl = new UrlHelper(new RequestStack(), $contexteRequete);

        Conteneur::ajouterService("generateurUrl", $generateurUrl);
        Conteneur::ajouterService("assistantUrl", $assistantUrl);
        
        // Initialisation des repositories
        $utilisateurRepository = new UtilisateurRepository();
        $evenementRepository = new EvenementRepository();
        $depenseRepository = new DepenseRepository();
        
        // Ajout des repositories au conteneur
        Conteneur::ajouterService("utilisateurRepository", $utilisateurRepository);
        Conteneur::ajouterService("evenementRepository", $evenementRepository);
        Conteneur::ajouterService("depenseRepository", $depenseRepository);
        
        // Initialisation des services avec injection de dépendances
        $utilisateurService = new UtilisateurService(
            $utilisateurRepository,
            $evenementRepository,
            $depenseRepository
        );
        
        $evenementService = new EvenementService(
            $evenementRepository,
            $utilisateurRepository,
            $depenseRepository
        );
        
        $depenseService = new DepenseService(
            $depenseRepository,
            $utilisateurRepository,
            $evenementService
        );
        
        // Ajout des services au conteneur
        Conteneur::ajouterService("utilisateurService", $utilisateurService);
        Conteneur::ajouterService("evenementService", $evenementService);
        Conteneur::ajouterService("depenseService", $depenseService);

        // Ajout du moteur Twig
        $twigLoader = new FilesystemLoader(__DIR__ . '/../vue/');
        $twig = new Environment(
            $twigLoader,
            [
                'autoescape' => 'html',
                'strict_variables' => true,
                'debug' => true
            ]
        );
        Conteneur::ajouterService("twig", $twig);

        // Ajout des variables globales et des méthodes Twig
        $twig->addGlobal('messagesFlash', new MessageFlash());
        $twig->addFunction(new TwigFunction('route', [$generateurUrl, 'generate']));
        $twig->addFunction(new TwigFunction('asset', [$assistantUrl, 'getAbsoluteUrl']));

        // Exécution du contrôleur
        call_user_func_array($controleur, $arguments);
    }
}
