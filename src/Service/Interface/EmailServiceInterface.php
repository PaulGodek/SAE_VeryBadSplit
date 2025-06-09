<?php

namespace App\VeryBadSplit\Service\Interface;

use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Service\Exception\ServiceException;

interface EmailServiceInterface
{
    /**
     * @throws ServiceException
     */
    public function envoyerMailMdpOublie(Utilisateur $utilisateur, $mdp): void;

    /**
     * @throws ServiceException
     */
    public function envoiEmail(string $destinataire, string $sujet, string $corpsEmailHTML, string $corpsEmailAlt): void;
}