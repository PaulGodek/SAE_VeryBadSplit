<?php

namespace App\VeryBadSplit\Controleur;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Service\DepenseService;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\EvenementServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\VeryBadSplit\Service\UtilisateurService;
use Symfony\Component\Routing\Attribute\Route;

class ControleurEvenement extends ControleurGenerique
{
    public function __construct(
        ContainerInterface $container,
        private EvenementServiceInterface $evenementService,
        private DepenseService $depenseService,)
    {
        parent::__construct($container);
    }

    #[Route(path: "/evenements/{codeEvenement}", name:"Evenement", requirements: ['codeEvenement' => '[a-zA-Z0-9]{64}'])]
    public function afficherEvenement(string $codeEvenement): Response
    {
        try {
            $resultat = $this->evenementService->recupererEvenementAvecDettes($codeEvenement);
            $evenement = $resultat["evenement"];
            $dettes = $resultat["dettes"];
            $coutTotal = $resultat["coutTotal"];
            $transactions=$resultat["transactionsOptimisees"];
            $depenses = $this->depenseService->recupererDepensesParEvenement($evenement->getId());
        } catch (ServiceException $e) {
            return $this->gererException($e, "warning");
        }

        if(is_null($depenses)){
            $depenses=[];
        }

        return $this->afficherTwig("evenement/evenement.html.twig", [
            "evenement" => $evenement,
            "depenses" => $depenses,
            "transactions" => $transactions,
            "coutTotal" => $coutTotal,
            "dettes" => $dettes,
            "loginConnecte" => ConnexionUtilisateur::getLoginUtilisateurConnecte()
        ]);
    }

    #[Route(path: "/evenements", name: "MesEvenements")]
    public function afficherListeMesEvenements(): Response
    {
        $login = ConnexionUtilisateur::getLoginUtilisateurConnecte() ?? null;
        try {
            $evenements = $this->evenementService->recupererEvenementsUtilisateur($login);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }

        return $this->afficherTwig("evenement/listeEvenementsUtilisateur.html.twig", [
            "evenements" => $evenements,
            "loginConnecte" => ConnexionUtilisateur::getLoginUtilisateurConnecte()
        ]);
    }

    #[Route(path: "/evenements/creation", name: "FormulaireCreationEvenement", methods: ['GET'])]
    public function afficherFormulaireCreationEvenement(): Response {
        try {
            $this->evenementService->verifierConnexion();
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }
        return $this->afficherTwig("evenement/formulaireCreationEvenement.html.twig");
    }

    #[Route(path: "/evenements/creation", name: "CreationEvenement", methods: ['POST'])]
    public function creerEvenement(): Response
    {
        $nomEvenement = $_REQUEST["nomEvenement"] ?? null;
        $loginUtilisateur = ConnexionUtilisateur::getLoginUtilisateurConnecte() ?? null;
        
        try {
            $codeSecret = $this->evenementService->creerEvenement($nomEvenement, $loginUtilisateur);
        } catch (ServiceException $e) {
            return $this->gererException($e);
        }
        
        MessageFlash::ajouter("success", "Événement créé avec succès");
        return $this->redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "FormulaireMiseAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public function afficherFormulaireMiseAJourEvenement(int $idEvenement): Response
    {
        try {
            $evenement = $this->evenementService->verifierAccesEvenement($idEvenement);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }

        return $this->afficherTwig("evenement/formulaireMiseAJourEvenement.html.twig", [
            "evenement" => $evenement
        ]);
    }

    #[Route(path: "/evenements/modifier/{idEvenement}", name: "mettreAJourEvenement",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public function mettreAJourEvenement(int $idEvenement): Response
    {
        $nomEvenement = $_REQUEST["nomEvenement"] ?? null;
        try {
            $codeSecret = $this->evenementService->mettreAJourEvenement($idEvenement, $nomEvenement);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }
        
        MessageFlash::ajouter("success", "Événement mis à jour avec succès.");
        return $this->redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }

    #[Route(path: "/evenements/supprimer/{idEvenement}", name: "SupprimerEvenement",
        requirements: ['idEvenement' => '\d+'])]
    public function supprimerEvenement(int $idEvenement): Response
    {
        try {
            $this->evenementService->supprimerEvenement($idEvenement);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }
        MessageFlash::ajouter("success", "Événement supprimé avec succès");
        return $this->redirection("MesEvenements");
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "afficherFormulaireAjoutMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['GET'])]
    public function afficherFormulaireAjoutMembre(int $idEvenement): Response
    {
        try {
            $resultat = $this->evenementService->recupererUtilisateursPourAjout($idEvenement);
            $evenement = $resultat["evenement"];
            $utilisateurs = $resultat["utilisateurs"];
        } catch (ServiceException $e) {
            MessageFlash::ajouter($e->getTypeMessageFlash(), $e->getMessage());
            return $this->redirection($e->getRedirectionRoute(), $e->getArguments());
        }

        return $this->afficherTwig("evenement/formulaireAjoutMembreEvenement.html.twig", [
            "evenement" => $evenement,
            "utilisateurs" => $utilisateurs
        ]);
    }

    #[Route(path: "/evenements/ajouterMembre/{idEvenement}", name: "ajouterMembre",
        requirements: ['idEvenement' => '\d+'], methods: ['POST'])]
    public function ajouterMembre(int $idEvenement): Response
    {
        $loginUtilisateur = $_REQUEST["login"] ?? null;

        try {
            $codeSecret = $this->evenementService->ajouterMembre($idEvenement, $loginUtilisateur);
        } catch (ServiceException $e) {
            return $this->gererException($e);
        }
        
        MessageFlash::ajouter("success", "Membre ajouté avec succès.");
        return $this->redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }

    #[Route(path: "/evenements/quitter/{idEvenement}", name: "QuitterEvenement", requirements: ['idEvenement' => '\d+'])]
    public function quitterEvenement(int $idEvenement): Response
    {
        try {
            $this->evenementService->quitterEvenement($idEvenement);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Vous avez quitté l'événement avec succès.");
        return $this->redirection("MesEvenements");
    }

    #[Route(path: "/evenements/supprimerMembre/{idEvenement}/{login}", name: "SupprimerMembre")]
    public function supprimerMembre(int $idEvenement, string $login): Response
    {
        try {
            $codeSecret = $this->evenementService->supprimerMembre($idEvenement, $login);
        } catch (ServiceException $e) {
            return $this->gererException($e, "danger");
        }

        MessageFlash::ajouter("success", "Membre supprimé avec succès.");
        return $this->redirection("Evenement", ["codeEvenement" => $codeSecret]);
    }
}