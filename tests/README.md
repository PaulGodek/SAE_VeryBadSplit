# Tests unitaires VeryBadSplit

## Installation
```bash
# Installer Xdebug pour la couverture
sudo apt install php8.3-xdebug
```

## Lancer les tests
```bash
# Tous les tests
composer test

# Avec couverture de code
XDEBUG_MODE=coverage composer test-coverage

# Un seul fichier
./vendor/bin/phpunit tests/Unit/Service/DepenseServiceTest.php

# Une seule méthode
./vendor/bin/phpunit --filter testNomDeLaMethode
```

## Structure
```
tests/
├── Unit/Service/          # Tests des services
├── TestCase.php          # Classe de base
├── bootstrap.php         # Config PHPUnit
└── TestSession.php       # Mock de Session
```

## Couverture
- Rapport HTML dans `reports/coverage/`

## Écrire un test
```php
public function testMethodeAvecSucces(): void
{
    // Arrange
    $this->connecterUtilisateur('alice');
    $mock = $this->createMock(Interface::class);
    $mock->method('find')->willReturn($data);
    
    // Act
    $result = $this->service->methode($param);
    
    // Assert
    $this->assertEquals($expected, $result);
}
```

## Problèmes fréquents
- **Session errors** : Utiliser `$this->connecterUtilisateur()`
- **Mock errors** : Vérifier que la méthode existe dans l'interface
- **Password tests** : Utiliser `MotDePasse::hacher()` pas `password_hash()`