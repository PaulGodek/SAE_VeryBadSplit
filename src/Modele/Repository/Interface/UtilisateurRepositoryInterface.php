<?php

namespace App\VeryBadSplit\Modele\Repository\Interface;

use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;

interface UtilisateurRepositoryInterface
{
    public function recupererParClePrimaire(string $clePrimaire): ?AbstractDataObject;
    
    /**
     * @return Utilisateur
     */
    public function recupererParEmail($email): ?AbstractDataObject;

    /**
     * @return Utilisateur[]
     */
    public function recupererUtilisateursOrdonnesPrenomNom(): array;
    
    public function ajouter(Utilisateur $utilisateur): bool;

    public function mettreAJour(Utilisateur $utilisateur): void;

    public function supprimer(string $login): bool;
}