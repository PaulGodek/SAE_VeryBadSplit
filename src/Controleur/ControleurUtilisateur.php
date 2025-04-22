<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Lib\MotDePasse;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\HTTP\Cookie;
use App\VeryBadSplit\Modele\Repository\DepenseRepository;
use App\VeryBadSplit\Modele\Repository\EvenementRepository;
use App\VeryBadSplit\Modele\Repository\UtilisateurRepository;
use Symfony\Component\Routing\Attribute\Route;

class ControleurUtilisateur extends ControleurGenerique
{
    public static function afficherDetail(): void
    {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        $utilisateur = (new UtilisateurRepository())->recuperer(ConnexionUtilisateur::getLoginUtilisateurConnecte());
        self::afficherVue('vueGenerale.php', [
            "utilisateur" => $utilisateur,
            "pagetitle" => "Détails du compte",
            "cheminVueBody" => "utilisateur/detail.php"
        ]);
    }

    #[Route(path: '/inscription', name: 'afficherFormulaireCreation', methods: ['GET'])]
    public static function afficherFormulaireCreation(): void
    {
        if(ConnexionUtilisateur::estConnecte()) {
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Création d'un utilisateur",
            "cheminVueBody" => "utilisateur/formulaireCreation.php"
        ]);
    }

    public static function creerDepuisFormulaire(): void
    {
        if(ConnexionUtilisateur::estConnecte()) {
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        if (self::issetAndNotNull(["login", "prenom", "nom", "mdp", "mdp2", "email"])) {
            if ($_REQUEST["mdp"] !== $_REQUEST["mdp2"]) {
                MessageFlash::ajouter("warning", "Mots de passe distincts.");
                self::redirection("utilisateur", "afficherFormulaireCreation");
            }
            if (!filter_var($_REQUEST["email"], FILTER_VALIDATE_EMAIL)) {
                MessageFlash::ajouter("warning", "Email non valide");
                self::redirection("utilisateur", "afficherFormulaireCreation");
            }

            $utilisateurRepository = new UtilisateurRepository();

            $checkUtilisateur = $utilisateurRepository->recuperer($_REQUEST["login"]);
            if($checkUtilisateur) {
                MessageFlash::ajouter("warning", "Le login est déjà pris.");
                self::redirection("utilisateur", "afficherFormulaireCreation");
            }

            $utilisateur = new Utilisateur(
                login: $_REQUEST["login"],
                nom: $_REQUEST["nom"],
                prenom: $_REQUEST["prenom"],
                email: $_REQUEST["email"],
                mdpHache: MotDePasse::hacher($_REQUEST["mdp"]),
                mdp: $_REQUEST["mdp"]
            );

            $evenementRepository = new EvenementRepository();
            $idEvenement = $evenementRepository->getNextId();

            $depenseRepository = new DepenseRepository();
            $idDepense = $depenseRepository->getNextId();

            $object = new Depense(
                id: $idDepense,
                titre: "Exemple de dépense",
                date: new \DateTime(),
                montant: 50,
                payeur: $utilisateur,
                evenement: new Evenement(
                    id: $idEvenement,
                    codeSecret: hash("sha256", $_REQUEST["login"].$idEvenement),
                    titre: "Evenement d'exemple",
                    date: new \DateTime(),
                    proprietaire: $utilisateur,
                    membres: [$utilisateur]
                ),
                participants: [$utilisateur]
            );

            if ($depenseRepository->ajouter($object)) {
                Cookie::enregistrer("login", $_REQUEST["login"]);
                Cookie::enregistrer("mdp", $_REQUEST["mdp"]);
                MessageFlash::ajouter("success", "L'utilisateur a bien été créé !");
                self::redirection("utilisateur", "afficherFormulaireConnexion");
            }
            else {
                MessageFlash::ajouter("warning", "Une erreur est survenue lors de la création de l'utilisateur.");
                self::redirection("utilisateur", "afficherFormulaireCreation");
            }
        } else {
            MessageFlash::ajouter("danger", "Login, nom, prenom, email ou mot de passe manquant.");
            self::redirection("utilisateur", "afficherFormulaireCreation");
        }
    }

    public static function afficherFormulaireMiseAJour(): void
    {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        $login = ConnexionUtilisateur::getLoginUtilisateurConnecte();
        $repository = new UtilisateurRepository();
        $utilisateur = $repository->recuperer($login);
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Mise à jour du profil",
            "cheminVueBody" => "utilisateur/formulaireMiseAJour.php",
            "utilisateur" => $utilisateur,
        ]);
    }

    public static function mettreAJour(): void
    {
        $x = ConnexionUtilisateur::tel();
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if (ControleurUtilisateur::issetAndNotNull(["login", "prenom", "nom", "mdpActuel", "email"])) {
            $login = $_REQUEST['login'];
            $repository = new UtilisateurRepository();

            $utilisateur = $repository->recuperer($login);

            if(!$utilisateur) {
                MessageFlash::ajouter("danger", "L'utilisateur n'existe pas");
                self::redirection("utilisateur", "afficherFormulaireMiseAJour");
            }

            if (!filter_var($_REQUEST["email"], FILTER_VALIDATE_EMAIL)) {
                MessageFlash::ajouter("warning", "Email non valide");
                self::redirection("utilisateur", "afficherFormulaireMiseAJour");
            }

            if(self::issetAndNotNull(["mdp"]) || self::issetAndNotNull(["mdp2"])) {
                if(!self::issetAndNotNull(["mdp", "mdp2"])) {
                    MessageFlash::ajouter("warning", "Pour modifier votre mot de passe, vous devez saisir les 2 champs correspondants.");
                    self::redirection("utilisateur", "afficherFormulaireMiseAJour");
                }
                else if ($_REQUEST["mdp"] !== $_REQUEST["mdp2"]) {
                    MessageFlash::ajouter("warning", "Mots de passe distincts.");
                    self::redirection("utilisateur", "afficherFormulaireMiseAJour");
                }
            }

            $utilisateur->setNom($_REQUEST["nom"]);
            $utilisateur->setPrenom($_REQUEST["prenom"]);
            $utilisateur->setEmail($_REQUEST["email"]);

            if(self::issetAndNotNull(["mdp", "mdp2"])) {
                $utilisateur->setMdpHache(MotDePasse::hacher($_REQUEST["mdp"]));
                $utilisateur->setMdp($_REQUEST["mdp"]);
                Cookie::enregistrer("mdp", $_REQUEST["mdp"]);
            }

            $repository->mettreAJour($utilisateur);

            $evenementRepository = new EvenementRepository();
            foreach ($evenementRepository->recupererEvenementsUtilisateur($login) as $evenement) {
                $membres = array_filter($evenement->getMembres(), function ($u) use ($login) {return $u->getLogin() !== $login;});
                $membres[] = $utilisateur;
                $evenement->setMembres($membres);
                $evenementRepository->mettreAJour($evenement);
            }

            MessageFlash::ajouter("success", "L'utilisateur a bien été modifié !");
            self::redirection("utilisateur", "afficherDetail");
        } else {
            MessageFlash::ajouter("danger", "Login, nom, prenom, email ou mot de passe actuel manquant.");
            self::redirection("utilisateur", "afficherFormulaireMiseAJour");
        }
    }

    public static function supprimer(): void
    {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if (!self::issetAndNotNull(["login"])) {
            MessageFlash::ajouter("warning", "Login manquant");
            self::redirection("utilisateur", "afficherDetail");
        }
        $login = $_REQUEST["login"];

        $evenementRepository = new EvenementRepository();
        foreach ($evenementRepository->recupererEvenementsUtilisateur($login) as $evenement) {
            $membres = array_filter($evenement->getMembres(), function ($u) use ($login) {return $u->getLogin() !== $login;});
            $evenement->setMembres($membres);
            $evenementRepository->mettreAJour($evenement);
        }

        $depensesRepository = new DepenseRepository();
        foreach ($depensesRepository->recupererDepensesPayeesOuParticipeUtilisateur($login) as $depense) {
            if($depense->estPayeur($login)) {
                $depensesRepository->supprimer($depense->getId());
            }
            else if($depense->estParticipant($login)) {
                $participants = array_filter($depense->getParticipants(), function ($u) use ($login) {return $u->getLogin() !== $login;});
                if(empty($participants)) {
                    $depensesRepository->supprimer($depense->getId());
                }
                else {
                    $depense->setParticipants($participants);
                    $depensesRepository->mettreAJour($depense);
                }
            }
        }

        $repository = new UtilisateurRepository();
        $repository->supprimer($login);
        Cookie::supprimer("login");
        Cookie::supprimer("mdp");
        ConnexionUtilisateur::deconnecter();
        MessageFlash::ajouter("success", "Votre compte a bien été supprimé!");
        self::redirection("utilisateur", "afficherFormulaireConnexion");
    }

    #[Route(path: '/connexion', name: 'afficherFormulaireConnexion', methods: ['GET'])]
    public static function afficherFormulaireConnexion(): void
    {
        if(ConnexionUtilisateur::estConnecte()) {
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Formulaire de connexion",
            "cheminVueBody" => "utilisateur/formulaireConnexion.php"
        ]);
    }

    public static function connecter(): void
    {
        if(ConnexionUtilisateur::estConnecte()) {
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        if (!self::issetAndNotNull(["login", "mdp"])) {
            MessageFlash::ajouter("danger", "Login ou mot de passe manquant.");
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        $utilisateurRepository = new UtilisateurRepository();
        /** @var Utilisateur $utilisateur */
        $utilisateur = $utilisateurRepository->recuperer($_REQUEST["login"]);

        if ($utilisateur == null) {
            MessageFlash::ajouter("danger", "Login inconnu.");
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }

        if (!MotDePasse::verifier($_REQUEST["mdp"], $utilisateur->getMdpHache())) {
            MessageFlash::ajouter("danger", "Mot de passe incorrect.");
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }

        ConnexionUtilisateur::connecter($utilisateur->getLogin());
        Cookie::enregistrer("login", $_REQUEST["login"]);
        Cookie::enregistrer("mdp", $_REQUEST["mdp"]);
        self::redirection("evenement", "afficherListeMesEvenements");
    }

    public static function deconnecter(): void
    {
        if (!ConnexionUtilisateur::estConnecte()) {
            MessageFlash::ajouter("danger", "Utilisateur non connecté.");
            self::redirection("base", "accueil");
        }
        ConnexionUtilisateur::deconnecter();
        self::redirection("base", "accueil");
    }

    #[Route(path: '/recuperation', name: 'afficherFormulaireRecuperationCompte', methods: ['GET'])]
    public static function afficherFormulaireRecuperationCompte(): void {
        if(ConnexionUtilisateur::estConnecte()) {
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Récupérer mon compte",
            "cheminVueBody" => "utilisateur/formulaireRecuperationCompte.php"
        ]);
    }

    public static function recupererCompte(): void {
        if(ConnexionUtilisateur::estConnecte()) {
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        if (!self::issetAndNotNull(["email"])) {
            MessageFlash::ajouter("warning", "Adresse email manquante");
            self::redirection("utilisateur", "afficherFormulaireRecuperationCompte");
        }
        $repository = new UtilisateurRepository();
        $utilisateurs = $repository->recupererParEmail($_REQUEST["email"]);
        if(empty($utilisateurs)) {
            MessageFlash::ajouter("warning", "Aucun compte associé à cette adresse email");
            self::redirection("utilisateur", "afficherFormulaireRecuperationCompte");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Récupérer mon compte",
            "cheminVueBody" => "utilisateur/resultatRecuperationCompte.php",
            "utilisateurs" => $utilisateurs
        ]);
    }
}