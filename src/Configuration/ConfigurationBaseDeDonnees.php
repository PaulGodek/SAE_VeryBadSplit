<?php

namespace App\VeryBadSplit\Configuration;

class ConfigurationBaseDeDonnees implements ConfigurationBaseDeDonneesInterface {

    public function getLogin() : string {
        return $_ENV['DB_USER'] ?? 'verybadsplit_user';
    }

    public function getNomBaseDeDonnees() : string {
        return $_ENV['DB_NAME'] ?? 'verybadsplit';
    }

    public function getPort() : string {
        return $_ENV['DB_PORT'] ?? '3306';
    }

    public function getNomHote() : string {
        return $_ENV['DB_HOST'] ?? 'db';
    }

    public function getMotDePasse() : string {
        return $_ENV['DB_PASSWORD'] ?? 'verybadsplit_pass';
    }

}
