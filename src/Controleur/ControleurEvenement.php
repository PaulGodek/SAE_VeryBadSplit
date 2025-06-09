<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\DepenseService;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use Symfony\Component\HttpFoundation\Response;
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
    {
        return Conteneur::recupererService("utilisateurService");
    }

    #[Route(path: "/evenements/{codeEvenement}", name:"Evenement", requirements: ['codeEvenement' => '[a-zA-Z0-9]{64}'])]
    public static function afficherEvenement(string $codeEvenement): Response
    {
        try {
            $resultat = self::getEvenementService()->recupererEvenementAvecDettes2($codeEvenement);
            $evenement = $resultat["evenement"];
            $dettes = $resultat["dettes"];
            $coutTotal = $resultat["coutTotal"];
            $transactions=$resultat["transactionsOptimisees"];
            $depenses = self::getDepenseService()->recupererDepensesParEvenement($evenement->getId());
        } catch (ServiceException $e) {
            return self::gererException($e, "warning");
        }

        if(is_null($depenses)){
            $depenses=[];
        }

        return self::afficherTwig("evenement/evenement.html.twig", [
            "evenement" => $evenement,
            "depenses" => $depenses,
            "transactions" => $transactions,
            "coutTotal" => $coutTotal,
            "dettes" => $dettes,
            "loginConnecte" => ConnexionUtilisateur::getLoginUtilisateurConnecte()
        ]);
    }

    #[Route(path: "/evenements", name: "MesEvenements")]
    public static function afficherListeMesEvenements(): Response
    {
        $login = ConnexionUtilisateur::getLoginUtilisateurConnecte() ?? null;
        try {
            $evenements = self::getEvenementService()->recupererEvenementsUtilisateur($login);
        } catch (ServiceException $e) {
            return self::gererException($e, "danger");
        }

        return self::afficherTwig("evenement/listeEvenementsUtilisateur.html.twig", [
            "evenements" => $evenements,
            "loginConnecte" => ConnexionUtilisateur::getLoginUtilisateurConnecte()
        ]);
    }

    #[Route(path: "/evenements/creation", name: "FormulaireCreationEvenement", methods: ['GET'])]
    public static function afficherFormulaireCreationEvenement(): Response {
        try {
            self::getEvenementService()->verifierConnexion();
        } catch (ServiceException $e) {
            return self::gererException($e, "danger");
        }
        return self::afficherTwig("evenement/formulaireCreationEvenement.html.twig");
    }

    #[Route(path: "/evenements/creation", name: "CreationEvenement", methods: ['POST'])]
    public static function creerEvenement(): Response
    {
        $nomEvenement = $_REQUEST["nomEvenement"] ?? null;
        $loginUtilisateur = ConnexionUtilisateur::getLoginUtilisateurConnecte() ?? null;
        
        try {
            $codeSecret = self::getEvenementService()->creerEvenement($nomEvenement, $loginUtilisateur);
        } catch (ServiceException $e) {
            return self::gererException($e);
        }
        
        MessageFlash::ajouter("success", "Événement créé avec succès");
        return self::redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "FormulaireMiseAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireMiseAJourEvenement(int $idEvenement): Response
    {
        try {
            $evenement = self::getEvenementService()->verifierAccesEvenement($idEvenement);
        } catch (ServiceException $e) {
            return self::gererException($e, "danger");
        }

        return self::afficherTwig("evenement/formulaireMiseAJourEvenement.html.twig", [
            "evenement" => $evenement
        ]);
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "mettreAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public static function mettreAJourEvenement(int $idEvenement): Response
    {
        $nomEvenement = $_REQUEST["nomEvenement"] ?? null;
        try {
            $codeSecret = self::getEvenementService()->mettreAJourEvenement($idEvenement, $nomEvenement);
        } catch (ServiceException $e) {
            return self::gererException($e, "danger");
        }
        
        MessageFlash::ajouter("success", "Événement mis à jour avec succès.");
        return self::redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }

    #[Route(path: "/evenements/supprimer/{idEvenement}", name: "SupprimerEvenement",
        requirements: ['idEvenement' => '\d+'])]
    public static function supprimerEvenement(int $idEvenement): Response
    {
        try {
            self::getEvenementService()->supprimerEvenement($idEvenement);
        } catch (ServiceException $e) {
            return self::gererException($e, "danger");
        }
        MessageFlash::ajouter("success", "Événement supprimé avec succès");
        return self::redirection("MesEvenements");
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "afficherFormulaireAjoutMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireAjoutMembre(int $idEvenement): Response
    {
        try {
            $resultat = self::getEvenementService()->recupererUtilisateursPourAjout($idEvenement);
            $evenement = $resultat["evenement"];
            $utilisateurs = $resultat["utilisateurs"];
        } catch (ServiceException $e) {
            MessageFlash::ajouter($e->getTypeMessageFlash(), $e->getMessage());
            return self::redirection($e->getRedirectionRoute(), $e->getArguments());
        }

        return self::afficherTwig("evenement/formulaireAjoutMembreEvenement.html.twig", [
            "evenement" => $evenement,
            "utilisateurs" => $utilisateurs
        ]);
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "ajouterMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public static function ajouterMembre(int $idEvenement): Response
    {
        $loginUtilisateur = $_REQUEST["login"] ?? null;

        try {
            $codeSecret = self::getEvenementService()->ajouterMembre($idEvenement, $loginUtilisateur);
        } catch (ServiceException $e) {
            return self::gererException($e);
        }
        
        MessageFlash::ajouter("success", "Membre ajouté avec succès.");
        return self::redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }

    #[Route(path: "/evenements/quitter/{idEvenement}", name: "QuitterEvenement", requirements: ['idEvenement' => '\d+'])]
    public static function quitterEvenement(int $idEvenement): Response
    {
        try {
            self::getEvenementService()->quitterEvenement($idEvenement);
        } catch (ServiceException $e) {
            return self::gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Vous avez quitté l'événement avec succès.");
        return self::redirection("MesEvenements");
    }

    #[Route(path: "/evenements/supprimerMembre/{idEvenement}/{login}", name: "SupprimerMembre")]
    public static function supprimerMembre(int $idEvenement, string $login): Response
    {
        try {
            $codeSecret = self::getEvenementService()->supprimerMembre($idEvenement, $login);
        } catch (ServiceException $e) {
            return self::gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Membre supprimé avec succès.");
        return self::redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }
}