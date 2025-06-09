<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Classe de base pour tous les tests
 * Gère les problèmes de session et fournit des helpers
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Éviter les erreurs de session
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        
        // Mock de la fonction session_start si nécessaire
        if (!defined('SESSION_TEST_MODE')) {
            define('SESSION_TEST_MODE', true);
        }
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la session
        $_SESSION = [];
    }
    
    /**
     * Simule une connexion utilisateur
     */
    protected function connecterUtilisateur(string $login): void
    {
        $_SESSION['_utilisateurConnecte'] = $login;
    }
    
    /**
     * Simule une déconnexion
     */
    protected function deconnecterUtilisateur(): void
    {
        unset($_SESSION['_utilisateurConnecte']);
    }
}