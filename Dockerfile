# Le systeme de build multi-étapes a été proposé par l'IA Gemini pour optimiser la taille de l'image Docker
# En tant que Paul, j'ai vérifié que tout fonctionnait et réglé ce qui ne fonctionnait pas
# J'ai pratiqué Docker durant ma deuxième année, ce projet est un des gros projets de mon année précédente

# --- Étape de Build (builder) ---
FROM php:8.3-apache AS builder

# Installer les dépendances système nécessaires pour le build
RUN apt-get update && apt-get install -y \
    git \
    unzip

# Nettoyer le cache APT pour réduire la taille de la couche
RUN rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers Composer AVANT le reste du code pour optimiser le cache Docker
COPY composer.json composer.lock ./

# Installer les dépendances Composer en ajoutant de la verbose pour 
# savoir ce qu'il se passe (aka pk ça marche pas)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --verbose

# Copier le reste de l'application
COPY . /var/www/html


# --- Étape de Production (final) ---
FROM php:8.3-apache

# Installer les extensions PHP et les dépendances système MINIMALES pour l'exécution
RUN apt-get update && apt-get install -y \
    # git et unzip non nécessaire en prod
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo_mysql

# Activer le module Apache rewrite
RUN a2enmod rewrite

# Copier uniquement les fichiers nécessaires depuis l'étape de build
COPY --from=builder /var/www/html /var/www/html

# Copier la configuration Apache spécifique (merci GPT)
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Donner les droits appropriés
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} +

# Exposer le port
EXPOSE 80

# Démarrer Apache en premier plan
CMD ["apache2-foreground"]