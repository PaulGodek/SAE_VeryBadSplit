# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

VeryBadSplit is a Tricount clone - an expense-sharing web application built with custom PHP MVC architecture. The project is intentionally poorly coded for educational purposes, requiring students to analyze, secure, and improve it.

## Architecture

### MVC Pattern
- **Controllers**: `src/Controleur/` - Extend `ControleurGenerique`, use attribute-based routing (`#[Route]`)
- **Models**: `src/Modele/` - DataObjects and Repository pattern for database access
- **Views**: `src/vue/` - PHP templates (will be migrated to Twig)
- **Service Layer**: `src/Service/` - Business logic layer (currently being refactored)

### Key Components
- **Router**: `RouteurURL::traiterRequete()` handles attribute-based routing with Symfony components
- **Database**: Singleton pattern in `ConnexionBaseDeDonnees`, uses PDO for MySQL/MariaDB
- **Session**: Custom session handling in `src/Modele/HTTP/Session.php`
- **Dependency Injection**: Basic container in `src/Lib/Conteneur.php`

### Routing
Routes are defined using attributes on controller methods:
```php
#[Route(path: '/', name: 'accueil', methods: ['GET'])]
```

## Common Commands

### Development
```bash
# Install dependencies
composer install

# Run local development server
php -S localhost:8000 -t web/

# Access the application
# http://localhost:8000/controleurFrontal.php
```

### Database Setup
1. Create a MySQL/MariaDB database
2. Import `VeryBadSplit.sql`
3. Update credentials in `src/Configuration/ConfigurationBaseDeDonnees.php`

## Current State & Refactoring

The project is on branch `refactor/ajout-couche-service`, implementing a service layer to separate business logic from controllers. The refactoring follows this pattern:
- Controllers handle HTTP requests/responses
- Services contain business logic
- Repositories handle database operations

## Security Notes

This application intentionally contains security vulnerabilities for educational analysis. When working on security fixes, focus on:
- SQL injection prevention
- XSS protection
- CSRF tokens
- Proper password hashing
- Input validation and sanitization

## Testing

PHPUnit tests need to be implemented for the service layer with 100% coverage goal. No testing framework is currently set up - this needs to be added as part of the improvements.

## Frontend

- CSS Framework: Bulma
- Icons: Ionicons (loaded via CDN)
- Currently server-side rendered, will be enhanced with JavaScript/AJAX