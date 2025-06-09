<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Service\DepenseService;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ControleurDepenseAPI extends ControleurGenerique{

    /*
    public function __construct(ContainerInterface $container,
        private DepenseServiceInterface $depenseService,
        private EvenementServiceInterface $EvenementService) {
        parent::__contruct($container);
    }
    */

    private static function getDepenseService(): DepenseService
    {
        return Conteneur::recupererService("depenseService");
    }

    private static function getEvenementService(): EvenementService
    {
        return Conteneur::recupererService("evenementService");
    }

    #[Route(path: "/api/depenses/{idDepense}", name: "supprimerDepenseAPI", methods: ['DELETE'])]
    public static function supprimerDepense(int $idDepense): Response {
        try {
            self::getDepenseService()->supprimerDepense($idDepense);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }

    #[Route(path: "/api/depenses/{idDepense}/participants/{login}", name: "supprimerParticipantDepenseAPI", methods: ['DELETE'])]
    public static function supprimerParticipant(int $idDepense, string $login): Response {
        try {
            self::getDepenseService()->supprimerParticipant($idDepense, $login);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }

    #[Route(path: "/api/depenses/{idDepense}/participants/{login}", name: "ajouterParticipantAPI", methods: ['POST'])]
    public static function ajouterParticipant(int $idDepense, string $login): Response
    {
        try {
            $codeSecret = self::getDepenseService()->ajouterParticipant($idDepense, $login);
            return new JsonResponse($codeSecret, Response::HTTP_CREATED);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }

    }

    #[Route(path: "/api/evenements/{idEvenement}/depenses", name: "creerDepenseAPI",
        methods: ['POST'])]
    public static function creerDepense(Request $request, int $idEvenement): Response {
        try {
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            $titre = $json->titre ?? null;
            $montant = $json->montant ?? null;
            $payeur = $json->payeur ?? null;
            $participants = $json->participants ?? null;
            if(is_null($participants)){
                $participants=[];
            }
            $codeSecret = self::getDepenseService()->creerDepense($idEvenement, $titre, $montant, $payeur, $participants);
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

    #[Route(path: "/api/depenses/{idDepense}", name: "mettreAJourDepenseAPI",
        methods: ['PATCH'])]
    public static function mettreAJourDepense(Request $request, int $idDepense): Response {
        try {
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            $titre = $json->titre ?? null;
            $montant = $json->montant ?? null;
            $payeur = $json->payeur ?? null;
            $participants = $json->participants ?? null;

            if(is_null($participants)){
                $participants=[];
            }
            $codeSecret = self::getDepenseService()->mettreAJourDepense($idDepense, $titre, $montant, $payeur, $participants);
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

}