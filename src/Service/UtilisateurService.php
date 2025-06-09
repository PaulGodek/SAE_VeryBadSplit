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
use Symfony\Component\HttpFoundation\Response;

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
            throw new ServiceException("Utilisateur introuvable.", Response::HTTP_FORBIDDEN, "afficherFormulaireConnexion");
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

        if (!$this->isNotNull([$login, $prenom, $nom, $email, $mdp, $mdp2])) {
            throw new ServiceException("Login, nom, prénom, email ou mot de passe manquant.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireCreation", [], "danger");
        }

        if ($mdp !== $mdp2) {
            throw new ServiceException("Mots de passe distincts.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireCreation");
        }

        if (!Validator::isValidEmail($email)) {
            throw new ServiceException("Email non valide.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireCreation");
        }

        if (!Validator::hasValideLength($login,3,30)) {
            throw new ServiceException("La longueur du nom d'utilisateur n'est pas valide.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireCreation");
        }

        if (!Validator::hasValideLength($nom,1,30)) {
            throw new ServiceException("La longueur du nom n'est pas valide.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireCreation");
        }

        if (!Validator::hasValideLength($prenom,1,30)) {
            throw new ServiceException("La longueur du prenom n'est pas valide.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireCreation");
        }

        if (!Validator::isValideMdp($mdp)) {
            throw new ServiceException("Le mot de passe ne respecte pas le modèle donné.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireCreation");
        }



        if ($this->utilisateurRepository->recupererParClePrimaire($login)) {
            throw new ServiceException("Le login est déjà pris.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireCreation");
        }

        if ($this->utilisateurRepository->recupererParEmail($email)) {
            throw new ServiceException("Ce mail est déjà pris.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireCreation");
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

        if (!$this->isNotNull([$login, $prenom, $nom, $email, $mdpActuel])) {
            throw new ServiceException("Login, nom, prénom, email ou mot de passe actuel manquant.", Response::HTTP_BAD_REQUEST, "afficherFormulaireMiseAJour");
        }

        if (!Validator::isValidEmail($email)) {
            throw new ServiceException("Email non valide.", Response::HTTP_BAD_REQUEST, "afficherFormulaireMiseAJour", [], "warning");
        }

        if (!Validator::hasValideLength($login,3,30)) {
            throw new ServiceException("La longueur du nom d'utilisateur n'est pas valide.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireMiseAJour", [], "danger");
        }

        if (!Validator::hasValideLength($nom,1,30)) {
            throw new ServiceException("La longueur du nom n'est pas valide.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireMiseAJour", [], "danger");
        }

        if (!Validator::hasValideLength($prenom,1,30)) {
            throw new ServiceException("La longueur du prenom n'est pas valide.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireMiseAJour", [], "danger");
        }

        if (!empty($mdp) && !Validator::isValideMdp($mdp)) {
            throw new ServiceException("Le mot de passe ne respecte pas le modèle donné.",
                Response::HTTP_BAD_REQUEST,
                "afficherFormulaireMiseAJour", [], "danger");
        }

        $utilisateurRepository = $this->utilisateurRepository;
        $utilisateur = $utilisateurRepository->recupererParClePrimaire($login);

        if (!$utilisateur) {
            throw new ServiceException("L'utilisateur n'existe pas.", Response::HTTP_NOT_FOUND, "afficherFormulaireMiseAJour");
        }

        if(!MotDePasse::verifier($mdpActuel,$utilisateur->getMdpHache())){
            throw new ServiceException('Le mot de passe actuel entré incorrect',Response::HTTP_BAD_REQUEST, "afficherFormulaireMiseAJour", [], 'warning');
        }

        if ($mdp || $mdp2) {
            if (!$mdp || !$mdp2) {
                throw new ServiceException("Pour modifier votre mot de passe, vous devez saisir les 2 champs correspondants.",
                    Response::HTTP_BAD_REQUEST,
                    "afficherFormulaireMiseAJour", [], "warning");
            }
            if ($mdp !== $mdp2) {
                throw new ServiceException("Mots de passe distincts.",
                    Response::HTTP_BAD_REQUEST,
                    "afficherFormulaireMiseAJour", [], "warning");
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
     * Met à jour les informations d'un utilisateur.
     *
     * @throws ServiceException
     */
    public function reinitialiserMotDePasse(
        string $login,
        string $mdp,
    ): void {

        $utilisateurRepository = $this->utilisateurRepository;
        $utilisateur = $utilisateurRepository->recupererParClePrimaire($login);

        if (!$utilisateur) {
            throw new ServiceException("L'utilisateur n'existe pas.", Response::HTTP_NOT_FOUND,"afficherFormulaireRecuperationCompte");
        }

        $utilisateur->setMdpHache(MotDePasse::hacher($mdp));

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
            throw new ServiceException("Login ou mot de passe manquant.", Response::HTTP_BAD_REQUEST, "afficherFormulaireConnexion");
        }
        
        $utilisateur = $this->utilisateurRepository->recupererParClePrimaire($login);

        if (!$utilisateur) {
            throw new ServiceException("Login inconnu.", Response::HTTP_BAD_REQUEST, "afficherFormulaireConnexion");
        }

        if (!MotDePasse::verifier($mdp, $utilisateur->getMdpHache())) {
            throw new ServiceException("Mot de passe incorrect.", Response::HTTP_BAD_REQUEST, "afficherFormulaireConnexion");
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
    public function recupererUtilisateurParEmail(string $email): Utilisateur {
        //$this->verifierConnexion();
        //Bah justement non du coup tu peux pas être connecté à ce moment la puisque t'as plus tes id
        
        if (empty($email)) {
            throw new ServiceException("Adresse email manquante.", Response::HTTP_BAD_REQUEST, "afficherFormulaireRecuperationCompte");
        }

        $utilisateur = $this->utilisateurRepository->recupererParEmail($email);

        if (!$utilisateur) {
            throw new ServiceException("Aucun compte associé à cette adresse email.", Response::HTTP_BAD_REQUEST, "afficherFormulaireRecuperationCompte");
        }

        return $utilisateur;
    }

    public function recupererUtilisateurParClePrimaire(string $login) {
        $this->verifierConnexion();
        $utilisateurs = $this->utilisateurRepository->recupererParClePrimaire($login);
        return $utilisateurs;
    }

    public function isNotNull(array $array) : bool {
        foreach ($array as $value) {
            if(!(isset($value) && $value != null)) {
                return false;
            }
        }
        return true;
    }

}