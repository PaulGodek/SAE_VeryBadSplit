<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\Repository\DepenseRepository;
use App\VeryBadSplit\Modele\Repository\EvenementRepository;
use App\VeryBadSplit\Modele\Repository\UtilisateurRepository;
use DateTime;
use Symfony\Component\Routing\Attribute\Route;

class ControleurDepense extends ControleurGenerique
{

    #[Route(path: "/evenements/nouvelleDepense/{idEvenement}", name: "afficherFormulaireCreationDepense",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireCreationDepense(int $idEvenement): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $evenementRepository = new EvenementRepository();

        $evenement = $evenementRepository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("warning", "Evenement inexistant");
            self::redirection("evenements");
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'une dépense",
            "cheminVueBody" => "depense/formulaireCreationDepense.php",
            "evenement" => $evenement,
        ]);
    }

    #[Route(path: "/evenements/nouvelleDepense/{idEvenement}", name: "creerDepense",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public static function creerDepense(int $idEvenement): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $evenementRepository = new EvenementRepository();
        $evenement = $evenementRepository->recuperer($idEvenement);
        if(!$evenement) {
            MessageFlash::ajouter("warning", "Evenement inexistant");
            self::redirection("");
        }
        if(!self::issetAndNotNull(["titre", "montant", "payeur", "participants"])) {
            MessageFlash::ajouter("danger", "Attributs manquants");
            self::redirection("/evenements/nouvelleDepense/$idEvenement");
        }
        if(empty($_REQUEST["participants"])) {
            MessageFlash::ajouter("danger", "Il faut au moins un participant.");
            self::redirection("/evenements/nouvelleDepense/$idEvenement");
        }
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }
        $participants = [];
        $utilisateurRepository = new UtilisateurRepository();

        $payeur = $utilisateurRepository->recuperer($_REQUEST["payeur"]);
        if(!$payeur) {
            MessageFlash::ajouter("danger", "Le payeur n'existe pas");
            self::redirection("/evenements/nouvelleDepense/$idEvenement");
        }
        if(!$evenement->estMembre($payeur->getLogin())) {
            MessageFlash::ajouter("danger", "Le payeur n'est pas membre de l'événement.");
            self::redirection("/evenements/nouvelleDepense/$idEvenement");
        }

        foreach ($_REQUEST["participants"] as $loginParticipant) {
            $utilisateur = $utilisateurRepository->recuperer($loginParticipant);
            if(!$utilisateur) {
                MessageFlash::ajouter("danger", "Un des membres affecté à la dépense n'existe pas");
                self::redirection("/evenements/nouvelleDepense/$idEvenement");
            }
            if(!$evenement->estMembre($utilisateur->getLogin())) {
                MessageFlash::ajouter("danger", "Un des membres affecté à la dépense n'est pas membre de l'événement.");
                self::redirection("/evenements/nouvelleDepense/$idEvenement");
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
        $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
    }

    #[Route(path: "/depense/modifier/{idDepense}", name: "afficherFormulaireMiseAJourDepense",
        requirements: ['idDepense' => '\d+'], methods: ['GET'])]
    public static function afficherFormulaireMiseAJourDepense(int $idDepense): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $depensesRepository = new DepenseRepository();
        $depense = $depensesRepository->recuperer($idDepense);
        if(!$depense) {
            MessageFlash::ajouter("warning", "Dépense inexistante");
            self::redirection("");
        }
        $evenement = $depense->getEvenement();
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }
        self::afficherVue('vueGenerale.php', [
            "pagetitle" => "Edition d'une dépense",
            "cheminVueBody" => "depense/formulaireMiseAJourDepense.php",
            "depense" => $depense
        ]);
    }

    #[Route(path: "/depense/modifier/{idDepense}", name: "mettreAJourDepense", 
        requirements: ['idDepense' => '\d+'], methods: ['POST'])]
    public static function mettreAJourDepense(int $idDepense): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $depensesRepository = new DepenseRepository();
        $depense = $depensesRepository->recuperer($idDepense);
        if(!$depense) {
            MessageFlash::ajouter("warning", "Dépense inexistante");
            self::redirection("");
        }
        $evenement = $depense->getEvenement();

        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }

        if(!self::issetAndNotNull(["titre", "montant", "payeur", "participants"])) {
            MessageFlash::ajouter("danger", "Attributs manquants");
            self::redirection("depense/modifier/$idDepense");
        }
        if(empty($_REQUEST["participants"])) {
            MessageFlash::ajouter("danger", "Il faut au moins un participant.");
            self::redirection("depense/modifier/$idDepense");
        }

        $depense->setTitre($_REQUEST["titre"]);
        $depense->setMontant($_REQUEST["montant"]);


        $utilisateurRepository = new UtilisateurRepository();

        $payeur = $utilisateurRepository->recuperer($_REQUEST["payeur"]);
        if(!$payeur) {
            MessageFlash::ajouter("danger", "Le payeur n'existe pas");
            self::redirection("depense/modifier/$idDepense");
        }

        if(!$evenement->estMembre($payeur->getLogin())) {
            MessageFlash::ajouter("danger", "Le payeur n'est pas membre de l'événement.");
            self::redirection("depense/modifier/$idDepense");
        }
        $depense->setPayeur($payeur);

        $participants = [];
        foreach ($_REQUEST["participants"] as $loginParticipant) {
            $utilisateur = $utilisateurRepository->recuperer($loginParticipant);
            if(!$utilisateur) {
                MessageFlash::ajouter("danger", "Un des membres affecté à la dépense n'existe pas");
                self::redirection("depense/modifier/$idDepense");
            }
            if(!$evenement->estMembre($utilisateur->getLogin())) {
                MessageFlash::ajouter("danger", "Un des membres affecté à la dépense n'est pas membre de l'événement.");
                self::redirection("depense/modifier/$idDepense");
            }
            $participants[] = $utilisateur;
        }
        $depense->setParticipants($participants);
        $depensesRepository->mettreAJour($depense);
        $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
    }

    #[Route(path: "/depense/supprimer/{idDepense}", name: "supprimerDepense", requirements: ['idDepense' => '\d+'], methods: ['POST'])]
    public static function supprimerDepense(int $idDepense): void {
        if(!ConnexionUtilisateur::estConnecte()) {
            self::redirection("connexion");
        }
        $depensesRepository = new DepenseRepository();
        $depense = $depensesRepository->recuperer($idDepense);
        if(!$depense) {
            MessageFlash::ajouter("danger", "Dépense inexistante");
            self::redirection("");
        }

        $evenement = $depense->getEvenement();
        if(!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            MessageFlash::ajouter("danger", "Vous n'avez pas de droits d'éditions sur cet événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }

        if($depensesRepository->compterNombreDepensesEvenement($evenement->getId()) == 1) {
            MessageFlash::ajouter("danger", "Vous ne pouvez pas supprimer cette dépense car cela entrainera la supression de l'événement");
            $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
        }
        $depensesRepository->supprimer($idDepense);
        $codeSecret = $evenement->getCodeSecret();
            self::redirection("evenements/$codeSecret");
    }
}