<?php

namespace App\VeryBadSplit\Modele\Repository\Interface;

use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\Repository\AbstractRepository;

interface DepenseRepositoryInterface
{
    public function ajouter(Depense $depense): bool;
    
    public function recuperer(): ?AbstractDataObject;

    /**
     * @return Depense[]
     */
    public function recupererDepensesPayeesOuParticipeUtilisateur(string $login): array;
    
    public function mettreAJour(Depense $depense): void;
    
    public function supprimer(string $id): bool;
    
    public function getNextId(): int;
    
    public function compterNombreDepensesEvenement($idEvenement): int;

    public function recupererParEvenement($idEvenement): ?Depense;
}