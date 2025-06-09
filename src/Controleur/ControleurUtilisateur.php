<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\HTTP\Cookie;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\UtilisateurService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ControleurUtilisateur extends ControleurGenerique
{
    private static function getUtilisateurService(): UtilisateurService
    {
        return Conteneur::recupererService("utilisateurService");
    }
    #[Route(path: '/compte', name: 'afficherDetail', methods: ['GET'])]
    public static function afficherDetail(): Response
    {
        try {
            $utilisateur = self::getUtilisateurService()->recupererUtilisateurConnecte();
        } catch (ServiceException $e) {
            return self::gererException($e, 'danger');
        }

        return self::afficherTwig("utilisateur/detail.html.twig", [
            "utilisateur" => $utilisateur
        ]);
    }

    #[Route(path: '/inscription', name: 'afficherFormulaireCreation', methods: ['GET'])]
    public static function afficherFormulaireCreation(): Response
    {
        try {
            self::getUtilisateurService()->verifierNonConnecte();
        } catch (ServiceException $e) {
            return self::gererException($e);
        }

        return self::afficherTwig("utilisateur/formulaireCreation.html.twig");
    }

    #[Route(path: '/inscription', name: 'creerDepuisFormulaire', methods: ['POST'])]
    public static function creerDepuisFormulaire(): Response
    {
        $login = $_REQUEST["login"] ?? null;
        $prenom = $_REQUEST["prenom"] ?? null;
        $nom = $_REQUEST["nom"] ?? null;
        $email = $_REQUEST["email"] ?? null;
        $mdp = $_REQUEST["mdp"] ?? null;
        $mdp2 = $_REQUEST["mdp2"] ?? null;

        try {
            self::getUtilisateurService()->creerUtilisateur($login, $prenom, $nom, $email, $mdp, $mdp2);
            MessageFlash::ajouter("success", "L'utilisateur a bien été créé !");
            return self::redirection("afficherFormulaireConnexion");
        } catch (ServiceException $e) {
            return self::gererException($e, "warning");
        }
    }

    #[Route(path: '/compte/modifier', name: 'afficherFormulaireMiseAJour', methods: ['GET'])]
    public static function afficherFormulaireMiseAJour(): Response
    {
        try {
            $utilisateur = self::getUtilisateurService()->recupererUtilisateurConnecte();
        } catch (ServiceException $e) {
            return self::gererException($e);
        }

        return self::afficherTwig("utilisateur/formulaireMiseAJour.html.twig", [
            "utilisateur" => $utilisateur
        ]);
    }

    #[Route(path: '/compte/modifier', name: 'modifierDepuisFormulaire', methods: ['POST'])]
    public static function mettreAJour(): Response
    {
        $login = $_REQUEST['login'] ?? null;
        $prenom = $_REQUEST['prenom'] ?? null;
        $nom = $_REQUEST['nom'] ?? null;
        $email = $_REQUEST['email'] ?? null;
        $mdpActuel = $_REQUEST['mdpActuel'] ?? null;
        $mdp = $_REQUEST['mdp'] ?? null;
        $mdp2 = $_REQUEST['mdp2'] ?? null;

        try {
            self::getUtilisateurService()->mettreAJourUtilisateur($login, $prenom, $nom, $email, $mdpActuel, $mdp, $mdp2);
        } catch (ServiceException $e) {
            return self::gererException($e);
        }

        MessageFlash::ajouter("success", "L'utilisateur a bien été modifié !");
        return self::redirection("afficherDetail");
    }

    #[Route(path: '/compte/supprimer/{login}', name: 'supprimerCompte', methods: ['GET'])]
    public static function supprimer(string $login): Response
    {
        try {
            self::getUtilisateurService()->supprimerUtilisateur($login);
        } catch (ServiceException $e) {
            return self::gererException($e);
        }

        MessageFlash::ajouter("success", "Votre compte a bien été supprimé !");
        return self::redirection("afficherFormulaireConnexion");
    }

    #[Route(path: '/connexion', name: 'afficherFormulaireConnexion', methods: ['GET'])]
    public static function afficherFormulaireConnexion(): Response
    {
        try {
            self::getUtilisateurService()->verifierNonConnecte();
        } catch (ServiceException $e) {
            return self::gererException($e);
        }

        return self::afficherTwig("utilisateur/formulaireConnexion.html.twig", [
            "login" => Cookie::contient("login") ? Cookie::lire("login") : "",
            "mdp" => Cookie::contient("mdp") ? Cookie::lire("mdp") : ""
        ]);
    }

    #[Route(path: '/connexion', name: 'connecter', methods: ['POST'])]
    public static function connecter(): Response
    {
        $login = $_REQUEST["login"] ?? null;
        $mdp = $_REQUEST["mdp"] ?? null;
        
        try {
            self::getUtilisateurService()->connecterUtilisateur($login, $mdp);
        } catch (ServiceException $e) {
            return self::gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Connexion réussie !");
        return self::redirection("MesEvenements");
    }

    #[Route(path: '/deconnexion', name: 'deconnecter', methods: ['GET'])]
    public static function deconnecter(): Response
    {
        if (ConnexionUtilisateur::estConnecte()) {
            ConnexionUtilisateur::deconnecter();
            MessageFlash::ajouter("success", "Déconnexion réussie.");
        } else {
            MessageFlash::ajouter("danger", "Utilisateur non connecté.");
        }
        return self::redirection("accueil");
    }

    #[Route(path: '/recuperation', name: 'afficherFormulaireRecuperationCompte', methods: ['GET'])]
    public static function afficherFormulaireRecuperationCompte(): Response {
        if(ConnexionUtilisateur::estConnecte()) {
            return self::redirection("MesEvenements");
        }

        return self::afficherTwig("utilisateur/formulaireRecuperationCompte.html.twig");
    }

    #[Route(path: '/recuperation', name: 'recupererCompte', methods: ['POST'])]
    public static function recupererCompte(): Response {
        $email = $_REQUEST["email"] ?? null;
        
        try {
            $utilisateurs = self::getUtilisateurService()->recupererUtilisateursParEmail($email);
        } catch (ServiceException $e) {
            return self::gererException($e, "warning");
        }

        return self::afficherTwig("utilisateur/resultatRecuperationCompte.html.twig", [
            "utilisateurs" => $utilisateurs
        ]);
    }

    #[Route(path: '/reinitialisation', name: 'reinitialiserMdp', methods: ['POST'])]
    public static function reinitialiserMdp(): Response {
        $login = $_REQUEST["login"] ?? null;
        $mdp = $_REQUEST["mdp"] ?? null;
        $mdp2 = $_REQUEST["mdp2"] ?? null;

        try {
            self::getUtilisateurService()->reinitialiserMotDePasse($login, $mdp,$mdp2);
        } catch (ServiceException $e) {
            return self::gererException($e, "warning");
        }

        MessageFlash::ajouter("success", "Mot de passe réinitialisé avec succès !");
        return self::redirection("connexion");
    }
}