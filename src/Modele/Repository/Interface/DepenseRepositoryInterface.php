<?php

namespace App\VeryBadSplit\Modele\Repository\Interface;

use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\Repository\AbstractRepository;

interface DepenseRepositoryInterface
{
    public function ajouter(Depense $depense): bool;
    
    public function recupererParClePrimaire(int $clePrimaire): ?AbstractDataObject;

    /**
     * @return Depense[]
     */
    public function recupererDepensesPayeesOuParticipeUtilisateur(string $login): array;
    
    public function mettreAJour(Depense $depense): void;
    
    public function supprimer(string $id): bool;
    
    public function getNextId(): int;
    
    public function recupererParEvenement($idEvenement): ?array;
    
    public function ajouterJointure(Depense $depense, string $loginParticipant): bool;
    
    public function supprimerJointure(Depense $depense, string $loginParticipant): bool;
}