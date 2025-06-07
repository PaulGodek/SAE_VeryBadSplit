<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\UtilisateurServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Attribute\Route;

class ControleurUtilisateur extends ControleurGenerique
{
    
    public function __construct(
        ContainerInterface $container,
        private UtilisateurServiceInterface $utilisateurService,
        private ConnexionUtilisateur $connexionUtilisateur)
    {
        parent::__construct($container);
    }
    
    #[Route(path: '/compte', name: 'afficherDetail', methods: ['GET'])]
    public function afficherDetail(): void
    {
        try {
            $utilisateur = $this->utilisateurService->recupererUtilisateurConnecte();
        } catch (ServiceException $e) {
            $this->gererException($e, 'danger');
        }

        $this->afficherVue('vueGenerale.php', [
            "utilisateur" => $utilisateur,
            "pagetitle" => "Détails du compte",
            "cheminVueBody" => "utilisateur/detail.php"
        ]);
    }

    #[Route(path: '/inscription', name: 'afficherFormulaireCreation', methods: ['GET'])]
    public function afficherFormulaireCreation(): void
    {
        try {
            $this->utilisateurService->verifierNonConnecte();
        } catch (ServiceException $e) {
            $this->gererException($e);
        }

        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Création d'un utilisateur",
            "cheminVueBody" => "utilisateur/formulaireCreation.php"
        ]);
    }

    #[Route(path: '/inscription', name: 'creerDepuisFormulaire', methods: ['POST'])]
    public function creerDepuisFormulaire(): void
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
            $this->redirection("connexion");
        } catch (ServiceException $e) {
            $this->gererException($e, "warning");
        }
    }

    #[Route(path: '/compte/modifier', name: 'afficherFormulaireMiseAJour', methods: ['GET'])]
    public function afficherFormulaireMiseAJour(): void
    {
        try {
            $utilisateur = $this->utilisateurService->recupererUtilisateurConnecte();
        } catch (ServiceException $e) {
            $this->gererException($e);
        }

        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Mise à jour du profil",
            "cheminVueBody" => "utilisateur/formulaireMiseAJour.php",
            "utilisateur" => $utilisateur,
        ]);
    }

    #[Route(path: '/compte/modifier', name: 'modifierDepuisFormulaire', methods: ['POST'])]
    public function mettreAJour(): void
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
            $this->gererException($e);
        }

        MessageFlash::ajouter("success", "L'utilisateur a bien été modifié !");
        $this->redirection("compte");
    }

    #[Route(path: '/compte/supprimer/{login}', name: 'supprimerCompte', methods: ['GET'])]
    public function supprimer(string $login): void
    {
        try {
            $this->utilisateurService->supprimerUtilisateur($login);
        } catch (ServiceException $e) {
            $this->gererException($e);
        }

        MessageFlash::ajouter("success", "Votre compte a bien été supprimé !");
        $this->redirection("connexion");
    }

    #[Route(path: '/connexion', name: 'afficherFormulaireConnexion', methods: ['GET'])]
    public function afficherFormulaireConnexion(): void
    {
        try {
            $this->utilisateurService->verifierNonConnecte();
        } catch (ServiceException $e) {
            $this->gererException($e);
        }
        
        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Formulaire de connexion",
            "cheminVueBody" => "utilisateur/formulaireConnexion.php"
        ]);
    }

    #[Route(path: '/connexion', name: 'connecter', methods: ['POST'])]
    public function connecter(): void
    {
        $login = $_REQUEST["login"] ?? null;
        $mdp = $_REQUEST["mdp"] ?? null;
        
        try {
            $this->utilisateurService->connecterUtilisateur($login, $mdp);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Connexion réussie !");
        $this->redirection("evenements");
    }

    #[Route(path: '/deconnexion', name: 'deconnecter', methods: ['GET'])]
    public function deconnecter(): void
    {
        if ($this->connexionUtilisateur->estConnecte()) {
            $this->connexionUtilisateur->deconnecter();
            MessageFlash::ajouter("success", "Déconnexion réussie.");
        } else {
            MessageFlash::ajouter("danger", "Utilisateur non connecté.");
        }
        $this->redirectionVersRoute("accueil");
    }

    #[Route(path: '/recuperation', name: 'afficherFormulaireRecuperationCompte', methods: ['GET'])]
    public function afficherFormulaireRecuperationCompte(): void {
        if($this->connexionUtilisateur->estConnecte()) {
            $this->redirection("evenements");
        }
        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Récupérer mon compte",
            "cheminVueBody" => "utilisateur/formulaireRecuperationCompte.php"
        ]);
    }

    #[Route(path: '/recuperation', name: 'recupererCompte', methods: ['POST'])]
    public function recupererCompte(): void {
        $email = $_REQUEST["email"] ?? null;
        
        try {
            $utilisateurs = $this->utilisateurService->recupererUtilisateursParEmail($email);
        } catch (ServiceException $e) {
            $this->gererException($e, "warning");
        }

        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Récupérer mon compte",
            "cheminVueBody" => "utilisateur/resultatRecuperationCompte.php",
            "utilisateurs" => $utilisateurs
        ]);
    }
}