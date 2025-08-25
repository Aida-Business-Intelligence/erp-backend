FROM php:8.1-apache-bullseye

# Habilita mod_rewrite
RUN a2enmod rewrite

# Habilita 'contrib' e 'non-free' nos repositórios do Debian 11
RUN set -eux; \
    sed -ri 's/bullseye main\b/bullseye main contrib non-free/g; \
             s/bullseye-updates main\b/bullseye-updates main contrib non-free/g; \
             s/bullseye-security main\b/bullseye-security main contrib non-free/g' /etc/apt/sources.list

# Dependências e extensões
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        zip unzip \
        libzip-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libxml2-dev libicu-dev libtidy-dev libsnmp-dev \
        libpspell-dev aspell-en \
        libcurl4-openssl-dev libssl-dev \
        libc-client2007e-dev libkrb5-dev \
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl; \
    docker-php-ext-install -j"$(nproc)" \
        zip pdo pdo_mysql mysqli mbstring soap curl gd imap \
        pspell snmp tidy intl bcmath \
    ; \
    rm -rf /var/lib/apt/lists/*

# App
WORKDIR /var/www/html
COPY . /var/www/html/
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install || true

# Permissões e porta
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
