<?php

namespace App\VeryBadSplit\Modele\Repository;

use App\VeryBadSplit\Configuration\ConfigurationBaseDeDonneesInterface;
use PDO;

class ConnexionBaseDeDonnees
{
    private PDO $pdo;

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    private function __construct(ConfigurationBaseDeDonneesInterface $configurationBaseDeDonnees)
    {
        $nomHote = $configurationBaseDeDonnees->getNomHote();
        $port = $configurationBaseDeDonnees->getPort();
        $login = $configurationBaseDeDonnees->getLogin();
        $motDePasse = $configurationBaseDeDonnees->getMotDePasse();
        $nomBaseDeDonnees = $configurationBaseDeDonnees->getNomBaseDeDonnees();

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
}