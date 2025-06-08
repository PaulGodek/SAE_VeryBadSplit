<?php

namespace App\VeryBadSplit\Modele\Repository\Interface;

use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\Repository\AbstractRepository;

interface EvenementRepositoryInterface
{
    public function recuperer(): ?AbstractDataObject;


    public function recupererParCodeSecret($code): ?Evenement;
    
    /**
     * @return Evenement[]
     */
    public function recupererEvenementsUtilisateur($login): array;
    
    public function mettreAJour(Evenement $evenement): void;
    
    public function supprimer(string $id): bool;
    
    public function getNextId(): int;
    
    public function compterNombreEvenementProprietaire($loginProprietaire): int;
}