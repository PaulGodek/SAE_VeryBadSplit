<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\DepenseService;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use Symfony\Component\Routing\Attribute\Route;

class ControleurDepense extends ControleurGenerique
{
    private static function getDepenseService(): DepenseService
    {
        return Conteneur::recupererService("depenseService");
    }
    
    private static function getEvenementService(): EvenementService
    {
        return Conteneur::recupererService("evenementService");
    }

    #[Route(path: "/evenements/nouvelleDepense/{idEvenement}", name: "afficherFormulaireCreationDepense",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireCreationDepense(int $idEvenement): void
    {
        try {
            $evenement = self::getEvenementService()->verifierAccesEvenement($idEvenement);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'une dépense",
            "cheminVueBody" => "depense/formulaireCreationDepense.php",
            "evenement" => $evenement,
        ]);
    }

    #[Route(path: "/evenements/nouvelleDepense/{idEvenement}", name: "creerDepense",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public static function creerDepense(int $idEvenement): void {
        $titre = $_REQUEST["titre"] ?? null;
        $montant = $_REQUEST["montant"] ?? null;
        $payeur = $_REQUEST["payeur"] ?? null;
        $participants = $_REQUEST["participants"] ?? null;
        
        try {
            $codeSecret = self::getDepenseService()->creerDepense($idEvenement, $titre, $montant, $payeur, $participants);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }
        
        MessageFlash::ajouter("success", "Dépense créée avec succès");
        self::redirection("evenements/$codeSecret");
    }

    #[Route(path: "/depense/modifier/{idDepense}", name: "afficherFormulaireMiseAJourDepense",
        requirements: ['idDepense' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireMiseAJourDepense(int $idDepense): void {
        try {
            $depense = self::getDepenseService()->verifierAccesDepense($idDepense);
            $evenement = self::getEvenementService()->verifierAccesEvenement($depense->getEvenement()->getId());

        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }
        
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Edition d'une dépense",
            "cheminVueBody" => "depense/formulaireMiseAJourDepense.php",
            "evenement" => $evenement,
            "depense" => $depense
        ]);
    }

    #[Route(path: "/depense/modifier/{idDepense}", name: "mettreAJourDepense", 
        requirements: ['idDepense' => '\d+'], methods: ['POST'])]
    public static function mettreAJourDepense(int $idDepense): void {
        $titre = $_REQUEST["titre"] ?? null;
        $montant = $_REQUEST["montant"] ?? null;
        $payeur = $_REQUEST["payeur"] ?? null;
        $participants = $_REQUEST["participants"] ?? null;

        try {
            $codeSecret = self::getDepenseService()->mettreAJourDepense($idDepense, $titre, $montant, $payeur, $participants);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Dépense mise à jour avec succès.");
        self::redirection("evenements/$codeSecret");
    }

    #[Route(path: "/depense/supprimer/{idDepense}", name: "supprimerDepense",
        requirements: ['idDepense' => '\d+'], methods: ['GET'])]
    public static function supprimerDepense(int $idDepense): void
    {
        try {
            $codeSecret = self::getDepenseService()->supprimerDepense($idDepense);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }
        MessageFlash::ajouter("success", "Dépense supprimée avec succès.");
        self::redirection("evenements/$codeSecret");
    }
}