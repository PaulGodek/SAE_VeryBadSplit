<?php

namespace App\VeryBadSplit\Service;

use App\VeryBadSplit\Controleur\ControleurGenerique;
use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MotDePasse;
use App\VeryBadSplit\Lib\Validator;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\DepenseRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\EvenementRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\UtilisateurRepositoryInterface;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\UtilisateurServiceInterface;

class UtilisateurService extends GeneriqueService implements UtilisateurServiceInterface
{
    private UtilisateurRepositoryInterface $utilisateurRepository;
    private EvenementRepositoryInterface $evenementRepository;
    private DepenseRepositoryInterface $depenseRepository;

    public function __construct(
        UtilisateurRepositoryInterface $utilisateurRepository,
        EvenementRepositoryInterface $evenementRepository,
        DepenseRepositoryInterface $depenseRepository
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
        $utilisateur = $this->utilisateurRepository->recupererParClePrimaire($login);

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

        if (!Validator::hasValideLength($login,3,30)) {
            throw new ServiceException("La longueur du nom d'utilisateur n'est pas valide.",
                "inscrpition");
        }

        if (!Validator::hasValideLength($nom,1,30)) {
            throw new ServiceException("La longueur du nom n'est pas valide.",
                "inscrpition");
        }

        if (!Validator::hasValideLength($prenom,1,30)) {
            throw new ServiceException("La longueur du prenom n'est pas valide.",
                "inscrpition");
        }

        if (!Validator::isValideMdp($mdp)) {
            throw new ServiceException("Le mot de passe ne respecte pas le modèle donné.",
                "inscrpition");
        }



        if ($this->utilisateurRepository->recupererParClePrimaire($login)) {
            throw new ServiceException("Le login est déjà pris.",
                "inscription");
        }

        $utilisateur = new Utilisateur(
            login: $login,
            nom: $nom,
            prenom: $prenom,
            email: $email,
            mdpHache: MotDePasse::hacher($mdp)

        );
        $this->utilisateurRepository->ajouter($utilisateur);

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

        if (!Validator::hasValideLength($login,3,30)) {
            throw new ServiceException("La longueur du nom d'utilisateur n'est pas valide.",
                "compte/modifier", "danger");
        }

        if (!Validator::hasValideLength($nom,1,30)) {
            throw new ServiceException("La longueur du nom n'est pas valide.",
                "compte/modifier", "danger");
        }

        if (!Validator::hasValideLength($prenom,1,30)) {
            throw new ServiceException("La longueur du prenom n'est pas valide.",
                "compte/modifier", "danger");
        }

        if (!empty($mdp) && !Validator::isValideMdp($mdp)) {
            throw new ServiceException("Le mot de passe ne respecte pas le modèle donné.",
                "compte/modifier", "danger");
        }

        $utilisateurRepository = $this->utilisateurRepository;
        $utilisateur = $utilisateurRepository->recupererParClePrimaire($login);

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
        
        $utilisateur = $this->utilisateurRepository->recupererParClePrimaire($login);

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

    public function recupererUtilisateurParClePrimaire(string $login) {
        $this->verifierConnexion();
        $utilisateurs = $this->utilisateurRepository->recupererParClePrimaire($login);
        return $utilisateurs;
    }

}