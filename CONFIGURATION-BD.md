# Configuration de la base de données - VeryBadSplit

## Comment l'application se connecte à la base de données

L'application utilise la classe `ConfigurationBaseDeDonnees` pour gérer les paramètres de connexion à MySQL/MariaDB.

### Fichier de configuration

**Emplacement** : `src/Configuration/ConfigurationBaseDeDonnees.php`

Cette classe lit les paramètres depuis les variables d'environnement ou utilise des valeurs par défaut :

```php
DB_USER     → Utilisateur MySQL (défaut: verybadsplit_user)
DB_PASSWORD → Mot de passe (défaut: verybadsplit_pass)
DB_NAME     → Nom de la base (défaut: verybadsplit)
DB_HOST     → Serveur MySQL (défaut: db)
DB_PORT     → Port MySQL (défaut: 3306)
```

### Configuration avec Docker

Lors de l'utilisation avec Docker, les paramètres sont automatiquement configurés :

1. **DB_HOST = "db"** : C'est le nom du service MySQL dans Docker
2. Les autres paramètres correspondent aux valeurs dans `docker-compose.yml`
3. Docker crée un réseau interne permettant la communication entre conteneurs

### Configuration sans Docker (développement local)

Si vous utilisez MySQL/MariaDB en local :

1. Modifiez le fichier `.env` à la racine :
```env
DB_HOST=localhost
DB_USER=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
DB_NAME=verybadsplit
```

2. Ou modifiez directement les valeurs par défaut dans `ConfigurationBaseDeDonnees.php`

### Schéma de connexion

```
Application PHP
     ↓
ConfigurationBaseDeDonnees::getNomHote() → "db" (Docker) ou "localhost" (local)
     ↓
ConnexionBaseDeDonnees (Singleton PDO)
     ↓
MySQL/MariaDB
```

### Vérifier la connexion

Pour tester si la connexion fonctionne :

1. Accédez à l'application : http://localhost:8080/web/controleurFrontal.php
2. Si une erreur de connexion apparaît, vérifiez :
   - Que les conteneurs Docker sont démarrés
   - Les paramètres dans `.env`
   - Les logs : `docker-compose logs db`

### Import de la base de données

La base est automatiquement créée au premier démarrage de Docker grâce au fichier `VeryBadSplit.sql` placé dans `docker/mysql-init/`.

Pour réimporter manuellement :
```bash
docker-compose exec db mysql -u verybadsplit_user -p verybadsplit < VeryBadSplit.sql
```