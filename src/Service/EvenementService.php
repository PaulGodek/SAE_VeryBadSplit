<?php

namespace App\VeryBadSplit\Service;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Lib\Validator;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\Repository\DepenseRepository;
use App\VeryBadSplit\Modele\Repository\Interface\DepenseRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\EvenementRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\UtilisateurRepositoryInterface;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\EvenementServiceInterface;
use DateTime;
use http\Message;
use Symfony\Component\HttpFoundation\Response;

class EvenementService extends GeneriqueService implements EvenementServiceInterface
{
    private EvenementRepositoryInterface $evenementRepository;
    private UtilisateurRepositoryInterface $utilisateurRepository;
    private DepenseRepositoryInterface $depenseRepository;

    public function __construct(
        EvenementRepositoryInterface $evenementRepository,
        UtilisateurRepositoryInterface $utilisateurRepository,
        DepenseRepositoryInterface $depenseRepository
    ) {
        $this->evenementRepository = $evenementRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->depenseRepository = $depenseRepository;
    }

    /**
     * Vérifie l'existence d'un événement.
     *
     * @param Evenement|null $evenement L'événement à vérifier.
     * @throws ServiceException Si l'événement n'existe pas.
     */
    public function verifierExistenceEvenement($evenement): void
    {
        if (!$evenement) {
            throw new ServiceException("Événement inexistant",
                Response::HTTP_BAD_REQUEST,
                "MesEvenements");
        }
    }

    /**
     * Vérifie les droits d'édition sur un événement.
     *
     * @param Evenement $evenement L'événement à vérifier.
     * @throws ServiceException Si l'utilisateur n'a pas les droits nécessaires.
     */
    public function verifierDroitsEvenement($evenement): void {
        if (!$evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            $codeSecret = $evenement->getCodeSecret();
            throw new ServiceException("Vous n'avez pas de droits d'éditions sur cet événement",
                Response::HTTP_FORBIDDEN,
                "Evenement", ["codeEvenement" => $codeSecret]);
        }
    }

    /**
     * Vérifie l'accès à un événement et retourne l'objet correspondant.
     *
     * @param int $idEvenement L'identifiant de l'événement.
     * @return Evenement L'objet Événement correspondant.
     * @throws ServiceException Si l'utilisateur n'est pas connecté ou n'a pas accès à l'événement.
     */
    public function verifierAccesEvenement(int $idEvenement): Evenement
    {
        GeneriqueService::verifierConnexion();

        $evenement = $this->evenementRepository->recupererParClePrimaire($idEvenement);

        self::verifierExistenceEvenement($evenement);
        self::verifierDroitsEvenement($evenement);

        return $evenement;
    }

    /**
     * Vérifie si l'utilisateur connecté est le propriétaire d'un événement et retourne l'objet correspondant.
     *
     * @param int $idEvenement L'identifiant de l'événement.
     * @return Evenement L'objet Événement correspondant.
     * @throws ServiceException Si l'utilisateur n'est pas connecté, si l'événement n'existe pas ou si l'utilisateur n'est pas le propriétaire.
     */
    public function verifierAccesProprietaireEvenement(int $idEvenement): Evenement
    {
        GeneriqueService::verifierConnexion();

        $evenement = $this->evenementRepository->recupererParClePrimaire($idEvenement);
        self::verifierExistenceEvenement($evenement);
        if (!$evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
            throw new ServiceException("Vous n'êtes pas propriétaire de cet événement",
                Response::HTTP_FORBIDDEN,
                "MesEvenements");
        }

        return $evenement;
    }

