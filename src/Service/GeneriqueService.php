<?php

namespace App\VeryBadSplit\Service;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Service\Exception\ServiceException;

abstract class GeneriqueService
{

    /**
     * Vérifie si un utilisateur est connecté.
     *
     * @throws ServiceException Si l'utilisateur n'est pas connecté.
     */
    public function verifierConnexion(): void
    {
        if (!ConnexionUtilisateur::estConnecte()) {
            throw new ServiceException("Vous devez être connecté pour cela.",
                "afficherFormulaireConnexion");
        }
    }

    /**
     * Vérifie si un utilisateur n'est pas connecté.
     *
     * @throws ServiceException Si l'utilisateur est déjà connecté.
     */
    public function verifierNonConnecte(): void
    {
        if (ConnexionUtilisateur::estConnecte()) {
            throw new ServiceException("Vous êtes déjà connecté.",
                "MesEvenement", [], "warning");
        }
    }
}