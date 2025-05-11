<?php

namespace App\VeryBadSplit\Controleur;

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

        // Exécution du contrôleur
        call_user_func_array($controleur, $arguments);
    }
}
