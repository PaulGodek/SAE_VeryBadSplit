# Guide d'installation Docker - VeryBadSplit

Ce guide vous accompagne pas à pas pour installer et configurer VeryBadSplit avec Docker.

## Prérequis

- Docker et Docker Compose installés sur votre machine
- Un terminal (Linux/Mac) ou PowerShell/Git Bash (Windows)

## Installation rapide (3 étapes)

### 1. Cloner le projet et se placer dans le répertoire

```bash
git clone <url-du-repo>
cd sae4
```

### 2. Configuration de l'environnement (optionnel)

Par défaut, l'application fonctionne sans configuration. Si vous souhaitez personnaliser les paramètres :

```bash
cp .env.example .env
```

Puis éditez le fichier `.env` pour modifier les paramètres si nécessaire.

### 3. Lancer l'application

```bash
docker-compose up -d
```

## Accès aux services

Une fois les conteneurs démarrés, vous pouvez accéder à :

- **Application VeryBadSplit** : http://localhost:8080/web/controleurFrontal.php
- **PHPMyAdmin** : http://localhost:8081
  - Serveur : `db`
  - Utilisateur : `verybadsplit_user` 
  - Mot de passe : `verybadsplit_pass`
  - Base de données : `verybadsplit`

## Configuration de la base de données

### Paramètres par défaut

Les paramètres de connexion à la base de données sont :

| Paramètre | Valeur par défaut |
|-----------|-------------------|
| Hôte | `db` (nom du conteneur) |
| Port | `3306` |
| Base de données | `verybadsplit` |
| Utilisateur | `verybadsplit_user` |
| Mot de passe | `verybadsplit_pass` |
| Mot de passe root | `root_password` |

### Comment l'application communique avec la base de données

1. **Réseau Docker** : Docker crée automatiquement un réseau interne entre les conteneurs
2. **Nom d'hôte** : Le conteneur de base de données est accessible via le nom `db` depuis le conteneur PHP
3. **Configuration PHP** : L'application utilise ces paramètres dans `src/Configuration/ConfigurationBaseDeDonnees.php`

### Personnaliser les paramètres

1. Créez/modifiez le fichier `.env` à la racine du projet
2. Modifiez les valeurs souhaitées :

```env
DB_NAME=ma_base
DB_USER=mon_utilisateur
DB_PASSWORD=mon_mot_de_passe
DB_ROOT_PASSWORD=mot_de_passe_root
```

3. Relancez les conteneurs :

```bash
docker-compose down
docker-compose up -d
```

## Structure des conteneurs

Le projet utilise 3 conteneurs :

1. **app** : Serveur Apache/PHP pour l'application
   - Image : PHP 8.2 avec Apache
   - Port : 8080
   - Volumes : Code source monté dans `/var/www/html`

2. **db** : Base de données MySQL
   - Image : MySQL 8.0
   - Port : 3306 (non exposé publiquement)
   - Volumes : Données persistantes + script d'initialisation

3. **phpmyadmin** : Interface d'administration de la BD
   - Image : PHPMyAdmin officielle
   - Port : 8081

## Commandes utiles

### Démarrer les conteneurs
```bash
docker-compose up -d
```

### Arrêter les conteneurs
```bash
docker-compose down
```

### Voir les logs
```bash
# Tous les conteneurs
docker-compose logs -f

# Un conteneur spécifique
docker-compose logs -f app
docker-compose logs -f db
```

### Accéder au shell d'un conteneur
```bash
# Shell PHP/Apache
docker-compose exec app bash

# Console MySQL
docker-compose exec db mysql -u verybadsplit_user -p
```

### Réinitialiser la base de données
```bash
docker-compose down -v  # Supprime les volumes
docker-compose up -d    # Recrée tout
```

## Dépannage

### L'application ne se connecte pas à la base de données

1. Vérifiez que les conteneurs sont bien démarrés :
   ```bash
   docker-compose ps
   ```

2. Vérifiez les logs de la base de données :
   ```bash
   docker-compose logs db
   ```

3. Assurez-vous que le fichier de configuration PHP utilise bien `db` comme hôte

### PHPMyAdmin affiche "Cannot log in to the MySQL server"

1. Attendez quelques secondes que MySQL soit complètement démarré
2. Utilisez les identifiants par défaut ou ceux de votre fichier `.env`
3. Le serveur doit être `db` (pas `localhost`)

### Les modifications du code ne s'affichent pas

Le code est monté en volume, les modifications sont instantanées. Si ce n'est pas le cas :
- Videz le cache de votre navigateur
- Vérifiez que vous modifiez bien les fichiers dans le bon répertoire

## Architecture détaillée

```
sae4/
├── docker-compose.yml      # Configuration des conteneurs
├── Dockerfile             # Image personnalisée PHP/Apache
├── .env.example          # Variables d'environnement exemple
├── docker/
│   ├── apache.conf       # Configuration Apache
│   └── mysql-init/       # Scripts d'initialisation BD
│       └── 01-init.sql   # Création des tables
├── src/                  # Code source PHP
└── web/                  # Point d'entrée de l'application
```

## Pour aller plus loin

- Modifier la configuration Apache : éditez `docker/apache.conf`
- Ajouter des extensions PHP : modifiez le `Dockerfile`
- Changer la version de MySQL : modifiez `docker-compose.yml`
- Ajouter d'autres services (Redis, Mailhog, etc.) : ajoutez-les dans `docker-compose.yml`