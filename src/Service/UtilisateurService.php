<?php

namespace App\VeryBadSplit\Service;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MotDePasse;
use App\VeryBadSplit\Lib\Validator;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\DepenseRepository;
use App\VeryBadSplit\Modele\Repository\EvenementRepository;
use App\VeryBadSplit\Modele\Repository\UtilisateurRepository;
use App\VeryBadSplit\Service\Exception\ServiceException;

class UtilisateurService extends GeneriqueService
{
    private UtilisateurRepository $utilisateurRepository;
    private EvenementRepository $evenementRepository;
    private DepenseRepository $depenseRepository;

    public function __construct(
        UtilisateurRepository $utilisateurRepository,
        EvenementRepository $evenementRepository,
        DepenseRepository $depenseRepository
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->evenementRepository = $evenementRepository;
        $this->depenseRepository = $depenseRepository;
    }

    /**
     * Récupère les détails de l'utilisateur connecté.
     *
     * @return Utilisateur L'objet Utilisateur de l'utilisateur connecté.
     * @throws ServiceException Si aucun utilisateur n'est connecté.
     */
    public function recupererUtilisateurConnecte(): Utilisateur
    {
        $this->verifierConnexion();

        $login = ConnexionUtilisateur::getLoginUtilisateurConnecte();
        $utilisateur = $this->utilisateurRepository->recuperer($login);

        if (!$utilisateur) {
            throw new ServiceException("Utilisateur introuvable.", "connexion");
        }

        return $utilisateur;
    }

    /**
     * Crée un nouvel utilisateur à partir des données fournies.
     *
     * @param string $login Le login de l'utilisateur.
     * @param string $prenom Le prénom de l'utilisateur.
     * @param string $nom Le nom de l'utilisateur.
     * @param string $email L'adresse email de l'utilisateur.
     * @param string $mdp Le mot de passe de l'utilisateur.
     * @param string $mdp2 La confirmation du mot de passe.
     * @throws ServiceException Si une erreur survient lors de la validation ou de la création.
     */
    public function creerUtilisateur(string $login, string $prenom, string $nom, string $email, string $mdp, string $mdp2): void {
        $this->verifierNonConnecte();

        if (!ControleurGenerique::isNotNull([$login, $prenom, $nom, $email, $mdp, $mdp2])) {
            throw new ServiceException("Login, nom, prénom, email ou mot de passe manquant.",
                "inscription", "danger");
        }

        if ($mdp !== $mdp2) {
            throw new ServiceException("Mots de passe distincts.",
                "inscription");
        }

        if (!Validator::isValidEmail($email)) {
            throw new ServiceException("Email non valide.",
                "inscription");
        }

        if ($this->utilisateurRepository->recuperer($login)) {
            throw new ServiceException("Le login est déjà pris.",
                "inscription");
        }

        $utilisateur = new Utilisateur(
            login: $login,
            nom: $nom,
            prenom: $prenom,
            email: $email,
            mdpHache: MotDePasse::hacher($mdp),
            mdp: $mdp
        );

        $idEvenement = $this->evenementRepository->getNextId();

        $depenseRepository = $this->depenseRepository;
        $idDepense = $depenseRepository->getNextId();

        $evenement = new Evenement(
            id: $idEvenement,
            codeSecret: hash("sha256", $login . $idEvenement),
            titre: "Evenement d'exemple",
            date: new \DateTime(),
            proprietaire: $utilisateur,
            membres: [$utilisateur]
        );

        $depense = new Depense(
            id: $idDepense,
            titre: "Exemple de dépense",
            date: new \DateTime(),
            montant: 50,
            payeur: $utilisateur,
            evenement: $evenement,
            participants: [$utilisateur]
        );

        if(!$depenseRepository->ajouter($depense))
            throw new ServiceException("Une erreur est survenue lors de la création de l'utilisateur.",
                "inscription");
    }

