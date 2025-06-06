<?php

namespace App\VeryBadSplit\Configuration;

class ConfigurationBaseDeDonnees {

    static private array $configurationBaseDeDonnees = array(
        'nomHote' => 'webinfo.iutmontp.univ-montp2.fr',
        'nomBaseDeDonnees' => 'godekp',
        'port' => '3316',
        'login' => 'godekp',
        'motDePasse' => '100793528EF'
    );

    static public function getLogin() : string {
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['login'];
    }

    static public function getNomBaseDeDonnees() : string {
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['nomBaseDeDonnees'];
    }

    static public function getPort() : string {
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['port'];
    }

    static public function getNomHote() : string {
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['nomHote'];
    }

    static public function getMotDePasse() : string {
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['motDePasse'];
    }

}