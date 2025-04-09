<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\Repository\DepenseRepository;
use App\VeryBadSplit\Modele\Repository\EvenementRepository;
use App\VeryBadSplit\Modele\Repository\UtilisateurRepository;
use DateTime;

class ControleurDepense extends ControleurGenerique
{
    public static function afficherFormulaireCreationDepense(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idEvenement"])) {
            MessageFlash::ajouter("warning", "Identifiant d'événement manquant");
            self::redirection("utilisateur", "afficherListeMesEvenements");
        }
        $evenementRepository = new EvenementRepository();

        $evenement = $evenementRepository->recuperer($_REQUEST["idEvenement"]);
        if(!$evenement) {
            MessageFlash::ajouter("warning", "Evenement inexistant");
            self::redirection("utilisateur", "afficherListeMesEvenements");
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'une dépense",
            "cheminVueBody" => "depense/formulaireCreationDepense.php",
            "evenement" => $evenement,
        ]);
    }

    public static function creerDepense(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idEvenement"])) {
            MessageFlash::ajouter("warning", "Identifiant d'événement manquant");
            self::redirection("base", "accueil");
        }
        $evenementRepository = new EvenementRepository();
        $evenement = $evenementRepository->recuperer($_REQUEST["idEvenement"]);
        if(!$evenement) {
            MessageFlash::ajouter("warning", "Evenement inexistant");
            self::redirection("base", "accueil");
        }
        if(!self::issetAndNotNull(["titre", "montant", "payeur", "participants"])) {
            MessageFlash::ajouter("danger", "Attributs manquants");
            self::redirection("depense", "afficherFormulaireCreationDepense", ["idEvenement" => $_REQUEST["idEvenement"]]);
        }
        if(empty($_REQUEST["participants"])) {
            MessageFlash::ajouter("danger", "Il faut au moins un participant.");
            self::redirection("depense", "afficherFormulaireCreationDepense", ["idEvenement" => $_REQUEST["idEvenement"]]);
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        $participants = [];
        $utilisateurRepository = new UtilisateurRepository();

        $payeur = $utilisateurRepository->recuperer($_REQUEST["payeur"]);
        if(!$payeur) {
            MessageFlash::ajouter("danger", "Le payeur n'existe pas");
            self::redirection("depense", "afficherFormulaireCreationDepense", ["idEvenement" => $_REQUEST["idEvenement"]]);
        }
        if(!$evenement->estMembre($payeur->getLogin())) {
            MessageFlash::ajouter("danger", "Le payeur n'est pas membre de l'événement.");
            self::redirection("depense", "afficherFormulaireCreationDepense", ["idEvenement" => $_REQUEST["idEvenement"]]);
        }

        foreach ($_REQUEST["participants"] as $loginParticipant) {
            $utilisateur = $utilisateurRepository->recuperer($loginParticipant);
            if(!$utilisateur) {
                MessageFlash::ajouter("danger", "Un des membres affecté à la dépense n'existe pas");
                self::redirection("depense", "afficherFormulaireCreationDepense", ["idEvenement" => $_REQUEST["idEvenement"]]);
            }
            if(!$evenement->estMembre($utilisateur->getLogin())) {
                MessageFlash::ajouter("danger", "Un des membres affecté à la dépense n'est pas membre de l'événement.");
                self::redirection("depense", "afficherFormulaireCreationDepense", ["idEvenement" => $_REQUEST["idEvenement"]]);
            }
            $participants[] = $utilisateur;
        }
        $depenseRepository = new DepenseRepository();
        $depense = new Depense(
            id: $depenseRepository->getNextId(),
            titre: $_REQUEST["titre"],
            date: new DateTime(),
            montant: floatval($_REQUEST["montant"]),
            payeur: $payeur,
            evenement: $evenement,
            participants: $participants
        );
        $depenseRepository->ajouter($depense);
        self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
    }

    public static function afficherFormulaireMiseAJourDepense(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idDepense"])) {
            MessageFlash::ajouter("warning", "Identifiant de la dépense manquant");
            self::redirection("base", "accueil");
        }
        $depensesRepository = new DepenseRepository();
        $depense = $depensesRepository->recuperer($_REQUEST["idDepense"]);
        if(!$depense) {
            MessageFlash::ajouter("warning", "Dépense inexistante");
            self::redirection("base", "accueil");
        }
        $evenement = $depense->getEvenement();
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Edition d'une dépense",
            "cheminVueBody" => "depense/formulaireMiseAJourDepense.php",
            "depense" => $depense
        ]);
    }

    public static function mettreAJourDepense(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idDepense"])) {
            MessageFlash::ajouter("warning", "Identifiant de la dépense manquant");
            self::redirection("base", "accueil");
        }
        $depensesRepository = new DepenseRepository();
        $depense = $depensesRepository->recuperer($_REQUEST["idDepense"]);
        if(!$depense) {
            MessageFlash::ajouter("warning", "Dépense inexistante");
            self::redirection("base", "accueil");
        }
        $evenement = $depense->getEvenement();

        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }

        if(!self::issetAndNotNull(["titre", "montant", "payeur", "participants"])) {
            MessageFlash::ajouter("danger", "Attributs manquants");
            self::redirection("depense", "afficherFormulaireMiseAJourDepense", ["idDepense" => $_REQUEST["idDepense"]]);
        }
        if(empty($_REQUEST["participants"])) {
            MessageFlash::ajouter("danger", "Il faut au moins un participant.");
            self::redirection("depense", "afficherFormulaireMiseAJourDepense", ["idDepense" => $_REQUEST["idDepense"]]);
        }

        $depense->setTitre($_REQUEST["titre"]);
        $depense->setMontant($_REQUEST["montant"]);


        $utilisateurRepository = new UtilisateurRepository();

        $payeur = $utilisateurRepository->recuperer($_REQUEST["payeur"]);
        if(!$payeur) {
            MessageFlash::ajouter("danger", "Le payeur n'existe pas");
            self::redirection("depense", "afficherFormulaireMiseAJourDepense", ["idDepense" => $_REQUEST["idDepense"]]);
        }

        if(!$evenement->estMembre($payeur->getLogin())) {
            MessageFlash::ajouter("danger", "Le payeur n'est pas membre de l'événement.");
            self::redirection("depense", "afficherFormulaireMiseAJourDepense", ["idDepense" => $_REQUEST["idDepense"]]);
        }
        $depense->setPayeur($payeur);

        $participants = [];
        foreach ($_REQUEST["participants"] as $loginParticipant) {
            $utilisateur = $utilisateurRepository->recuperer($loginParticipant);
            if(!$utilisateur) {
                MessageFlash::ajouter("danger", "Un des membres affecté à la dépense n'existe pas");
                self::redirection("depense", "afficherFormulaireMiseAJourDepense", ["idDepense" => $_REQUEST["idDepense"]]);
            }
            if(!$evenement->estMembre($utilisateur->getLogin())) {
                MessageFlash::ajouter("danger", "Un des membres affecté à la dépense n'est pas membre de l'événement.");
                self::redirection("depense", "afficherFormulaireMiseAJourDepense", ["idDepense" => $_REQUEST["idDepense"]]);
            }
            $participants[] = $utilisateur;
        }
        $depense->setParticipants($participants);
        $depensesRepository->mettreAJour($depense);
        self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
    }

    public static function supprimerDepense(): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("utilisateur", "afficherFormulaireConnexion");
        }
        if(!self::issetAndNotNull(["idDepense"])) {
            MessageFlash::ajouter("warning", "Identifiant de dépense manquant");
            self::redirection("base", "accueil");
        }
        $depensesRepository = new DepenseRepository();
        $idDepense = $_REQUEST["idDepense"];
        $depense = $depensesRepository->recuperer($idDepense);
        if(!$depense) {
            MessageFlash::ajouter("danger", "Dépense inexistante");
            self::redirection("base", "accueil");
        }

        $evenement = $depense->getEvenement();
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }

        if($depensesRepository->compterNombreDepensesEvenement($evenement->getId()) == 1) {
            MessageFlash::ajouter("danger", "Vous ne pouvez pas supprimer cette dépense car cela entrainera la supression de l'événement");
            self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
        }
        $depensesRepository->supprimer($idDepense);
        self::redirection("evenement", "afficherEvenement", ["codeEvenement" => $evenement->getCodeSecret()]);
    }
}