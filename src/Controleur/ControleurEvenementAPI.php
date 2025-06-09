<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Service\DepenseService;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\EvenementServiceInterface;
use JsonException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ControleurEvenementAPI extends ControleurGenerique
{

    public function __construct(ContainerInterface $container,
        private EvenementServiceInterface $evenementService) {
        parent::__construct($container);
    }

    #[Route(path: "/api/evenements", name: "CreationEvenementAPI", methods: ['POST'])]
    public function creerEvenement(Request $request): Response
    {
        try {
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            $nomEvenement = $json->nomEvenement ?? null;
            $loginUtilisateur = ConnexionUtilisateur::getLoginUtilisateurConnecte() ?? null;
            $codeSecret = $this->evenementService->creerEvenement($nomEvenement, $loginUtilisateur);
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
    public function mettreAJourEvenement(Request $request, int $idEvenement): Response
    {
        try {
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            $nomEvenement = $json->nomEvenement ?? null;
            $codeSecret = $this->evenementService->mettreAJourEvenement($idEvenement, $nomEvenement);
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
    public function supprimerEvenement(int $idEvenement): Response
    {
        try {
            $this->evenementService->supprimerEvenement($idEvenement);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }

        #[Route(path: "/api/evenements/{idEvenement}/membres", name: "ajouterMembreAPI",
        methods: ['POST'])]
    public function ajouterMembre(Request $request, int $idEvenement): Response
    {
        try{
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            $loginUtilisateur = $json->login ?? null;
            $codeSecret = $this->evenementService->ajouterMembre($idEvenement, $loginUtilisateur);
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
    public function quitterEvenement(int $idEvenement): Response
    {
        try {
            $this->evenementService->quitterEvenement($idEvenement);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }

    #[Route(path: "/api/evenements/{idEvenement}/membres/{login}", name: "SupprimerMembreAPI", methods: ['DELETE'])]

    public function supprimerMembre(int $idEvenement, string $login): Response
    {
        try {
            $this->evenementService->supprimerMembre($idEvenement, $login);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }
}