<?php

namespace App\VeryBadSplit\Service\Interface;

use App\VeryBadSplit\Modele\DataObject\Evenement;

interface EvenementServiceInterface
{
    /**
     * Vérifie l'existence d'un événement.
     *
     * @param Evenement|null $evenement L'événement à vérifier.
     * @throws \App\VeryBadSplit\Service\Exception\ServiceException Si l'événement n'existe pas.
     */
    public function verifierExistenceEvenement($evenement): void;
    
    /**
     * Vérifie les droits d'édition sur un événement.
     *
     * @param Evenement $evenement L'événement à vérifier.
     * @throws \App\VeryBadSplit\Service\Exception\ServiceException Si l'utilisateur n'a pas les droits nécessaires.
     */
    public function verifierDroitsEvenement($evenement): void;
    
    /**
     * Vérifie l'accès à un événement et retourne l'événement.
     */
    public function verifierAccesEvenement(int $idEvenement): Evenement;
    
    /**
     * Vérifie que l'utilisateur est propriétaire de l'événement.
     */
    public function verifierAccesProprietaireEvenement(int $idEvenement): Evenement;
    
    /**
     * Récupère un événement avec les dettes calculées.
     */
    public function recupererEvenementAvecDettes(string $codeEvenement): array;
    
    /**
     * Récupère la liste des événements d'un utilisateur.
     */
    public function recupererEvenementsUtilisateur(?string $login): array;
    
    /**
     * Crée un nouvel événement.
     */
    public function creerEvenement(string $nomEvenement, string $loginUtilisateur): string;
    
    /**
     * Met à jour un événement.
     */
    public function mettreAJourEvenement(int $idEvenement, string $nomEvenement): string;
    
    /**
     * Supprime un événement.
     */
    public function supprimerEvenement(int $idEvenement): void;
    
    /**
     * Récupère la liste des utilisateurs pouvant être ajoutés à un événement.
     */
    public function recupererUtilisateursPourAjout(int $idEvenement): array;
    
    /**
     * Ajoute un membre à un événement.
     */
    public function ajouterMembre(int $idEvenement, string $loginUtilisateur): string;
    
    /**
     * Permet à l'utilisateur connecté de quitter un événement.
     */
    public function quitterEvenement(int $idEvenement): void;
    
    /**
     * Supprime un membre d'un événement.
     */
    public function supprimerMembre(int $idEvenement, string $loginUtilisateur): string;
}