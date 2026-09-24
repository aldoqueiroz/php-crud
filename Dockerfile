FROM php:8.3-apache

# Instala a extensão PDO SQLite
RUN apt-get update && apt-get install -y \
        libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite (opcional, útil no futuro)
RUN a2enmod rewrite

# Diretório onde o banco SQLite será armazenado
RUN mkdir -p /var/www/data && chown -R www-data:www-data /var/www/data

EXPOSE 80