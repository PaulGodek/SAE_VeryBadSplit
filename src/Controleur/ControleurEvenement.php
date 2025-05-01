<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\HTTP\Cookie;
use App\VeryBadSplit\Modele\Repository\DepenseRepository;
use App\VeryBadSplit\Modele\Repository\EvenementRepository;
use App\VeryBadSplit\Modele\Repository\UtilisateurRepository;
use Symfony\Component\Routing\Attribute\Route;

class ControleurEvenement extends ControleurGenerique
{
    #[Route(path: "/evenements/{codeEvenement}", name:"Evenement", requirements: ['codeEvenement' => '[a-zA-Z0-9]{64}'])] 
    public static function afficherEvenement(string $codeEvenement) : void
    {
        $evenementRepository = new EvenementRepository();

        $evenement = $evenementRepository->recupererParCodeSecret($codeEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("warning", "Evenement inexistant");
            self::redirection("");
        }

        $dettes = [];
        $coutTotal = 0;
        foreach ($evenement->getMembres() as $membre) {
            $dettes[$membre->getLogin()] = [];
            foreach ($evenement->getMembres() as $membreBis) {
                if($membre->getLogin() !== $membreBis->getLogin()) {
                    $dettes[$membre->getLogin()][$membreBis->getLogin()] = ["membre" => $membreBis, "montant" => 0];
                }
            }
        }
        foreach ($evenement->getDepenses() as $depense) {
            $coutTotal += $depense->getMontant();
            $payeur = $depense->getPayeur();
            $participants = $depense->getParticipants();
            $montantAPayerParPersonne = $depense->getMontant() / count($participants);
            foreach ($participants as $participant) {
                if($participant->getLogin() !== $payeur->getLogin()) {
                    $dettes[$participant->getLogin()][$payeur->getLogin()]["montant"] += $montantAPayerParPersonne;
                }
            }
        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => $evenement->getTitre(),
            "cheminVueBody" => "evenement/evenement.php",
            "evenement" => $evenement,
            "coutTotal" => $coutTotal,
            "dettes" => $dettes,
        ]);
    }

    #[Route(path: "/evenements", name: "MesEvenements")]
    public static function afficherListeMesEvenements() : void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $repository = new EvenementRepository();
        $login = ConnexionUtilisateur::getLoginUtilisateurConnecte();
        $evenements = $repository->recupererEvenementsUtilisateur($login);
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Liste des événements de $login",
            "cheminVueBody" => "evenement/listeEvenementsUtilisateur.php",
            "evenements" => $evenements
        ]);
    }

    #[Route(path: "/evenements/creation", name: "FormulaireCreationEvenement", methods: ['GET'])]
    public static function afficherFormulaireCreationEvenement(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un événement",
            "cheminVueBody" => "evenement/formulaireCreationEvenement.php",
        ]);
    }

    #[Route(path: "/evenements/creation", name: "CreationEvenement", methods: ['POST'])]
    public static function creerEvenement(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }

        if(!self::issetAndNotNull(["nomEvenement"])) {
            MessageFlash::ajouter("danger", "Le nom de l'événement est manquant");
            self::redirection("evenements/creation");
        }
        /**
         * @var Utilisateur $utilisateur
         */
        $utilisateurRepository = new UtilisateurRepository();
        $utilisateur = $utilisateurRepository->recuperer(ConnexionUtilisateur::getLoginUtilisateurConnecte());

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
                titre: $_REQUEST["nomEvenement"],
                date: new \DateTime(),
                proprietaire: $utilisateur,
                membres: [$utilisateur]
            ),
            participants: [$utilisateur]
        );

        if ($depenseRepository->ajouter($object)) {
            $codeSecret = $object->getEvenement()->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        } else {
            MessageFlash::ajouter("warning", "Une erreur est survenue lors de la création de l'événement.");
            self::redirection("evenements/creation");
        }
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "FormulaireMiseAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireMiseAJourEvenement(int $idEvenement): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $repository = new EvenementRepository();

        $evenement = $repository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("evenements");
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet evenement.");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Modification d'un événement",
            "cheminVueBody" => "evenement/formulaireMiseAJourEvenement.php",
            "evenement" => $evenement
        ]);
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "mettreAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public static function mettreAJourEvenement(int $idEvenement): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $repository = new EvenementRepository();

        /**
         * @var Evenement $evenement
         */
        $evenement = $repository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("evenements");
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
        }
        if(!self::issetAndNotNull(["nomEvenement"])) {
            MessageFlash::ajouter("danger", "Nom de l'évenement manquant");
            self::redirection("evenements/modifier/$idEvenement");
        }

        $evenement->setTitre($_REQUEST["nomEvenement"]);
        $repository->mettreAJour($evenement);
        $codeSecret = $evenement->getCodeSecret();
        self::redirection("evenements/$codeSecret");
    }

    #[Route(path: "/evenements/supprimer/{idEvenement}", name: "SupprimerEvenement",
        requirements: ['idEvenement' => '\d+'])]
    public static function supprimerEvenement($idEvenement): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $repository = new EvenementRepository();
        /**
         * @var Evenement $evenement
         */
        $evenement = $repository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("evenements");
        }
        if(!$evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'êtes pas propriétaire de cet événement");
            self::redirection("evenements");
        }

        if($repository->compterNombreEvenementProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte()) == 1) {
            MessageFlash::ajouter("danger", "Vous ne pouvez pas supprimer cet événement car cela entrainera la supression du compte");
            self::redirection("evenements");
        }

        $repository->supprimer($idEvenement);
        MessageFlash::ajouter("success", "Evenement supprimé");
        self::redirection("evenements");
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "afficherFormulaireAjoutMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireAjoutMembre(int $idEvenement): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $repository = new EvenementRepository();
        $evenement = $repository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("");
        }
        if(!$evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'êtes pas propriétaire de cet événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }

        $utilisateurRepository = new UtilisateurRepository();
        $utilisateurs = $utilisateurRepository->recupererUtilisateursOrdonnesPrenomNom();
        $filtredUtilisateurs = array_filter($utilisateurs, function ($u) use ($evenement) {return !$evenement->estMembre($u->getLogin());});

        if(empty($filtredUtilisateurs)) {
            MessageFlash::ajouter("warning", "Il n'est pas possible d'ajouter plus de membre à cet événement.");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un membre",
            "cheminVueBody" => "evenement/formulaireAjoutMembreEvenement.php",
            "evenement" => $evenement,
            "utilisateurs" => $filtredUtilisateurs
        ]);
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "ajouterMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public static function ajouterMembre(int $idEvenement): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $repository = new EvenementRepository();
        $evenement = $repository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("");
        }
        if(!$evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'êtes pas propriétaire de cet événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }
        if(!self::issetAndNotNull(["login"])) {
            MessageFlash::ajouter("danger", "Login du membre à ajouter manquant");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }

        $utilisateurRepository = new UtilisateurRepository();
        $utilisateur = $utilisateurRepository->recuperer($_REQUEST["login"]);
        if(!$utilisateur) {
            MessageFlash::ajouter("danger", "Utlisateur inexistant");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }
        if($evenement->estMembre($utilisateur->getLogin())) {
            MessageFlash::ajouter("warning", "Ce membre est déjà membre de l'événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }

        $membres = $evenement->getMembres();
        $membres[] = $utilisateur;
        $evenement->setMembres($membres);
        $repository->mettreAJour($evenement);
        $codeSecret = $evenement->getCodeSecret();
        self::redirection("evenements/$codeSecret");
    }

    #[Route(path: "/evenements/quitter/{idEvenement}", name: "QuitterEvenement", requirements: ['idEvenement' => '\d+'])]
    public static function quitterEvenement(string $idEvenement): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $repository = new EvenementRepository();
        $evenement = $repository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("");
        }

        $utilisateurRepository = new UtilisateurRepository();
        $utilisateur = $utilisateurRepository->recuperer(ConnexionUtilisateur::getLoginUtilisateurConnecte());

        if($evenement->estProprietaire($utilisateur->getLogin())) {
            MessageFlash::ajouter("danger", "Vous ne pouvez pas quitter cet événement");
            self::redirection("evenements");
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'appartenez pas à cet événement");
            self::redirection("evenements");
        }

        $membres = array_filter($evenement->getMembres(), function ($u) use ($utilisateur) {return $u->getLogin() !== $utilisateur->getLogin();});
        $evenement->setMembres($membres);
        $repository->mettreAJour($evenement);
        $depensesRepository = new DepenseRepository();
        $login = ConnexionUtilisateur::getLoginUtilisateurConnecte();
        foreach ($evenement->getDepenses() as $depense) {
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
        self::redirection("evenements");
    }

    #[Route(path: "/evenements/supprimerMembre/{idEvenement}/{login}", name: "SupprimerMembre")]
    public static function supprimerMembre(int $idEvenement, string $login): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $repository = new EvenementRepository();
        $evenement = $repository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("");
        }

        if(!$evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'êtes pas propriétaire de cet événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }

        $utilisateurRepository = new UtilisateurRepository();
        $utilisateur = $utilisateurRepository->recuperer($login);

        if(!$utilisateur) {
            MessageFlash::ajouter("danger", "Utlisateur inexistant");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }
        if($evenement->estProprietaire($utilisateur->getLogin())) {
            MessageFlash::ajouter("danger", "Vous ne pouvez pas vous supprimer de cet événement.");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }
        if(!$evenement->estMembre($utilisateur->getLogin())) {
            MessageFlash::ajouter("danger", "Cet utilisateur n'est pas membre de cet événemment.");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }

        $membres = array_filter($evenement->getMembres(), function ($u) use ($utilisateur) {return $u->getLogin() !== $utilisateur->getLogin();});
        $evenement->setMembres($membres);
        $repository->mettreAJour($evenement);

        $depensesRepository = new DepenseRepository();
        foreach ($evenement->getDepenses() as $depense) {
            if($depense->estPayeur($utilisateur->getLogin())) {
                $depensesRepository->supprimer($depense->getId());
            }
            else if($depense->estParticipant($utilisateur->getLogin())) {
                $participants = array_filter($depense->getParticipants(), function ($u) use ($utilisateur) {return $u->getLogin() !== $utilisateur->getLogin();});
                if(empty($participants)) {
                    $depensesRepository->supprimer($depense->getId());
                }
                else {
                    $depense->setParticipants($participants);
                    $depensesRepository->mettreAJour($depense);
                }
            }
        }
        $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
    }
}