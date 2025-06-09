<?php

namespace App\VeryBadSplit\Modele\HTTP;

/**
 * Version mockeé de Session pour les tests
 * Évite l'appel à session_start() qui cause des erreurs
 */
class Session
{
    private static ?Session $instance = null;

    private function __construct()
    {}

    public static function getInstance(): Session
    {
        if (is_null(static::$instance)) {
            static::$instance = new Session();
        }
        return static::$instance;
    }

    public function contient($nom): bool
    {
        return isset($_SESSION[$nom]);
    }

    public function enregistrer(string $nom, mixed $valeur): void
    {
        $_SESSION[$nom] = $valeur;
    }

    public function lire(string $nom): mixed
    {
        return $_SESSION[$nom];
    }

    public function supprimer($nom): void
    {
        unset($_SESSION[$nom]);
    }
}