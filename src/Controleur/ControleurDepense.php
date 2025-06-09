<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\DepenseServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ControleurDepense extends ControleurGenerique
{

    public function __construct(ContainerInterface $container, private DepenseServiceInterface $depenseService, private EvenementService $evenementService)
    {
        parent::__construct($container);
    }

    #[Route(path: "/evenements/nouvelleDepense/{idEvenement}", name: "afficherFormulaireCreationDepense",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public function afficherFormulaireCreationDepense(int $idEvenement): Response
    {
        try {
            $evenement = $this->evenementService->verifierAccesEvenement($idEvenement);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }

        return $this->afficherTwig("depense/formulaireCreationDepense.html.twig", ["evenement" => $evenement]);
    }

    #[Route(path: "/evenements/nouvelleDepense/{idEvenement}", name: "creerDepense",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public function creerDepense(int $idEvenement): Response {
        $titre = $_REQUEST["titre"] ?? null;
        $montant = $_REQUEST["montant"] ? floatval($_REQUEST["montant"]) : null;
        $payeur = $_REQUEST["payeur"] ?? null;
        $participants = $_REQUEST["participants"] ?? null;

        if(is_null($participants)){
            $participants=[];
        }
        try {
            $codeSecret = $this->depenseService->creerDepense($idEvenement, $titre, $montant, $payeur, $participants);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }
        
        MessageFlash::ajouter("success", "Dépense créée avec succès");
        return $this->redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }

    #[Route(path: "/depense/modifier/{idDepense}", name: "afficherFormulaireMiseAJourDepense",
        requirements: ['idDepense' => '\d+'], methods: ['GET'])]
    public function afficherFormulaireMiseAJourDepense(int $idDepense): Response {
        try {
            $depense = $this->depenseService->verifierAccesDepense($idDepense);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }
        
        return $this->afficherTwig("depense/formulaireMiseAJourDepense.html.twig", [
            "depense" => $depense
        ]);
    }

    #[Route(path: "/depense/modifier/{idDepense}", name: "mettreAJourDepense", 
        requirements: ['idDepense' => '\d+'], methods: ['POST'])]
    public function mettreAJourDepense(int $idDepense): Response {
        $titre = $_REQUEST["titre"] ?? null;
        $montant = $_REQUEST["montant"] ? floatval($_REQUEST["montant"]) : null;
        $payeur = $_REQUEST["payeur"] ?? null;
        $participants = $_REQUEST["participants"] ?? null;

        if(is_null($participants)){
            $participants=[];
        }

        try {
            $codeSecret = $this->depenseService->mettreAJourDepense($idDepense, $titre, $montant, $payeur, $participants);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Dépense mise à jour avec succès.");
        return $this->redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }

    #[Route(path: "/depense/supprimer/{idDepense}", name: "supprimerDepense",
        requirements: ['idDepense' => '\d+'], methods: ['GET'])]
    public function supprimerDepense(int $idDepense): Response
    {
        try {
            $codeSecret = $this->depenseService->supprimerDepense($idDepense);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }
        MessageFlash::ajouter("success", "Dépense supprimée avec succès.");
        return $this->redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }

    #[Route(path: "/depense/supprimerParticipant/{idDepense}/{login}", name: "SupprimerParticipant")]
    public function supprimerParticipant(int $idDepense, string $login): Response
    {
        try {
            $this->depenseService->supprimerParticipant($idDepense, $login);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Participant supprimé avec succès.");
        return $this->redirection("afficherFormulaireMiseAJourDepense", ["idDepense" => $idDepense]);
    }

    #[Route(path: "/depense/ajouterParticipant/{idDepense}/{login}", name: "AjouterParticipant")]
    public function ajouterParticipant(int $idDepense, string $login): Response
    {
        try {
            $this->depenseService->ajouterParticipant($idDepense, $login);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Participant ajouté avec succès.");
        return $this->redirection("afficherFormulaireMiseAJourDepense", ["idDepense" => $idDepense]);
    }
}