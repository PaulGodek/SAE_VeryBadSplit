<?php

namespace App\VeryBadSplit\Service;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Validator;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\Repository\Interface\DepenseRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\EvenementRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\UtilisateurRepositoryInterface;
use App\VeryBadSplit\Service\Interface\EvenementServiceInterface;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\DepenseServiceInterface;
use DateTime;

class DepenseService extends GeneriqueService implements DepenseServiceInterface
{
    private DepenseRepositoryInterface $depenseRepository;
    private UtilisateurRepositoryInterface $utilisateurRepository;
    private EvenementRepositoryInterface $evenementRepository;
    private EvenementService $evenementService ;

    public function __construct(
        DepenseRepositoryInterface $depenseRepository,
        UtilisateurRepositoryInterface $utilisateurRepository,
        EvenementRepositoryInterface $evenementRepository,
        EvenementServiceInterface $evenementService
    ) {
        $this->depenseRepository = $depenseRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->evenementRepository = $evenementRepository;
        $this->evenementService = $evenementService;
    }

    /**
     * Vérifie l'accès à une dépense en fonction de son ID.
     *
     * @param int $idDepense L'ID de la dépense à vérifier.
     * @return Depense L'objet dépense si l'accès est valide.
     * @throws ServiceException Si la dépense n'existe pas ou si l'utilisateur n'a pas les droits.
     */
    public function verifierAccesDepense(int $idDepense): Depense
    {
        GeneriqueService::verifierConnexion();
        $depense = $this->depenseRepository->recupererParClePrimaire($idDepense);
        if (!$depense) {
            throw new ServiceException("Dépense inexistante.", "");
        }

        $evenement = $this->evenementRepository->recupererParClePrimaire($depense->getEvenement()->getId());
        if (!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            $codeSecret = $evenement->getCodeSecret();
            throw new ServiceException("Vous n'avez pas de droits d'éditions sur cet événement.",
                "evenements/$codeSecret");
        }

        return $depense;
    }

    /**
     * Crée une nouvelle dépense pour un événement donné.
     *
     * @param int $idEvenement L'ID de l'événement associé.
     * @param string $titre Le titre de la dépense.
     * @param float $montant Le montant de la dépense.
     * @param string $payeur Le login du payeur.
     * @param array $loginsParticipants Les logins des participants à la dépense.
     * @return string Le code secret de l'événement associé.
     * @throws ServiceException Si des attributs sont manquants ou invalides.
     */
    public function creerDepense(int $idEvenement, string $titre, float $montant, string $payeur, array $loginsParticipants): string
    {
        $evenement = $this->evenementService->verifierAccesEvenement($idEvenement);

        if (!Validator::allNotEmpty([$titre, $montant, $payeur, $loginsParticipants])) {
            throw new ServiceException("Attributs manquants.", 
                "evenements/nouvelleDepense/$idEvenement");
        }

        if (empty($loginsParticipants)) {
            throw new ServiceException("Il faut au moins un participant.",
                "evenements/nouvelleDepense/$idEvenement");
        }

        if (!Validator::hasValideLength($titre,1,50)) {
            throw new ServiceException("La longueur du titre n'est pas valide.",
                "evenements/nouvelleDepense/$idEvenement");
        }

        $utilisateurRepository = $this->utilisateurRepository;
        $payeur = $utilisateurRepository->recupererParClePrimaire($payeur);
        if (!$payeur || !$evenement->estMembre($payeur->getLogin())) {
            throw new ServiceException("Le payeur n'existe pas ou n'est pas membre de l'événement.", 
                "evenements/nouvelleDepense/$idEvenement");
        }

        $participants = [];
        foreach ($loginsParticipants as $loginParticipant) {
            $utilisateur = $utilisateurRepository->recupererParClePrimaire($loginParticipant);
            if (!$utilisateur || !$evenement->estMembre($utilisateur->getLogin())) {
                throw new ServiceException("Un des participants n'existe pas ou n'est pas membre de l'événement.", 
                    "evenements/nouvelleDepense/$idEvenement");
            }
            $participants[] = $utilisateur;
        }

        $depenseRepository = $this->depenseRepository;
        $depense = new Depense(
            id: $depenseRepository->getNextId(),
            titre: $titre,
            date: new DateTime(),
            montant: floatval($montant),
            payeur: $payeur,
            evenement: $evenement,
            participants: $participants
        );


        $depenseRepository->ajouter($depense);
        foreach ($participants as $participant) {
            if(!$depense->estParticipant($participant->getLogin())) {
                $depenseRepository->ajouterJointure($depense, $participant->getLogin());
            }

        }
        $this->depenseRepository->ajouterJointure($depense,$depense->getPayeur()->getLogin());

        return $evenement->getCodeSecret();
    }

