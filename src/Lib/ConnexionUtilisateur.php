<?php

namespace App\VeryBadSplit\Lib;

use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\HTTP\Session;
use App\VeryBadSplit\Modele\Repository\UtilisateurRepository;

class ConnexionUtilisateur
{
    private static string $cleConnexion = "_utilisateurConnecte";

    public static function connecter(string $loginUtilisateur): void
    {
        $session = Session::getInstance();
        $session->enregistrer(ConnexionUtilisateur::$cleConnexion, $loginUtilisateur);
    }

    public static function estConnecte(): bool
    {
        $session = Session::getInstance();
        return $session->contient(ConnexionUtilisateur::$cleConnexion);
    }

    public static function deconnecter() : void
    {
        $session = Session::getInstance();
        $session->supprimer(ConnexionUtilisateur::$cleConnexion);
    }

    public static function getLoginUtilisateurConnecte(): ?string
    {
        $session = Session::getInstance();
        if ($session->contient(ConnexionUtilisateur::$cleConnexion)) {
            return $session->lire(ConnexionUtilisateur::$cleConnexion);
        } else
            return null;
    }

    public static function estUtilisateur($login): bool
    {
        return (ConnexionUtilisateur::estConnecte() &&
            ConnexionUtilisateur::getLoginUtilisateurConnecte() == $login
        );
    }

    public static function tel() : int
    {
        //TO-DO
        return 5;
    }
}
