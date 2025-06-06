<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\DepenseService;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\UtilisateurService;
use Symfony\Component\Routing\Attribute\Route;

class ControleurEvenement extends ControleurGenerique
{
    private static function getEvenementService(): EvenementService
    {
        return Conteneur::recupererService("evenementService");
    }
    private static function getDepenseService(): DepenseService
    {
        return Conteneur::recupererService("depenseService");
    }

    private static function getUtilisateurService():UtilisateurService
    {return Conteneur::recupererService("utilisateurService");
    }
    #[Route(path: "/evenements/{codeEvenement}", name:"Evenement", requirements: ['codeEvenement' => '[a-zA-Z0-9]{64}'])]
    public static function afficherEvenement(string $codeEvenement): void
    {
        try {
            $resultat = self::getEvenementService()->recupererEvenementAvecDettes($codeEvenement);
            $evenement = $resultat["evenement"];
            $dettes = $resultat["dettes"];
            $coutTotal = $resultat["coutTotal"];
            $depenses = self::getDepenseService()->recupererDepensesParEvenement($evenement->getId());
        } catch (ServiceException $e) {
            self::gererException($e, "warning");
        }

        if(is_null($depenses)){
            $depenses=[];

        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => $evenement->getTitre(),
            "cheminVueBody" => "evenement/evenement.php",
            "evenement" => $evenement,
            "depenses" => $depenses,
            "coutTotal" => $coutTotal,
            "dettes" => $dettes,
        ]);
    }

    #[Route(path: "/evenements", name: "MesEvenements")]
    public static function afficherListeMesEvenements(): void
    {
        $login = ConnexionUtilisateur::getLoginUtilisateurConnecte() ?? null;
        try {


            $evenements = self::getEvenementService()->recupererEvenementsUtilisateur($login);
            $membres=[];
            foreach ($evenements as $evenement) {
                foreach ($evenement->getMembres() as $membre) {
                    $membres[]=self::getUtilisateurService()->recupererUtilisateurParClePrimaire($membre->getLogin());
                }
            }
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Liste des événements de $login",
            "cheminVueBody" => "evenement/listeEvenementsUtilisateur.php",
            "membres"=>$membres,
            "evenements" => $evenements
        ]);
    }

    #[Route(path: "/evenements/creation", name: "FormulaireCreationEvenement", methods: ['GET'])]
    public static function afficherFormulaireCreationEvenement(): void {
        try {
            self::getEvenementService()->verifierConnexion();
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un événement",
            "cheminVueBody" => "evenement/formulaireCreationEvenement.php",
        ]);
    }

    #[Route(path: "/evenements/creation", name: "CreationEvenement", methods: ['POST'])]
    public static function creerEvenement(): void
    {
        $nomEvenement = $_REQUEST["nomEvenement"] ?? null;
        $loginUtilisateur = ConnexionUtilisateur::getLoginUtilisateurConnecte() ?? null;
        
        try {
            $codeSecret = self::getEvenementService()->creerEvenement($nomEvenement, $loginUtilisateur);
        } catch (ServiceException $e) {
            self::gererException($e);
        }
        
        MessageFlash::ajouter("success", "Événement créé avec succès");
        self::redirection("evenements/$codeSecret");
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "FormulaireMiseAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireMiseAJourEvenement(int $idEvenement): void
    {
        try {
            $evenement = self::getEvenementService()->verifierAccesEvenement($idEvenement);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Modification d'un événement",
            "cheminVueBody" => "evenement/formulaireMiseAJourEvenement.php",
            "evenement" => $evenement
        ]);
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "mettreAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public static function mettreAJourEvenement(int $idEvenement): void
    {
        $nomEvenement = $_REQUEST["nomEvenement"] ?? null;
        try {
            $codeSecret = self::getEvenementService()->mettreAJourEvenement($idEvenement, $nomEvenement);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }
        
        MessageFlash::ajouter("success", "Événement mis à jour avec succès.");
        self::redirection("evenements/$codeSecret");
    }

    #[Route(path: "/evenements/supprimer/{idEvenement}", name: "SupprimerEvenement",
        requirements: ['idEvenement' => '\d+'])]
    public static function supprimerEvenement(int $idEvenement): void
    {
        try {
            self::getEvenementService()->supprimerEvenement($idEvenement);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }
        MessageFlash::ajouter("success", "Événement supprimé avec succès");
        self::redirection("evenements");
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "afficherFormulaireAjoutMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireAjoutMembre(int $idEvenement): void
    {
        try {
            $resultat = self::getEvenementService()->recupererUtilisateursPourAjout($idEvenement);
            $evenement = $resultat["evenement"];
            $utilisateurs = $resultat["utilisateurs"];
        } catch (ServiceException $e) {
            MessageFlash::ajouter($e->getTypeMessageFlash(), $e->getMessage());
            self::redirection($e->getRedirectionUrl());
        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un membre",
            "cheminVueBody" => "evenement/formulaireAjoutMembreEvenement.php",
            "evenement" => $evenement,
            "utilisateurs" => $utilisateurs
        ]);
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "ajouterMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public static function ajouterMembre(int $idEvenement): void
    {
        $loginUtilisateur = $_REQUEST["login"] ?? null;

        try {
            $codeSecret = self::getEvenementService()->ajouterMembre($idEvenement, $loginUtilisateur);
        } catch (ServiceException $e) {
            self::gererException($e);
        }
        
        MessageFlash::ajouter("success", "Membre ajouté avec succès.");
        self::redirection("evenements/$codeSecret");
    }

    #[Route(path: "/evenements/quitter/{idEvenement}", name: "QuitterEvenement", requirements: ['idEvenement' => '\d+'])]
    public static function quitterEvenement(int $idEvenement): void
    {
        try {
            self::getEvenementService()->quitterEvenement($idEvenement);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Vous avez quitté l'événement avec succès.");
        self::redirection("evenements");
    }

    #[Route(path: "/evenements/supprimerMembre/{idEvenement}/{login}", name: "SupprimerMembre")]

    public static function supprimerMembre(int $idEvenement, string $login): void
    {
        try {
            $codeSecret = self::getEvenementService()->supprimerMembre($idEvenement, $login);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Membre supprimé avec succès.");
        self::redirection("evenements/$codeSecret");
    }


}