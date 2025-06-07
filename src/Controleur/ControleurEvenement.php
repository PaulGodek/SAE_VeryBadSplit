<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\EvenementServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Attribute\Route;

class ControleurEvenement extends ControleurGenerique
{

    public function __construct(
        ContainerInterface $container,
        private EvenementServiceInterface $evenementService,
        private ConnexionUtilisateur $connexionUtilisateur)
    {
        parent::__construct($container);
    }
    #[Route(path: "/evenements/{codeEvenement}", name:"Evenement", requirements: ['codeEvenement' => '[a-zA-Z0-9]{64}'])]
    public function afficherEvenement(string $codeEvenement): void
    {
        try {
            $resultat = $this->evenementService->recupererEvenementAvecDettes($codeEvenement);
            $evenement = $resultat["evenement"];
            $dettes = $resultat["dettes"];
            $coutTotal = $resultat["coutTotal"];
        } catch (ServiceException $e) {
            $this->gererException($e, "warning");
        }

        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => $evenement->getTitre(),
            "cheminVueBody" => "evenement/evenement.php",
            "evenement" => $evenement,
            "coutTotal" => $coutTotal,
            "dettes" => $dettes,
        ]);
    }

    #[Route(path: "/evenements", name: "MesEvenements")]
    public function afficherListeMesEvenements(): void
    {
        $login = $this->connexionUtilisateur->getLoginUtilisateurConnecte() ?? null;
        try {
            $evenements = $this->evenementService->recupererEvenementsUtilisateur($login);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }
        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Liste des événements de $login",
            "cheminVueBody" => "evenement/listeEvenementsUtilisateur.php",
            "evenements" => $evenements
        ]);
    }

    #[Route(path: "/evenements/creation", name: "FormulaireCreationEvenement", methods: ['GET'])]
    public function afficherFormulaireCreationEvenement(): void {
        try {
            $this->evenementService->verifierConnexion();
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }
        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un événement",
            "cheminVueBody" => "evenement/formulaireCreationEvenement.php",
        ]);
    }

    #[Route(path: "/evenements/creation", name: "CreationEvenement", methods: ['POST'])]
    public function creerEvenement(): void
    {
        $nomEvenement = $_REQUEST["nomEvenement"] ?? null;
        $loginUtilisateur = $this->connexionUtilisateur->getLoginUtilisateurConnecte() ?? null;
        
        try {
            $codeSecret = $this->evenementService->creerEvenement($nomEvenement, $loginUtilisateur);
        } catch (ServiceException $e) {
            $this->gererException($e);
        }
        
        MessageFlash::ajouter("success", "Événement créé avec succès");
        $this->redirection("evenements/$codeSecret");
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "FormulaireMiseAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public function afficherFormulaireMiseAJourEvenement(int $idEvenement): void
    {
        try {
            $evenement = $this->evenementService->verifierAccesEvenement($idEvenement);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }

        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Modification d'un événement",
            "cheminVueBody" => "evenement/formulaireMiseAJourEvenement.php",
            "evenement" => $evenement
        ]);
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "mettreAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public function mettreAJourEvenement(int $idEvenement): void
    {
        $nomEvenement = $_REQUEST["nomEvenement"] ?? null;
        try {
            $codeSecret = $this->evenementService->mettreAJourEvenement($idEvenement, $nomEvenement);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }
        
        MessageFlash::ajouter("success", "Événement mis à jour avec succès.");
        $this->redirection("evenements/$codeSecret");
    }

    #[Route(path: "/evenements/supprimer/{idEvenement}", name: "SupprimerEvenement",
        requirements: ['idEvenement' => '\d+'])]
    public function supprimerEvenement(int $idEvenement): void
    {
        try {
            $this->evenementService->supprimerEvenement($idEvenement);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }
        MessageFlash::ajouter("success", "Événement supprimé avec succès");
        $this->redirection("evenements");
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "afficherFormulaireAjoutMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public function afficherFormulaireAjoutMembre(int $idEvenement): void
    {
        try {
            $resultat = $this->evenementService->recupererUtilisateursPourAjout($idEvenement);
            $evenement = $resultat["evenement"];
            $utilisateurs = $resultat["utilisateurs"];
        } catch (ServiceException $e) {
            MessageFlash::ajouter($e->getTypeMessageFlash(), $e->getMessage());
            $this->redirection($e->getRedirectionUrl());
        }

        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Ajout d'un membre",
            "cheminVueBody" => "evenement/formulaireAjoutMembreEvenement.php",
            "evenement" => $evenement,
            "utilisateurs" => $utilisateurs
        ]);
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "ajouterMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public function ajouterMembre(int $idEvenement): void
    {
        $loginUtilisateur = $_REQUEST["login"] ?? null;

        try {
            $codeSecret = $this->evenementService->ajouterMembre($idEvenement, $loginUtilisateur);
        } catch (ServiceException $e) {
            $this->gererException($e);
        }
        
        MessageFlash::ajouter("success", "Membre ajouté avec succès.");
        $this->redirection("evenements/$codeSecret");
    }

    #[Route(path: "/evenements/quitter/{idEvenement}", name: "QuitterEvenement", requirements: ['idEvenement' => '\d+'])]
    public function quitterEvenement(int $idEvenement): void
    {
        try {
            $this->evenementService->quitterEvenement($idEvenement);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Vous avez quitté l'événement avec succès.");
        $this->redirection("evenements");
    }

    #[Route(path: "/evenements/supprimerMembre/{idEvenement}/{login}", name: "SupprimerMembre")]

    public function supprimerMembre(int $idEvenement, string $login): void
    {
        try {
            $codeSecret = $this->evenementService->supprimerMembre($idEvenement, $login);
        } catch (ServiceException $e) {
            $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Membre supprimé avec succès.");
        $this->redirection("evenements/$codeSecret");
    }
}