<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\HTTP\Cookie;
use App\VeryBadSplit\Lib\MotDePasse;
use App\VeryBadSplit\Service\EmailService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\EmailServiceInterface;
use App\VeryBadSplit\Service\Interface\UtilisateurServiceInterface;
use App\VeryBadSplit\Service\UtilisateurService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ControleurUtilisateur extends ControleurGenerique
{

    public function __construct(
        ContainerInterface $container,
        private UtilisateurServiceInterface $utilisateurService,
        private EmailServiceInterface $emailService)
    {
        parent::__construct($container);
    }

    #[Route(path: '/compte', name: 'afficherDetail', methods: ['GET'])]
    public function afficherDetail(): Response
    {
        try {
            $utilisateur = $this->utilisateurService->recupererUtilisateurConnecte();
        } catch (ServiceException $e) {
            return $this->gererException($e, 'danger');
        }

        return $this->afficherTwig("utilisateur/detail.html.twig", [
            "utilisateur" => $utilisateur
        ]);
    }

    #[Route(path: '/inscription', name: 'afficherFormulaireCreation', methods: ['GET'])]
    public function afficherFormulaireCreation(): Response
    {
        try {
            $this->utilisateurService->verifierNonConnecte();
        } catch (ServiceException $e) {
            return $this->gererException($e);
        }

        return $this->afficherTwig("utilisateur/formulaireCreation.html.twig");
    }

    #[Route(path: '/inscription', name: 'creerDepuisFormulaire', methods: ['POST'])]
    public function creerDepuisFormulaire(): Response
    {
        $login = $_REQUEST["login"] ?? null;
        $prenom = $_REQUEST["prenom"] ?? null;
        $nom = $_REQUEST["nom"] ?? null;
        $email = $_REQUEST["email"] ?? null;
        $mdp = $_REQUEST["mdp"] ?? null;
        $mdp2 = $_REQUEST["mdp2"] ?? null;

        try {
            $this->utilisateurService->creerUtilisateur($login, $prenom, $nom, $email, $mdp, $mdp2);
            MessageFlash::ajouter("success", "L'utilisateur a bien été créé !");
            return $this->redirection("afficherFormulaireConnexion");
        } catch (ServiceException $e) {
            return $this->gererException($e, "warning");
        }
    }

    #[Route(path: '/compte/modifier', name: 'afficherFormulaireMiseAJour', methods: ['GET'])]
    public function afficherFormulaireMiseAJour(): Response
    {
        try {
            $utilisateur = $this->utilisateurService->recupererUtilisateurConnecte();
        } catch (ServiceException $e) {
            return $this->gererException($e);
        }

        return $this->afficherTwig("utilisateur/formulaireMiseAJour.html.twig", [
            "utilisateur" => $utilisateur
        ]);
    }

    #[Route(path: '/compte/modifier', name: 'modifierDepuisFormulaire', methods: ['POST'])]
    public function mettreAJour(): Response
    {
        $login = $_REQUEST['login'] ?? null;
        $prenom = $_REQUEST['prenom'] ?? null;
        $nom = $_REQUEST['nom'] ?? null;
        $email = $_REQUEST['email'] ?? null;
        $mdpActuel = $_REQUEST['mdpActuel'] ?? null;
        $mdp = $_REQUEST['mdp'] ?? null;
        $mdp2 = $_REQUEST['mdp2'] ?? null;

        try {
            $this->utilisateurService->mettreAJourUtilisateur($login, $prenom, $nom, $email, $mdpActuel, $mdp, $mdp2);
        } catch (ServiceException $e) {
            return $this->gererException($e);
        }

        MessageFlash::ajouter("success", "L'utilisateur a bien été modifié !");
        return $this->redirection("afficherDetail");
    }

    #[Route(path: '/compte/supprimer/{login}', name: 'supprimerCompte', methods: ['GET'])]
    public function supprimer(string $login): Response
    {
        try {
            $this->utilisateurService->supprimerUtilisateur($login);
        } catch (ServiceException $e) {
            return $this->gererException($e);
        }

        MessageFlash::ajouter("success", "Votre compte a bien été supprimé !");
        return $this->redirection("afficherFormulaireConnexion");
    }

    #[Route(path: '/connexion', name: 'afficherFormulaireConnexion', methods: ['GET'])]
    public function afficherFormulaireConnexion(): Response
    {
        try {
            $this->utilisateurService->verifierNonConnecte();
        } catch (ServiceException $e) {
            return $this->gererException($e);
        }

        return $this->afficherTwig("utilisateur/formulaireConnexion.html.twig", [
            "login" => Cookie::contient("login") ? Cookie::lire("login") : "",
            "mdp" => Cookie::contient("mdp") ? Cookie::lire("mdp") : ""
        ]);
    }

    #[Route(path: '/connexion', name: 'connecter', methods: ['POST'])]
    public function connecter(): Response
    {
        $login = $_REQUEST["login"] ?? null;
        $mdp = $_REQUEST["mdp"] ?? null;
        
        try {
            $this->utilisateurService->connecterUtilisateur($login, $mdp);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Connexion réussie !");
        return $this->redirection("MesEvenements");
    }

    #[Route(path: '/deconnexion', name: 'deconnecter', methods: ['GET'])]
    public function deconnecter(): Response
    {
        if (ConnexionUtilisateur::estConnecte()) {
            ConnexionUtilisateur::deconnecter();
            MessageFlash::ajouter("success", "Déconnexion réussie.");
        } else {
            MessageFlash::ajouter("danger", "Utilisateur non connecté.");
        }
        return $this->redirection("accueil");
    }

    #[Route(path: '/recuperation', name: 'afficherFormulaireRecuperationCompte', methods: ['GET'])]
    public function afficherFormulaireRecuperationCompte(): Response {
        if(ConnexionUtilisateur::estConnecte()) {
            return $this->redirection("MesEvenements");
        }

        return $this->afficherTwig("utilisateur/formulaireRecuperationCompte.html.twig");
    }

    #[Route(path: '/mail', name: 'envoiMail', methods: ['POST'])]
    public function envoiMailOublieMdp(): Response {
        $email = $_REQUEST["email"] ?? null;

        try{
            $utilisateur=$this->utilisateurService->recupererUtilisateurParEmail($email);
            $mdp=MotDePasse::genererMdpAleatoire();
            $this->utilisateurService->reinitialiserMotDePasse($utilisateur->getLogin(),$mdp);
            $this->emailService->envoyerMailMdpOublie($utilisateur,$mdp);
        }catch(ServiceException $e){
            return $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Le mail a été envoyé avec succès");
        return $this->redirection("afficherFormulaireConnexion");
    }
}