<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MessageFlash;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use App\VeryBadSplit\Lib\AttributeRouteControllerLoader;
use App\VeryBadSplit\Lib\Conteneur;
use Twig\TwigFunction;

class RouteurURL
{
    public static function traiterRequete(Request $requete): Response
    {
        // Contexte de la requête
        $contexteRequete = (new RequestContext())->fromRequest($requete);

        $conteneur = new ContainerBuilder();
//On indique au FileLocator de chercher à partir du dossier de configuration
        $loader = new YamlFileLoader($conteneur, new FileLocator(__DIR__."/../Configuration"));
//On remplit le conteneur avec les données fournies dans le fichier de configuration
        $loader->load("conteneur.yml");
        $conteneur->setParameter('project_root', __DIR__.'/../..');

        // Chargement des routes
        $fileLocator = new FileLocator(__DIR__);
        $attrClassLoader = new AttributeRouteControllerLoader();
        $routes = (new AttributeDirectoryLoader($fileLocator, $attrClassLoader))->load(__DIR__);

        // Ajout des services au conteneur
        $generateurUrl = new UrlGenerator($routes, $contexteRequete);
        $assistantUrl = new UrlHelper(new RequestStack(), $contexteRequete);

        $conteneur->set(UrlGenerator::class, $generateurUrl);

        Conteneur::ajouterService("generateurUrl", $generateurUrl);
        Conteneur::ajouterService("assistantUrl", $assistantUrl);

        $twig=$conteneur->get('Twig\Environment');
        $twig->addGlobal('messagesFlash', new MessageFlash());
        $twig->addGlobal("estConnecte", ConnexionUtilisateur::estConnecte() ? ConnexionUtilisateur::getLoginUtilisateurConnecte() : null);
        $twig->addFunction(new TwigFunction('route', $generateurUrl->generate(...)));
        $twig->addFunction(new TwigFunction('asset', $assistantUrl->getAbsoluteUrl(...)));

        // Exécution du contrôleur
        try {
            // Association de l'URL à une route
            $associateurUrl = new UrlMatcher($routes, $contexteRequete);
            $donneesRoute = $associateurUrl->match($requete->getPathInfo());
            $requete->attributes->add($donneesRoute);

            // Résolution du contrôleur et des arguments
            $resolveurDeControleur = new ContainerControllerResolver($conteneur);
            $controleur = $resolveurDeControleur->getController($requete);

            $resolveurDArguments = new ArgumentResolver();
            $arguments = $resolveurDArguments->getArguments($requete, $controleur);

            $reponse = call_user_func_array($controleur, $arguments);
        } catch (MethodNotAllowedException $exception) {
            // Remplacez xxx par le bon code d'erreur
            $reponse = $conteneur->get("App\VeryBadSplit\Controleur\ControleurBase")->afficherErreur($exception->getMessage(), 405);
        } catch (ResourceNotFoundException $exception) {
            // Remplacez xxx par le bon code d'erreur
            $reponse = $conteneur->get("App\VeryBadSplit\Controleur\ControleurBase")->afficherErreur($exception->getMessage(), 404);
        } catch (\Exception $exception) {
            $reponse = $conteneur->get("App\VeryBadSplit\Controleur\ControleurBase")->afficherErreur($exception->getMessage());
        }

        return $reponse;
    }
}