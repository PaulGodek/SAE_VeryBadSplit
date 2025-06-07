<?php

namespace App\VeryBadSplit\Modele\Repository\Interface;

use PDO;

interface ConnexionBaseDeDonneesInterface
{
    public function getPdo(): PDO;
}