    /**
    * Récupère un événement avec les dettes associées (brutes + optimisées).
    *
    * @param string $codeEvenement Le code secret de l'événement.
     * @return array Un tableau contenant l'événement, les dettes brutes, les dettes optimisées et le coût total.
    * @throws ServiceException Si l'événement n'existe pas.
    */
    public function recupererEvenementAvecDettes(string $codeEvenement): array
    {
        $evenement = $this->evenementRepository->recupererParCodeSecret($codeEvenement);
        $this->verifierExistenceEvenement($evenement);

        $dettes = [];
        $coutTotal = 0;
        foreach ($evenement->getMembres() as $membre) {
            $dettes[$membre->getLogin()] = [];
            foreach ($evenement->getMembres() as $membreBis) {
                if ($membre->getLogin() !== $membreBis->getLogin()) {
                    $dettes[$membre->getLogin()][$membreBis->getLogin()] = [
                        "membre" => $membreBis,
                        "montant" => 0
                    ];
                }
            }
        }

        $depenses = $this->depenseRepository->recupererParEvenement($evenement->getId());
        if (!is_null($depenses)) {
            foreach ($depenses as $depense) {
                $coutTotal += $depense->getMontant();
                $payeur = $depense->getPayeur();
                $participants = $depense->getParticipants();
                $montantAPayerParPersonne = $depense->getMontant() / count($participants);

                foreach ($participants as $participant) {
                    if ($participant->getLogin() !== $payeur->getLogin()) {
                        $dettes[$participant->getLogin()][$payeur->getLogin()]["montant"] += $montantAPayerParPersonne;
                    }
                }
            }
        }

        $soldes = [];
        foreach ($evenement->getMembres() as $membre) {
            $login = $membre->getLogin();
            $totalDoit = 0;
            $totalRecu = 0;

            foreach ($dettes[$login] as $autreLogin => $info) {
                $totalDoit += $info["montant"];
            }

            foreach ($dettes as $autreLogin => $autresDettes) {
                if (isset($autresDettes[$login])) {
                    $totalRecu += $autresDettes[$login]["montant"];
                }
            }

            $soldes[$login] = $totalRecu - $totalDoit;
        }

        $transactionsOptimisees = [];

        $debit = [];
        $credit = [];

        foreach ($soldes as $login => $solde) {
            $soldeArrondi = round($solde, 2);
            if ($soldeArrondi > 0) {
                $credit[$login] = $soldeArrondi;
            } elseif ($soldeArrondi < 0) {
                $debit[$login] = $soldeArrondi;
            }
        }

        while (!empty($debit) && !empty($credit)) {
            $debiteur = array_key_first($debit);
            $creancier = array_key_first($credit);

            $montantADonner = min(abs($debit[$debiteur]), $credit[$creancier]);

            $transactionsOptimisees[] = [
                "from" => $debiteur,
                "to" => $creancier,
                "montant" => $montantADonner
            ];

            $debit[$debiteur] += $montantADonner;
            $credit[$creancier] -= $montantADonner;

            if (round($debit[$debiteur], 2) == 0) {
                unset($debit[$debiteur]);
            }
            if (round($credit[$creancier], 2) == 0) {
                unset($credit[$creancier]);
            }
        }

        // Résultat
        return [
            "evenement" => $evenement,
            "dettes" => $dettes, // Dettes brutes par participant
            "transactionsOptimisees" => $transactionsOptimisees, // Liste minimale de remboursements
            "coutTotal" => $coutTotal,
        ];
    }

    /**
     * Récupère les événements associés à l'utilisateur connecté.
     *
     * @param string|null $login Le login de l'utilisateur.
     * @return Evenement[] Un tableau des événements de l'utilisateur.
     * @throws ServiceException Si l'utilisateur n'est pas connecté.
     */
    public function recupererEvenementsUtilisateur(?string $login): array
    {
        $this->verifierConnexion();

        return $this->evenementRepository->recupererEvenementsUtilisateur($login);
    }

    /**
     * Crée un nouvel événement avec une dépense initiale.
     *
     * @param string $nomEvenement Le nom de l'événement.
     * @param string $loginUtilisateur Le login de l'utilisateur connecté.
     * @return string Le code secret de l'événement créé.
     * @throws ServiceException Si des attributs sont manquants ou invalides.
     */
    public function creerEvenement(string $nomEvenement, string $loginUtilisateur): string
    {
        $this->verifierConnexion();

        if (empty($nomEvenement)) {
            throw new ServiceException("Le nom de l'événement est manquant.",
                Response::HTTP_BAD_REQUEST,
                "FormulaireCreationEvenement", [], "danger");
        }
        if (!Validator::hasValideLength($nomEvenement,3,30)) {
            throw new ServiceException("La longueur du nom de l'événement n'est pas valide.",
                Response::HTTP_BAD_REQUEST,
                "FormulaireCreationEvenement", [], "danger");
        }

        $evenementRepository = $this->evenementRepository;
        $idEvenement = $evenementRepository->getNextId();

        $utilisateur = $this->utilisateurRepository->recupererParClePrimaire($loginUtilisateur);

        $evenement = new Evenement(
            id: $idEvenement,
            codeSecret: hash("sha256", $loginUtilisateur . $idEvenement),
            titre: $nomEvenement,
            date: new DateTime(),
            proprietaire: $utilisateur,
            membres: [$utilisateur]
        );


        if (!$evenementRepository->ajouter($evenement)) {
            throw new ServiceException("Une erreur est survenue lors de la création de l'événement",
                "FormulaireCreationEvenement", [], "warning");
        }
        if (!$this->evenementRepository->ajouterJointure($evenement,$loginUtilisateur)) {
            throw new ServiceException("Une erreur est survenue lors de la création de l'événement",
                "FormulaireCreationEvenement", [], "warning");
        }

        return $evenement->getCodeSecret();
    }

