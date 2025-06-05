<?php

namespace App\VeryBadSplit\Modele\Repository\Interface;

use App\VeryBadSplit\Modele\DataObject\Evenement;

interface EvenementRepositoryInterface
{
    public function recuperer($id): ?Evenement;
    
    public function recupererParCodeSecret($code): ?Evenement;
    
    /**
     * @return Evenement[]
     */
    public function recupererEvenementsUtilisateur($login): array;
    
    public function mettreAJour(Evenement $evenement): void;
    
    public function supprimer(int $id): bool;
    
    public function getNextId(): int;
    
    public function compterNombreEvenementProprietaire($loginProprietaire): int;
}