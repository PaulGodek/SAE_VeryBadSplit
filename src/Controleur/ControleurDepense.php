<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\DepenseServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Attribute\Route;

class ControleurDepense extends ControleurGenerique
{

    public function __construct(ContainerInterface $container, private DepenseServiceInterface $depenseService, private EvenementService $evenementService)
    {
        parent::__construct($container);
    }

    #[Route(path: "/evenements/nouvelleDepense/{idEvenement}", name: "afficherFormulaireCreationDepense",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public function afficherFormulaireCreationDepense(int $idEvenement): void
    {
        try {
            $evenement = $this->evenementService->verifierAccesEvenement($idEvenement);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }

        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'une dépense",
            "cheminVueBody" => "depense/formulaireCreationDepense.php",
            "evenement" => $evenement,
        ]);
    }

    #[Route(path: "/evenements/nouvelleDepense/{idEvenement}", name: "creerDepense",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public function creerDepense(int $idEvenement): void {
        $titre = $_REQUEST["titre"] ?? null;
        $montant = $_REQUEST["montant"] ?? null;
        $payeur = $_REQUEST["payeur"] ?? null;
        $participants = $_REQUEST["participants"] ?? null;
        
        try {
            $codeSecret = $this->depenseService->creerDepense($idEvenement, $titre, $montant, $payeur, $participants);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }
        
        MessageFlash::ajouter("success", "Dépense créée avec succès");
        $this->redirection("evenements/$codeSecret");
    }

    #[Route(path: "/depense/modifier/{idDepense}", name: "afficherFormulaireMiseAJourDepense",
        requirements: ['idDepense' => '\d+'], methods: ['GET'])]
    public function afficherFormulaireMiseAJourDepense(int $idDepense): void {
        try {
            $depense = $this->depenseService->verifierAccesDepense($idDepense);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }

        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Edition d'une dépense",
            "cheminVueBody" => "depense/formulaireMiseAJourDepense.php",
            "depense" => $depense
        ]);
    }

    #[Route(path: "/depense/modifier/{idDepense}", name: "mettreAJourDepense", 
        requirements: ['idDepense' => '\d+'], methods: ['POST'])]
    public function mettreAJourDepense(int $idDepense): void {
        $titre = $_REQUEST["titre"] ?? null;
        $montant = $_REQUEST["montant"] ?? null;
        $payeur = $_REQUEST["payeur"] ?? null;
        $participants = $_REQUEST["participants"] ?? null;

        try {
            $codeSecret = $this->depenseService->mettreAJourDepense($idDepense, $titre, $montant, $payeur, $participants);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Dépense mise à jour avec succès.");
        $this->redirection("evenements/$codeSecret");
    }

    #[Route(path: "/depense/supprimer/{idDepense}", name: "supprimerDepense",
        requirements: ['idDepense' => '\d+'], methods: ['GET'])]
    public function supprimerDepense(int $idDepense): void
    {
        try {
            $codeSecret = $this->depenseService->supprimerDepense($idDepense);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }
        MessageFlash::ajouter("success", "Dépense supprimée avec succès.");
        $this->redirection("evenements/$codeSecret");
    }
}