    /**
     * Met à jour les informations d'un utilisateur.
     * 
     * @throws ServiceException
     */
    public function mettreAJourUtilisateur(
        string $login,
        ?string $prenom,
        ?string $nom,
        ?string $email,
        ?string $mdpActuel,
        ?string $mdp,
        ?string $mdp2
    ): void {
        $this->verifierConnexion();

        if (!ControleurGenerique::isNotNull([$login, $prenom, $nom, $email, $mdpActuel])) {
            throw new ServiceException("Login, nom, prénom, email ou mot de passe actuel manquant.", "compte/modifier");
        }

        if (!Validator::isValidEmail($email)) {
            throw new ServiceException("Email non valide.", "compte/modifier", "warning");
        }

        $utilisateurRepository = $this->utilisateurRepository;
        $utilisateur = $utilisateurRepository->recuperer($login);

        if (!$utilisateur) {
            throw new ServiceException("L'utilisateur n'existe pas.", "compte/modifier");
        }

        if ($mdp || $mdp2) {
            if (!$mdp || !$mdp2) {
                throw new ServiceException("Pour modifier votre mot de passe, vous devez saisir les 2 champs correspondants.",
                    "compte/modifier", "warning");
            }
            if ($mdp !== $mdp2) {
                throw new ServiceException("Mots de passe distincts.", "compte/modifier", "warning");
            }
            // Stocke que le mot de passe haché. Pas le mot de passe en clair ni en en cookie.
            $utilisateur->setMdpHache(MotDePasse::hacher($mdp));
        }

        $utilisateur->setNom($nom);
        $utilisateur->setPrenom($prenom);
        $utilisateur->setEmail($email);

        $utilisateurRepository->mettreAJour($utilisateur);

        $evenementRepository = $this->evenementRepository;
        foreach ($evenementRepository->recupererEvenementsUtilisateur($login) as $evenement) {
            $membres = array_filter($evenement->getMembres(), fn($u) => $u->getLogin() !== $login);
            $membres[] = $utilisateur;
            $evenement->setMembres($membres);
            $evenementRepository->mettreAJour($evenement);
        }
    }

    /**
     * Supprime un utilisateur ainsi que toutes les données associées à son compte.
     *
     * @param string $login Le login de l'utilisateur à supprimer.
     * @throws ServiceException Si l'utilisateur n'est pas connecté.
     */
    public function supprimerUtilisateur(string $login): void
    {
        $this->verifierConnexion();

        $evenementRepository = $this->evenementRepository;
        foreach ($evenementRepository->recupererEvenementsUtilisateur($login) as $evenement) {
            $membres = array_filter($evenement->getMembres(), fn($u) => $u->getLogin() !== $login);
            $evenement->setMembres($membres);
            $evenementRepository->mettreAJour($evenement);
        }

        $depenseRepository = $this->depenseRepository;
        foreach ($depenseRepository->recupererDepensesPayeesOuParticipeUtilisateur($login) as $depense) {
            if ($depense->estPayeur($login)) {
                $depenseRepository->supprimer($depense->getId());
            } elseif ($depense->estParticipant($login)) {
                $participants = array_filter($depense->getParticipants(), fn($u) => $u->getLogin() !== $login);
                if (empty($participants)) {
                    $depenseRepository->supprimer($depense->getId());
                } else {
                    $depense->setParticipants($participants);
                    $depenseRepository->mettreAJour($depense);
                }
            }
        }
        
        $this->utilisateurRepository->supprimer($login);
        
        ConnexionUtilisateur::deconnecter();
    }

    /**
     * Connecte un utilisateur en vérifiant ses identifiants.
     *
     * @param string|null $login Le login de l'utilisateur.
     * @param string|null $mdp Le mot de passe de l'utilisateur.
     * @throws ServiceException Si l'utilisateur est déjà connecté, si les identifiants sont manquants,
     *                          si le login est inconnu ou si le mot de passe est incorrect.
     */
    public function connecterUtilisateur(?string $login, ?string $mdp): void
    {
        $this->verifierNonConnecte();
        
        if ($login == null || $mdp == null) {
            throw new ServiceException("Login ou mot de passe manquant.", "connexion");
        }
        
        $utilisateur = $this->utilisateurRepository->recuperer($login);

        if (!$utilisateur) {
            throw new ServiceException("Login inconnu.", "connexion");
        }

        if (!MotDePasse::verifier($mdp, $utilisateur->getMdpHache())) {
            throw new ServiceException("Mot de passe incorrect.", "connexion");
        }

        ConnexionUtilisateur::connecter($utilisateur->getLogin());
    }


    /**
     * Récupère une liste d'utilisateurs associés à une adresse email donnée.
     *
     * @param string $email L'adresse email à rechercher.
     * @return Utilisateur[] Un tableau d'objets Utilisateur correspondant à l'email fourni.
     * @throws ServiceException Si l'adresse email est manquante ou si aucun utilisateur n'est trouvé.
     */
    public function recupererUtilisateursParEmail(string $email): array {
        $this->verifierConnexion();
        
        if (empty($email)) {
            throw new ServiceException("Adresse email manquante.", "recuperation");
        }

        $utilisateurs = $this->utilisateurRepository->recupererParEmail($email);

        if (empty($utilisateurs)) {
            throw new ServiceException("Aucun compte associé à cette adresse email.", "recuperation");
        }

        return $utilisateurs;
    }
    
}