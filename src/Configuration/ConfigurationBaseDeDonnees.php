<?php

namespace App\VeryBadSplit\Configuration;

class ConfigurationBaseDeDonnees {

    static public function getLogin() : string {
        return $_ENV['DB_USER'] ?? 'verybadsplit_user';
    }

    static public function getNomBaseDeDonnees() : string {
        return $_ENV['DB_NAME'] ?? 'verybadsplit';
    }

    static public function getPort() : string {
        return $_ENV['DB_PORT'] ?? '3306';
    }

    static public function getNomHote() : string {
        return $_ENV['DB_HOST'] ?? 'db';
    }

    static public function getMotDePasse() : string {
        return $_ENV['DB_PASSWORD'] ?? 'verybadsplit_pass';
    }

}
