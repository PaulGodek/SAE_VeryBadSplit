<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\DepenseServiceInterface;
use App\VeryBadSplit\Service\Interface\EvenementServiceInterface;
use JsonException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ControleurDepenseAPI extends ControleurGenerique{

    public function __construct(ContainerInterface $container,
        private DepenseServiceInterface $depenseService,) {
        parent::__construct($container);
    }

    #[Route(path: "/api/depenses/{idDepense}", name: "supprimerDepenseAPI", methods: ['DELETE'])]
    public function supprimerDepense(int $idDepense): Response {
        try {
            $this->depenseService->supprimerDepense($idDepense);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }

    #[Route(path: "/api/depenses/{idDepense}/participants/{login}", name: "supprimerParticipantDepenseAPI", methods: ['DELETE'])]
    public function supprimerParticipant(int $idDepense, string $login): Response {
        try {
            $this->depenseService->supprimerParticipant($idDepense, $login);
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }
    }

    #[Route(path: "/api/depenses/{idDepense}/participants/{login}", name: "ajouterParticipantAPI", methods: ['POST'])]
    public function ajouterParticipant(int $idDepense, string $login): Response
    {
        try {
            $codeSecret = $this->depenseService->ajouterParticipant($idDepense, $login);
            return new JsonResponse($codeSecret, Response::HTTP_CREATED);
        } catch (ServiceException $e) {
            return new JsonResponse(["error" => $e->getMessage()], $e->getCode());
        }

    }

    #[Route(path: "/api/evenements/{idEvenement}/depenses", name: "creerDepenseAPI",
        methods: ['POST'])]
    public function creerDepense(Request $request, int $idEvenement): Response {
        try {
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            $titre = $json->titre ?? null;
            $montant = $json->montant ? floatval($json->montant) : null;
            $payeur = $json->payeur ?? null;
            $participants = $json->participants ?? null;
            if(is_null($participants)){
                $participants=[];
            }
            $codeSecret = $this->depenseService->creerDepense($idEvenement, $titre, $montant, $payeur, $participants);
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
    public function mettreAJourDepense(Request $request, int $idDepense): Response {
        try {
            $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
            
            // Récupérer la dépense existante pour obtenir les valeurs actuelles
            $depenseExistante = $this->depenseService->verifierAccesDepense($idDepense);
            
            // Utiliser les valeurs fournies ou garder les valeurs existantes
            $titre = $json->titre ?? $depenseExistante->getTitre();
            $montant = isset($json->montant) ? floatval($json->montant) : $depenseExistante->getMontant();
            $payeur = $json->payeur ?? $depenseExistante->getPayeur()->getLogin();
            
            // Si les participants sont fournis, les utiliser, sinon garder les existants
            if (isset($json->participants)) {
                $participants = $json->participants;
            } else {
                // getParticipants retourne un tableau associatif indexé par login
                $participantsExistants = $depenseExistante->getParticipants();
                $participants = $participantsExistants ? array_keys($participantsExistants) : [];
            }

            if(is_null($participants)){
                $participants=[];
            }
            
            $codeSecret = $this->depenseService->mettreAJourDepense($idDepense, $titre, $montant, $payeur, $participants);
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