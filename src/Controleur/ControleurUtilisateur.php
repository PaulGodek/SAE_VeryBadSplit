<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Lib\MotDePasse;
use App\VeryBadSplit\Service\EmailService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\UtilisateurService;
use Symfony\Component\Routing\Attribute\Route;

class ControleurUtilisateur extends ControleurGenerique
{
    private static function getUtilisateurService(): UtilisateurService
    {
        return Conteneur::recupererService("utilisateurService");
    }


    public static function getEmailService():EmailService{
        return Conteneur::recupererService("emailService");
    }

    #[Route(path: '/compte', name: 'afficherDetail', methods: ['GET'])]
    public static function afficherDetail(): void
    {
        try {
            $utilisateur = self::getUtilisateurService()->recupererUtilisateurConnecte();
        } catch (ServiceException $e) {
            self::gererException($e, 'danger');
        }

        self::afficherVue('vueGenerale.php', [
            "utilisateur" => $utilisateur,
            "pagetitle" => "Détails du compte",
            "cheminVueBody" => "utilisateur/detail.php"
        ]);
    }

    #[Route(path: '/inscription', name: 'afficherFormulaireCreation', methods: ['GET'])]
    public static function afficherFormulaireCreation(): void
    {
        try {
            self::getUtilisateurService()->verifierNonConnecte();
        } catch (ServiceException $e) {
            self::gererException($e);
        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Création d'un utilisateur",
            "cheminVueBody" => "utilisateur/formulaireCreation.php"
        ]);
    }

    #[Route(path: '/inscription', name: 'creerDepuisFormulaire', methods: ['POST'])]
    public static function creerDepuisFormulaire(): void
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
            self::redirection("connexion");
        } catch (ServiceException $e) {
            self::gererException($e, "warning");
        }
    }

    #[Route(path: '/compte/modifier', name: 'afficherFormulaireMiseAJour', methods: ['GET'])]
    public static function afficherFormulaireMiseAJour(): void
    {
        try {
            $utilisateur = self::getUtilisateurService()->recupererUtilisateurConnecte();
        } catch (ServiceException $e) {
            self::gererException($e);
        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Mise à jour du profil",
            "cheminVueBody" => "utilisateur/formulaireMiseAJour.php",
            "utilisateur" => $utilisateur,
        ]);
    }

    #[Route(path: '/compte/modifier', name: 'modifierDepuisFormulaire', methods: ['POST'])]
    public static function mettreAJour(): void
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
            self::gererException($e);
        }

        MessageFlash::ajouter("success", "L'utilisateur a bien été modifié !");
        self::redirection("compte");
    }

    #[Route(path: '/compte/supprimer/{login}', name: 'supprimerCompte', methods: ['GET'])]
    public static function supprimer(string $login): void
    {
        try {
            self::getUtilisateurService()->supprimerUtilisateur($login);
        } catch (ServiceException $e) {
            self::gererException($e);
        }

        MessageFlash::ajouter("success", "Votre compte a bien été supprimé !");
        self::redirection("connexion");
    }

    #[Route(path: '/connexion', name: 'afficherFormulaireConnexion', methods: ['GET'])]
    public static function afficherFormulaireConnexion(): void
    {
        try {
            self::getUtilisateurService()->verifierNonConnecte();
        } catch (ServiceException $e) {
            self::gererException($e);
        }
        
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Formulaire de connexion",
            "cheminVueBody" => "utilisateur/formulaireConnexion.php"
        ]);
    }

    #[Route(path: '/connexion', name: 'connecter', methods: ['POST'])]
    public static function connecter(): void
    {
        $login = $_REQUEST["login"] ?? null;
        $mdp = $_REQUEST["mdp"] ?? null;
        
        try {
            self::getUtilisateurService()->connecterUtilisateur($login, $mdp);
        } catch (ServiceException $e) {
            self::gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Connexion réussie !");
        self::redirection("evenements");
    }

    #[Route(path: '/deconnexion', name: 'deconnecter', methods: ['GET'])]
    public static function deconnecter(): void
    {
        if (ConnexionUtilisateur::estConnecte()) {
            ConnexionUtilisateur::deconnecter();
            MessageFlash::ajouter("success", "Déconnexion réussie.");
        } else {
            MessageFlash::ajouter("danger", "Utilisateur non connecté.");
        }
        self::redirectionVersRoute("accueil");
    }

    #[Route(path: '/recuperation', name: 'afficherFormulaireRecuperationCompte', methods: ['GET'])]
    public static function afficherFormulaireRecuperationCompte(): void {
        if(ConnexionUtilisateur::estConnecte()) {
            self::redirection("evenements");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Récupérer mon compte",
            "cheminVueBody" => "utilisateur/formulaireRecuperationCompte.php"
        ]);
    }

    /*#[Route(path: '/recuperation', name: 'recupererCompte', methods: ['POST'])]
    public static function recupererCompte(): void {
        $email = $_REQUEST["email"] ?? null;
        
        try {
            $utilisateur = self::getUtilisateurService()->recupererUtilisateurParEmail($email);
        } catch (ServiceException $e) {
            self::gererException($e, "warning");
        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Récupérer mon compte",
            "cheminVueBody" => "utilisateur/resultatRecuperationCompte.php",
            "utilisateur" => $utilisateur
        ]);
    }*/


    /*#[Route(path: '/reinitialisation', name: 'reinitialiserMdp', methods: ['POST'])]
    public static function reinitialiserMdp(): void {
        $login = $_REQUEST["login"] ?? null;
        $mdp = $_REQUEST["mdp"] ?? null;


        try {
            self::getUtilisateurService()->reinitialiserMotDePasse($login, $mdp,$mdp2);
        } catch (ServiceException $e) {
            self::gererException($e, "warning");
        }
        MessageFlash::ajouter("success", "Mot de passe réinitialisé avec succès !");

        self::redirection("connexion");
    }*/

    //---------------------------------------------------------------------------------------------------------------------------


    /**
     * @throws ServiceException
     */
    #[Route(path: '/mail', name: 'envoiMail', methods: ['POST'])]
    public static function envoiMailOublieMdp():void{
        $email = $_REQUEST["email"] ?? null;

        try{
            $utilisateur=self::getUtilisateurService()->recupererUtilisateurParEmail($email);
            $mdp=MotDePasse::genererMdpAleatoire();
            self::getUtilisateurService()->reinitialiserMotDePasse($utilisateur->getLogin(),$mdp);
            self::getEmailService()->envoyerMailMdpOublie($utilisateur,$mdp);
        }catch(ServiceException $e){
            self::gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Le mail a été envoyé");
        self::redirectionVersRoute("afficherFormulaireConnexion");
    }




}