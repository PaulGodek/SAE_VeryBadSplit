<?php

namespace App\VeryBadSplit\Modele\Repository;

use App\VeryBadSplit\Configuration\ConfigurationBaseDeDonnees;
use PDO;

class ConnexionBaseDeDonnees
{
    private static ?ConnexionBaseDeDonnees $instance = null;

    private PDO $pdo;

    public static function getPdo(): PDO
    {
        return ConnexionBaseDeDonnees::getInstance()->pdo;
    }

    private function __construct()
    {
        $nomHote = ConfigurationBaseDeDonnees::getNomHote();
        $port = ConfigurationBaseDeDonnees::getPort();
        $login = ConfigurationBaseDeDonnees::getLogin();
        $motDePasse = ConfigurationBaseDeDonnees::getMotDePasse();
        $nomBaseDeDonnees = ConfigurationBaseDeDonnees::getNomBaseDeDonnees();

        // Connexion à la base de données
        // Le dernier argument sert à ce que toutes les chaines de caractères
        // en entrée et sortie de MySql soit dans le codage UTF-8
        $this->pdo = new PDO(
            "mysql:host=$nomHote;port=$port;dbname=$nomBaseDeDonnees",
            $login,
            $motDePasse,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );

        // On active le mode d'affichage des erreurs, et le lancement d'exception en cas d'erreur
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private static function getInstance(): ConnexionBaseDeDonnees
    {
        if (is_null(ConnexionBaseDeDonnees::$instance))
            ConnexionBaseDeDonnees::$instance = new ConnexionBaseDeDonnees();
        return ConnexionBaseDeDonnees::$instance;
    }
}