    /**
     * Met à jour un événement.
     *
     * @param int $idEvenement L'identifiant de l'événement.
     * @param string $nomEvenement Le nouveau nom de l'événement.
     * @return string Le code secret de l'événement mis à jour.
     * @throws ServiceException Si l'événement n'existe pas ou si l'utilisateur n'a pas les droits.
     */
    public function mettreAJourEvenement(int $idEvenement, string $nomEvenement): string
    {
        $evenement= $this->verifierAccesEvenement($idEvenement);

        if (empty($nomEvenement)) {
            throw new ServiceException("Le nom de l'événement est manquant.",
                Response::HTTP_BAD_REQUEST,
                "FormulaireMiseAJourEvenement", ["idEvenement" => $idEvenement]);
        }
        if (!Validator::hasValideLength($nomEvenement,3,30)) {
            throw new ServiceException("La longueur du nom de l'événement n'est pas valide.",
                Response::HTTP_BAD_REQUEST,
                "FormulaireMiseAJourEvenement", ["idEvenement" => $idEvenement]);
        }

        $evenement->setTitre($nomEvenement);
        $this->evenementRepository->mettreAJour($evenement);
        return $evenement->getCodeSecret();
    }

    /**
     * Supprime un événement.
     *
     * @param int $idEvenement L'identifiant de l'événement.
     * @throws ServiceException Si l'événement n'existe pas ou si l'utilisateur n'a pas les droits.
     */
    public function supprimerEvenement(int $idEvenement): void
    {
        $this->verifierAccesProprietaireEvenement($idEvenement);

        $evenementRepository = $this->evenementRepository;

        if (!$evenementRepository->supprimer($idEvenement)) {
            throw new ServiceException("Une erreur est survenue lors de la suppression de l'événement.",
                "MesEvenements");
        }
    }

    /**
     * Récupère les utilisateurs pouvant être ajoutés à un événement.
     *
     * @param int $idEvenement L'identifiant de l'événement.
     * @return array Un tableau contenant l'événement et les utilisateurs disponibles.
     * @throws ServiceException Si l'événement n'existe pas ou si l'utilisateur n'a pas les droits.
     */
    public function recupererUtilisateursPourAjout(int $idEvenement): array
    {
        $evenement = $this->verifierAccesProprietaireEvenement($idEvenement);

        $utilisateurs = $this->utilisateurRepository->recupererUtilisateursOrdonnesPrenomNom();
        $utilisateursDisponibles = array_filter($utilisateurs, function ($u) use ($evenement) {
            return !$evenement->estMembre($u->getLogin());
        });

        if (empty($utilisateursDisponibles)) {
            throw new ServiceException("Aucun utilisateur disponible à ajouter.",
                Response::HTTP_BAD_REQUEST,
                "Evenement", ["codeEvenement" => $evenement->getCodeSecret()], "warning");
        }

        return [
            "evenement" => $evenement,
            "utilisateurs" => $utilisateursDisponibles
        ];
    }

    /**
     * Ajoute un membre à un événement.
     *
     * @param int $idEvenement L'identifiant de l'événement.
     * @param string $loginUtilisateur Le login de l'utilisateur à ajouter.
     * @return string Le code secret de l'événement.
     * @throws ServiceException Si l'événement ou l'utilisateur n'existe pas, ou si l'utilisateur est déjà membre.
     */
    public function ajouterMembre(int $idEvenement, string $loginUtilisateur): string
    {
        $evenement = $this->verifierAccesProprietaireEvenement($idEvenement);
        $codeSecret = $evenement->getCodeSecret();

        if($loginUtilisateur == null) {
            throw new ServiceException("Login du membre à ajouter manquant",
                Response::HTTP_BAD_REQUEST,
                "Evenement", ["codeEvenement" => $codeSecret]);
        }

        $utilisateurRepository = $this->utilisateurRepository;
        $utilisateur = $utilisateurRepository->recupererParClePrimaire($loginUtilisateur);

        if (!$utilisateur) {
            throw new ServiceException("Utilisateur inexistant",
                Response::HTTP_BAD_REQUEST,
                "Evenement", ["codeEvenement" => $codeSecret]);
        }

        if ($evenement->estMembre($loginUtilisateur)) {
            throw new ServiceException("Cet utilisateur est déjà membre de l'événement",
                Response::HTTP_BAD_REQUEST,
                "Evenement", ["codeEvenement" => $codeSecret], "warning");
        }

        $membres = $evenement->getMembres();
        $membres[] = $utilisateur->getLogin();
        $evenement->setMembres($membres);

        $this->evenementRepository->mettreAJour($evenement);
        $this->evenementRepository->ajouterJointure($evenement,$loginUtilisateur);

        return $codeSecret;
    }

