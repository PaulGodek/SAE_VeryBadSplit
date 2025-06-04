<?php

namespace App\VeryBadSplit\Lib;

/**
 * Classe utilitaire pour la validation des données
 */
class Validator
{
    /**
     * Vérifie qu'aucune valeur du tableau n'est null ou vide
     * 
     * @param array $values Les valeurs à vérifier
     * @return bool true si toutes les valeurs sont non-nulles et non-vides
     */
    public static function allNotEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Vérifie qu'aucune valeur du tableau n'est null
     * 
     * @param array $values Les valeurs à vérifier
     * @return bool true si toutes les valeurs sont non-nulles
     */
    public static function allNotNull(array $values): bool
    {
        foreach ($values as $value) {
            if ($value === null) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Valide une adresse email
     * 
     * @param string $email L'email à valider
     * @return bool true si l'email est valide
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Vérifie qu'une chaîne a une longueur minimale
     * 
     * @param string $string La chaîne à vérifier
     * @param int $minLength La longueur minimale
     * @return bool true si la chaîne respecte la longueur minimale
     */
    public static function hasMinLength(string $string, int $minLength): bool
    {
        return strlen($string) >= $minLength;
    }
    
    /**
     * Vérifie qu'un nombre est dans un intervalle
     * 
     * @param float $number Le nombre à vérifier
     * @param float $min Le minimum (inclus)
     * @param float $max Le maximum (inclus)
     * @return bool true si le nombre est dans l'intervalle
     */
    public static function isInRange(float $number, float $min, float $max): bool
    {
        return $number >= $min && $number <= $max;
    }
}