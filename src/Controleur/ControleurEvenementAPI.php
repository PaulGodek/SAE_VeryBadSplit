<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Service\DepenseService;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ControleurEvenementAPI extends ControleurGenerique
{

    /*
    public function __construct(ContainerInterface $container,
        private EvenementServiceInterface $EvenementService,
        private DepenseServiceInterface $depenseService) {
        parent::__contruct($container);
    }
    */

    private static function getEvenementService(): EvenementService
    {
        return Conteneur::recupererService("evenementService");
    }
    private static function getDepenseService(): DepenseService
    {
        return Conteneur::recupererService("depenseService");
    }

    #[Route(path: "/api/evenements", name: "CreationEvenementAPI", methods: ['POST'])]
    public static function creerEvenement(Request $request): Response
    {
        try {
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            $nomEvenement = $json->nomEvenement ?? null;
            $loginUtilisateur = ConnexionUtilisateur::getLoginUtilisateurConnecte() ?? null;
            $codeSecret = self::getEvenementService()->creerEvenement($nomEvenement, $loginUtilisateur);
            return new JsonResponse($codeSecret, Response::HTTP_CREATED);
        } catch (ServiceException $exception) {
            return new JsonResponse(["error" => $exception->getMessage()], $exception->getCode());
        } catch (JsonException $exception) {
            return new JsonResponse(
                ["error" => "Corps de la requête mal formé"],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route(path: "/api/evenements/{idEvenement}", name: "mettreAJourEvenementAPI",
        methods: ['PATCH'])]
    public static function mettreAJourEvenement(Request $request, int $idEvenement): Response
    {
        try {
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            $nomEvenement = $json->nomEvenement ?? null;
            $codeSecret = self::getEvenementService()->mettreAJourEvenement($idEvenement, $nomEvenement);
            return new JsonResponse($codeSecret, Response::HTTP_OK);
        } catch (ServiceException $exception) {
            return new JsonResponse(["error" => $exception->getMessage()], $exception->getCode());
        } catch (JsonException $exception) {
            return new JsonResponse(
                ["error" => "Corps de la requête mal formé"],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route(path: "/api/evenements/{idEvenement}", name: "SupprimerEvenementAPI",
        methods: ['DELETE'])]
    public static function supprimerEvenement(int $idEvenement): Response
    {
        try {
            self::getEvenementService()->supprimerEvenement($idEvenement);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }

        #[Route(path: "/api/evenements/{idEvenement}/membres", name: "ajouterMembreAPI",
        methods: ['POST'])]
    public static function ajouterMembre(Request $request, int $idEvenement): Response
    {
        try{
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            $loginUtilisateur = $json->login ?? null;
            $codeSecret = self::getEvenementService()->ajouterMembre($idEvenement, $loginUtilisateur);
            return new JsonResponse($codeSecret, Response::HTTP_CREATED);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        } catch (JsonException $exception) {
            return new JsonResponse(
                ["error" => "Corps de la requête mal formé"],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route(path: "/api/evenements/{idEvenement}/membres", name: "QuitterEvenementAPI", methods: ['DELETE'])]
    public static function quitterEvenement(int $idEvenement): Response
    {
        try {
            self::getEvenementService()->quitterEvenement($idEvenement);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }

    #[Route(path: "/api/evenements/{idEvenement}/membres/{login}", name: "SupprimerMembreAPI", methods: ['DELETE'])]

    public static function supprimerMembre(int $idEvenement, string $login): Response
    {
        try {
            self::getEvenementService()->supprimerMembre($idEvenement, $login);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }
}