<?php

namespace App\VeryBadSplit\Configuration;

interface ConfigurationBaseDeDonneesInterface
{
    public function getLogin(): string;

    public function getNomBaseDeDonnees(): string;

    public function getPort(): string;

    public function getNomHote(): string;

    public function getMotDePasse(): string;
}