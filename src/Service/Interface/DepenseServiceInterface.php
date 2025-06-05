<?php

namespace App\VeryBadSplit\Service\Interface;

use App\VeryBadSplit\Modele\DataObject\Depense;

interface DepenseServiceInterface
{
    /**
     * Vérifie l'accès à une dépense et la retourne.
     */
    public function verifierAccesDepense(int $idDepense): Depense;
    
    /**
     * Crée une nouvelle dépense.
     */
    public function creerDepense(int $idEvenement, string $titre, float $montant, string $payeur, array $loginsParticipants): string;
    
    /**
     * Met à jour une dépense existante.
     */
    public function mettreAJourDepense(int $idDepense, string $titre, float $montant, string $payeurLogin, array $loginsParticipants): string;
    
    /**
     * Supprime une dépense.
     */
    public function supprimerDepense(int $idDepense): string;
}