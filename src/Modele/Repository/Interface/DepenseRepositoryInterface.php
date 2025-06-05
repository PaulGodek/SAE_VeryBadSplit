<?php

namespace App\VeryBadSplit\Modele\Repository\Interface;

use App\VeryBadSplit\Modele\DataObject\Depense;

interface DepenseRepositoryInterface
{
    public function ajouter(Depense $depense): bool;
    
    public function recuperer(int $id): ?Depense;
    
    /**
     * @return Depense[]
     */
    public function recupererDepensesPayeesOuParticipeUtilisateur(string $login): array;
    
    public function mettreAJour(Depense $depense): void;
    
    public function supprimer(int $id): bool;
    
    public function getNextId(): int;
    
    public function compterNombreDepensesEvenement($idEvenement): int;
}