    /**
     * Met à jour une dépense existante.
     *
     * @param int $idDepense L'ID de la dépense à mettre à jour.
     * @param string $titre Le nouveau titre de la dépense.
     * @param float $montant Le nouveau montant de la dépense.
     * @param string $payeurLogin Le login du nouveau payeur.
     * @param array $loginsParticipants Les logins des nouveaux participants.
     * @return string Le code secret de l'événement associé.
     * @throws ServiceException Si des attributs sont manquants ou invalides.
     */
    public function mettreAJourDepense(int $idDepense, string $titre, float $montant, string $payeurLogin, array $loginsParticipants): string
    {
        GeneriqueService::verifierConnexion();
        
        $depenseRepository = $this->depenseRepository;
        $depense = $depenseRepository->recupererParClePrimaire($idDepense);

        if (!$depense) {
            throw new ServiceException("Dépense inexistante.",
                "");
        }

        $evenement = $depense->getEvenement();
        $this->evenementService->verifierDroitsEvenement($evenement);

        if (!Validator::allNotEmpty([$titre, $montant, $payeurLogin, $loginsParticipants])) {
            throw new ServiceException("Attributs manquants.",
                "depense/modifier/$idDepense");
        }

        if (empty($loginsParticipants)) {
            throw new ServiceException("Il faut au moins un participant.",
                "depense/modifier/$idDepense");
        }

        if (!Validator::hasValideLength($titre,1,50)) {
            throw new ServiceException("La longueur du titre n'est pas valide.",
                "depense/modifier/$idDepense");
        }

        $utilisateurRepository = $this->utilisateurRepository;
        $payeur = $utilisateurRepository->recupererParClePrimaire($payeurLogin);
        if (!$payeur || !$evenement->estMembre($payeur->getLogin())) {
            throw new ServiceException("Le payeur n'existe pas ou n'est pas membre de l'événement.",
                "depense/modifier/$idDepense");
        }

        $participants = [];
        foreach ($loginsParticipants as $loginParticipant) {
            $utilisateur = $utilisateurRepository->recupererParClePrimaire($loginParticipant);
            if (!$utilisateur || !$evenement->estMembre($utilisateur->getLogin())) {
                throw new ServiceException("Un des participants n'existe pas ou n'est pas membre de l'événement.",
                    "depense/modifier/$idDepense");
            }
            $participants[] = $utilisateur;
        }

        foreach ($participants as $participant) {
            if(!$depense->estParticipant($participant->getLogin())) {
                $depenseRepository->ajouterJointure($depense, $participant->getLogin());
            }

        }
        $depense->setTitre($titre);
        $depense->setMontant($montant);
        $depense->setpayeur($payeur);
        $depense->setParticipants($participants);

        $depenseRepository->mettreAJour($depense);

        return $evenement->getCodeSecret();
    }

    /**
     * Supprime une dépense existante.
     *
     * @param int $idDepense L'ID de la dépense à supprimer.
     * @return string Le code secret de l'événement associé.
     * @throws ServiceException Si la dépense n'existe pas ou si sa suppression entraîne des problèmes.
     */
    public function supprimerDepense( $idDepense): string
    {
        $this->verifierConnexion();

        $depenseRepository = $this->depenseRepository;
        $depense = $depenseRepository->recupererParClePrimaire($idDepense);

        if (!$depense) {
            throw new ServiceException("Dépense inexistante.",
                "");
        }

        $evenement = $this->evenementRepository->recupererParClePrimaire($depense->getEvenement()->getId());
        $this->evenementService->verifierDroitsEvenement($evenement);

        /*if ($depenseRepository->compterNombreDepensesEvenement($evenement->getId()) == 1) {
            throw new ServiceException("Vous ne pouvez pas supprimer cette dépense car cela entraînera la suppression de l'événement.",
                "evenements/" . $evenement->getCodeSecret());
        }*///non

        $depenseRepository->supprimer($idDepense);

        return $evenement->getCodeSecret();
    }

    public function recupererDepensesParEvenement(int $idEvenement): ?array{
        $this->verifierConnexion();
        $depenseRepository = $this->depenseRepository;
        $depenses = $depenseRepository->recupererParEvenement($idEvenement);
        return $depenses;
    }
}