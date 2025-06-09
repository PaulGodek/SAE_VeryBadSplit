<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Charger notre Session mockée avant que l'autoloader charge la vraie
require_once __DIR__ . '/TestSession.php';

// Éviter les problèmes de session dans les tests
if (!defined('SESSION_DISABLED')) {
    define('SESSION_DISABLED', true);
}

// Mock de session pour les tests
$_SESSION = [];

// Override session functions pour les tests
if (!function_exists('session_start')) {
    function session_start(): true
    {
        return true;
    }
}

if (!function_exists('session_destroy')) {
    function session_destroy(): true
    {
        $_SESSION = [];
        return true;
    }
}

if (!function_exists('session_unset')) {
    function session_unset(): true
    {
        $_SESSION = [];
        return true;
    }
}

if (!function_exists('session_name')) {
    function session_name(): string
    {
        return 'PHPSESSID';
    }
}

// Helper pour réinitialiser l'environnement entre les tests
function resetTestEnvironment() {
    $_SESSION = [];
    $_COOKIE = [];
    $_POST = [];
    $_GET = [];
}