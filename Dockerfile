# Utilise une image officielle PHP 8.1 avec le serveur Apache
FROM php:8.1-apache

# Installer les dépendances système nécessaires pour Symfony et PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip intl

# Installer Composer (le gestionnaire de paquets PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail dans le conteneur
WORKDIR /var/www/html

# Copier les fichiers de dépendances et les installer
# On fait ça en premier pour profiter du cache Docker si les dépendances ne changent pas
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Copier le reste du code de l'application
COPY . .

# Exécuter les scripts post-installation de Symfony (comme la création du .env.local)
RUN composer run-script post-install-cmd

# Changer le propriétaire des fichiers pour que le serveur web puisse écrire dans les logs et le cache
RUN chown -R www-data:www-data var

# Configurer Apache pour pointer vers le dossier public/ de Symfony, qui est le point d'entrée
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
