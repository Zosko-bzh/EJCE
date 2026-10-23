FROM php:8.2-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP basiques
RUN docker-php-ext-install pdo pdo_mysql intl zip mbstring

# Configurer les limites d'upload PHP
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/uploads.ini

# Installer l'extension MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer un utilisateur pour éviter de lancer composer en root (optionnel mais recommandé)
RUN adduser -disabled-password -gecos "" appuser
USER appuser

WORKDIR /var/www/html
