<?php

namespace App\VeryBadSplit\Service\Interface;

use App\VeryBadSplit\Modele\DataObject\Utilisateur;

interface UtilisateurServiceInterface
{
    /**
     * Récupère les détails de l'utilisateur connecté.
     *
     * @return Utilisateur L'objet Utilisateur de l'utilisateur connecté.
     * @throws \App\VeryBadSplit\Service\Exception\ServiceException Si aucun utilisateur n'est connecté.
     */
    public function recupererUtilisateurConnecte(): Utilisateur;
    
    /**
     * Crée un nouvel utilisateur à partir des données fournies.
     *
     * @param string $login Le login de l'utilisateur.
     * @param string $prenom Le prénom de l'utilisateur.
     * @param string $nom Le nom de l'utilisateur.
     * @param string $email L'adresse email de l'utilisateur.
     * @param string $mdp Le mot de passe de l'utilisateur.
     * @param string $mdp2 La confirmation du mot de passe.
     * @throws \App\VeryBadSplit\Service\Exception\ServiceException Si une erreur survient lors de la validation ou de la création.
     */
    public function creerUtilisateur(string $login, string $prenom, string $nom, string $email, string $mdp, string $mdp2): void;
    
    /**
     * Met à jour les informations d'un utilisateur.
     * 
     * @throws \App\VeryBadSplit\Service\Exception\ServiceException
     */
    public function mettreAJourUtilisateur(
        string $login,
        ?string $prenom,
        ?string $nom,
        ?string $email,
        ?string $mdpActuel,
        ?string $mdp,
        ?string $mdp2
    ): void;
    
    /**
     * Supprime un utilisateur ainsi que toutes les données associées à son compte.
     *
     * @param string $login Le login de l'utilisateur à supprimer.
     * @throws \App\VeryBadSplit\Service\Exception\ServiceException Si l'utilisateur n'est pas connecté.
     */
    public function supprimerUtilisateur(string $login): void;
    
    /**
     * Connecte un utilisateur en vérifiant ses identifiants.
     *
     * @param string|null $login Le login de l'utilisateur.
     * @param string|null $mdp Le mot de passe de l'utilisateur.
     * @throws \App\VeryBadSplit\Service\Exception\ServiceException Si l'utilisateur est déjà connecté, si les identifiants sont manquants,
     *                          si le login est inconnu ou si le mot de passe est incorrect.
     */
    public function connecterUtilisateur(?string $login, ?string $mdp): void;
    
    /**
     * Récupère une liste d'utilisateurs associés à une adresse email donnée.
     *
     * @param string $email L'adresse email à rechercher.
     * @return Utilisateur[] Un tableau d'objets Utilisateur correspondant à l'email fourni.
     * @throws \App\VeryBadSplit\Service\Exception\ServiceException Si l'adresse email est manquante ou si aucun utilisateur n'est trouvé.
     */
    public function recupererUtilisateurParEmail(string $email): Utilisateur;
}