<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;

use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ControleurEvenement extends ControleurGenerique
{
    private static function getEvenementService(): EvenementService
    {
        return Conteneur::recupererService("evenementService");
    }
    #[Route(path: "/evenements/{codeEvenement}", name:"Evenement", requirements: ['codeEvenement' => '[a-zA-Z0-9]{64}'])]
    public static function afficherEvenement(string $codeEvenement): Response
    {
        try {
            $resultat = self::getEvenementService()->recupererEvenementAvecDettes($codeEvenement);
            $evenement = $resultat["evenement"];
            $dettes = $resultat["dettes"];
            $coutTotal = $resultat["coutTotal"];
        } catch (ServiceException $e) {
            return self::gererException($e, "warning");
        }

        return self::afficherVue('vueGenerale.php', [
            "pagetitle" => $evenement->getTitre(),
            "cheminVueBody" => "evenement/evenement.php",
            "evenement" => $evenement,
            "coutTotal" => $coutTotal,
            "dettes" => $dettes,
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

        return self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Liste des événements de $login",
            "cheminVueBody" => "evenement/listeEvenementsUtilisateur.php",
            "evenements" => $evenements
        ]);
    }

    #[Route(path: "/evenements/creation", name: "FormulaireCreationEvenement", methods: ['GET'])]
    public static function afficherFormulaireCreationEvenement(): Response {
        try {
            self::getEvenementService()->verifierConnexion();
        } catch (ServiceException $e) {
            return self::gererException($e, "danger");
        }
        return self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un événement",
            "cheminVueBody" => "evenement/formulaireCreationEvenement.php",
        ]);
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

        return self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Modification d'un événement",
            "cheminVueBody" => "evenement/formulaireMiseAJourEvenement.php",
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

        return self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un membre",
            "cheminVueBody" => "evenement/formulaireAjoutMembreEvenement.php",
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