    /**
     * Permet à un utilisateur de quitter un événement.
     *
     * @param int $idEvenement L'identifiant de l'événement.
     * @throws ServiceException Si l'utilisateur n'est pas membre ou propriétaire de l'événement.
     */
    public function quitterEvenement(int $idEvenement): void
    {
        $this->verifierConnexion();

        $evenementRepository = $this->evenementRepository;
        $evenement = $evenementRepository->recupererParClePrimaire($idEvenement);

        $this->verifierExistenceEvenement($evenement);

        $loginUtilisateur = ConnexionUtilisateur::getLoginUtilisateurConnecte();

        if ($evenement->estProprietaire($loginUtilisateur)) {
            throw new ServiceException("Vous ne pouvez pas quitter cet événement car vous en êtes le propriétaire.",
                Response::HTTP_FORBIDDEN,
                "MesEvenements");
        }

        if (!$evenement->estMembre($loginUtilisateur)) {
            throw new ServiceException("Vous n'êtes pas membre de cet événement.",
                Response::HTTP_FORBIDDEN,
                "MesEvenements");
        }

        $this->mettreAJourMembresEtDepenses($evenement, $loginUtilisateur);
        $this->evenementRepository->supprimerJointure($evenement,ConnexionUtilisateur::getLoginUtilisateurConnecte());
    }

    /**
     * Supprime un membre d'un événement.
     *
     * @param int $idEvenement L'identifiant de l'événement.
     * @param string $loginUtilisateur Le login de l'utilisateur à supprimer.
     * @return string Le code secret de l'événement.
     * @throws ServiceException Si l'événement ou l'utilisateur n'existe pas, ou si l'utilisateur n'est pas membre.
     */
    public function supprimerMembre(int $idEvenement, string $loginUtilisateur): string
    {
        $evenement = $this->verifierAccesProprietaireEvenement($idEvenement);

        $utilisateurRepository = $this->utilisateurRepository;
        $utilisateur = $utilisateurRepository->recupererParClePrimaire($loginUtilisateur);

        $codeSecret = $evenement->getCodeSecret();
        if (!$utilisateur) {
            throw new ServiceException("Utilisateur inexistant.",
                Response::HTTP_NOT_FOUND,
                "Evenement", ["codeEvenement" => $codeSecret]);
        }

        if (!$evenement->estMembre($loginUtilisateur)) {
            throw new ServiceException("Cet utilisateur n'est pas membre de l'événement.",
                Response::HTTP_BAD_REQUEST,
                "Evenement", ["codeEvenement" => $codeSecret]);
        }

        if ($evenement->estProprietaire($loginUtilisateur)) {
            throw new ServiceException("Vous ne pouvez pas supprimer le propriétaire de l'événement.",
                Response::HTTP_BAD_REQUEST,
                "Evenement", ["codeEvenement" => $codeSecret]);
        }

        $this->mettreAJourMembresEtDepenses($evenement, $loginUtilisateur);
        $this->evenementRepository->supprimerJointure($evenement,$loginUtilisateur);

        return $codeSecret;
    }

    /**
     * Met à jour les membres et les dépenses d'un événement après la suppression d'un membre.
     *
     * @param Evenement $evenement L'événement à mettre à jour.
     * @param string $loginUtilisateur Le login de l'utilisateur à supprimer.
     */
    private function mettreAJourMembresEtDepenses(Evenement $evenement, string $loginUtilisateur): void
    {
        // Mise à jour des membres
        $membres = array_filter($evenement->getMembres(), fn($membre) => $membre->getLogin() !== $loginUtilisateur);
        $evenement->setMembres($membres);
        $this->evenementRepository->mettreAJour($evenement);

        // Gestion des dépenses
        $depenseRepository = $this->depenseRepository;
        foreach ($this->depenseRepository->recupererParEvenement($evenement->getId()) as $depense) {
            if ($depense->estPayeur($loginUtilisateur)) {
                $depenseRepository->supprimer($depense->getId());
            } elseif ($depense->estParticipant($loginUtilisateur)) {
                $depenseRepository->supprimerJointure($depense,$loginUtilisateur);
                $participants = array_filter($depense->getParticipants(), fn($participant) => $participant->getLogin() !== $loginUtilisateur);
                if (empty($participants)) {
                    $depenseRepository->supprimer($depense->getId());
                } else {
                    $depense->setParticipants($participants);
                    $depenseRepository->mettreAJour($depense);
                }
            }
        }
    }
}