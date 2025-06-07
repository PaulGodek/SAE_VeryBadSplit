## Copier le fichier d'environnement
cp .env.example .env
Optionnel, car il a déjà des valeurs par défaut.

## Démarrer les conteneurs
docker-compose up -d

## Accéder à l'application
## http://localhost:8080/web

# PHPMyAdmin
##  http://localhost:8081

Les services disponibles :
- Application PHP : Port 8080
- MySQL : Port 3306
- PHPMyAdmin : Port 8081