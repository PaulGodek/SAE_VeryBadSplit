<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\HTTP\Cookie;
use App\VeryBadSplit\Modele\Repository\DepenseRepository;
use App\VeryBadSplit\Modele\Repository\EvenementRepository;
use App\VeryBadSplit\Modele\Repository\UtilisateurRepository;

class ControleurEvenement extends ControleurGenerique
{
    public static function afficherEvenement() : void
    {
        if(!self::issetAndNotNull(["codeEvenement"])) {
            MessageFlash::ajouter("warning", "Code d'évenement manquant");
            self::redirection("base", "accueil");
        }
        $code = $_REQUEST["codeEvenement"];
        $evenementRepository = new EvenementRepository();

        $evenement = $evenementRepository->recupererParCodeSecret($code);
        if(!$evenement) {
            MessageFlash::ajouter("warning", "Evenement inexistant");
            self::redirection("base", "accueil");
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


    public static function afficherListeMesEvenements() : void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
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

    public static function afficherFormulaireCreationEvenement(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un événement",
            "cheminVueBody" => "evenement/formulaireCreationEvenement.php",
        ]);
    }

    public static function creerEvenement(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }

        if(!self::issetAndNotNull(["nomEvenement"])) {
            MessageFlash::ajouter("danger", "Le nom de l'événement est manquant");
            self::redirection("evenement", "afficherFormulaireCreationEvenement");
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
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $object->getEvenement()->getCodeSecret()]);
        }
        else {
            MessageFlash::ajouter("warning", "Une erreur est survenue lors de la création de l'événement.");
            self::redirection("utilisateur", "afficherFormulaireCreation");
        }
    }

    public static function afficherFormulaireMiseAJourEvenement(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idEvenement"])) {
            MessageFlash::ajouter("danger", "Identifiant d'événement manquant");
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        $repository = new EvenementRepository();

        $evenement = $repository->recuperer($_REQUEST["idEvenement"]);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet evenement.");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Modification d'un événement",
            "cheminVueBody" => "evenement/formulaireMiseAJourEvenement.php",
            "evenement" => $evenement
        ]);
    }

    public static function mettreAJourEvenement(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idEvenement"])) {
            MessageFlash::ajouter("danger", "Identifiant d'événement manquant");
            self::redirection("utilisateur", "afficherListeMesEvenements");
        }
        $repository = new EvenementRepository();

        /**
         * @var Evenement $evenement
         */
        $evenement = $repository->recuperer($_REQUEST["idEvenement"]);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("utilisateur", "afficherListeMesEvenements");
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
        }
        if(!self::issetAndNotNull(["nomEvenement"])) {
            MessageFlash::ajouter("danger", "Nom de l'évenement manquant");
            self::redirection("utilisateur", "afficherFormulaireMiseAJourEvenement", ["idEvenement" => $_REQUEST["idEvenement"]]);
        }

        $evenement->setTitre($_REQUEST["nomEvenement"]);
        $repository->mettreAJour($evenement);
        self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
    }

    public static function supprimerEvenement(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idEvenement"])) {
            MessageFlash::ajouter("danger", "Identifiant d'événement manquant");
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        $repository = new EvenementRepository();
        $idEvenement = $_REQUEST["idEvenement"];
        /**
         * @var Evenement $evenement
         */
        $evenement = $repository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        if(!$evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'êtes pas propriétaire de cet événement");
            self::redirection("evenement", "afficherListeMesEvenements");
        }

        if($repository->compterNombreEvenementProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte()) == 1) {
            MessageFlash::ajouter("danger", "Vous ne pouvez pas supprimer cet événement car cela entrainera la supression du compte");
            self::redirection("evenement", "afficherListeMesEvenements");
        }

        $repository->supprimer($idEvenement);
        MessageFlash::ajouter("success", "Evenement supprimé");
        self::redirection("evenement", "afficherListeMesEvenements");
    }

    public static function afficherFormulaireAjoutMembre(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idEvenement"])) {
            MessageFlash::ajouter("danger", "Identifiant de l'événement manquant");
            self::redirection("base", "accueil");
        }
        $repository = new EvenementRepository();
        $evenement = $repository->recuperer($_REQUEST["idEvenement"]);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("base", "accueil");
        }
        if(!$evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'êtes pas propriétaire de cet événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }

        $utilisateurRepository = new UtilisateurRepository();
        $utilisateurs = $utilisateurRepository->recupererUtilisateursOrdonnesPrenomNom();
        $filtredUtilisateurs = array_filter($utilisateurs, function ($u) use ($evenement) {return !$evenement->estMembre($u->getLogin());});

        if(empty($filtredUtilisateurs)) {
            MessageFlash::ajouter("warning", "Il n'est pas possible d'ajouter plus de membre à cet événement.");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }

        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un membre",
            "cheminVueBody" => "evenement/formulaireAjoutMembreEvenement.php",
            "evenement" => $evenement,
            "utilisateurs" => $filtredUtilisateurs
        ]);
    }

    public static function ajouterMembre(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idEvenement"])) {
            MessageFlash::ajouter("danger", "Identifiant de l'événement manquant");
            self::redirection("base", "accueil");
        }
        $repository = new EvenementRepository();
        $evenement = $repository->recuperer($_REQUEST["idEvenement"]);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("base", "accueil");
        }
        if(!$evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'êtes pas propriétaire de cet événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        if(!self::issetAndNotNull(["login"])) {
            MessageFlash::ajouter("danger", "Login du membre à ajouter manquant");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }

        $utilisateurRepository = new UtilisateurRepository();
        $utilisateur = $utilisateurRepository->recuperer($_REQUEST["login"]);
        if(!$utilisateur) {
            MessageFlash::ajouter("danger", "Utlisateur inexistant");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        if($evenement->estMembre($utilisateur->getLogin())) {
            MessageFlash::ajouter("warning", "Ce membre est déjà membre de l'événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }

        $membres = $evenement->getMembres();
        $membres[] = $utilisateur;
        $evenement->setMembres($membres);
        $repository->mettreAJour($evenement);
        self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
    }

    public static function quitterEvenement(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idEvenement"])) {
            MessageFlash::ajouter("danger", "Identifiant de l'événement manquant");
            self::redirection("base", "accueil");
        }
        $repository = new EvenementRepository();
        $evenement = $repository->recuperer($_REQUEST["idEvenement"]);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("base", "accueil");
        }

        $utilisateurRepository = new UtilisateurRepository();
        $utilisateur = $utilisateurRepository->recuperer(ConnexionUtilisateur::getLoginUtilisateurConnecte());

        if($evenement->estProprietaire($utilisateur->getLogin())) {
            MessageFlash::ajouter("danger", "Vous ne pouvez pas quitter cet événement");
            self::redirection("evenement", "afficherListeMesEvenements");
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'appartenez pas à cet événement");
            self::redirection("evenement", "afficherListeMesEvenements");
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
        self::redirection("evenement", "afficherListeMesEvenements");
    }

    public static function supprimerMembre(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idEvenement"])) {
            MessageFlash::ajouter("danger", "Identifiant de l'événement manquant");
            self::redirection("base", "accueil");
        }
        $repository = new EvenementRepository();
        $evenement = $repository->recuperer($_REQUEST["idEvenement"]);
        if(!$evenement) {
            MessageFlash::ajouter("danger", "Evenement inexistant");
            self::redirection("base", "accueil");
        }

        if(!$evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'êtes pas propriétaire de cet événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        if(!self::issetAndNotNull(["login"])) {
            MessageFlash::ajouter("danger", "Login du membre à supprimer manquant");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }

        $utilisateurRepository = new UtilisateurRepository();
        $utilisateur = $utilisateurRepository->recuperer($_REQUEST["login"]);

        if(!$utilisateur) {
            MessageFlash::ajouter("danger", "Utlisateur inexistant");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        if($evenement->estProprietaire($utilisateur->getLogin())) {
            MessageFlash::ajouter("danger", "Vous ne pouvez pas vous supprimer de cet événement.");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        if(!$evenement->estMembre($utilisateur->getLogin())) {
            MessageFlash::ajouter("danger", "Cet utilisateur n'est pas membre de cet événemment.");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
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
        self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
    }
}