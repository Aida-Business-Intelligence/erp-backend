FROM php:8.1-apache

# Habilita mod_rewrite
RUN a2enmod rewrite

# Dependências para extensões
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        zip unzip \
        libzip-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libxml2-dev libicu-dev libtidy-dev libsnmp-dev \
        libpspell-dev aspell-en \
        libcurl4-openssl-dev libssl-dev \
        uw-imap-dev libkrb5-dev \
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl; \
    docker-php-ext-install -j"$(nproc)" \
        zip pdo pdo_mysql mysqli mbstring soap curl gd imap \
        pspell snmp tidy intl bcmath \
    ; \
    rm -rf /var/lib/apt/lists/*

# (Opcional) ajustar AllowOverride para .htaccess no Apache
# RUN sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# Código
WORKDIR /var/www/html
COPY . /var/www/html/

# php.ini custom
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install || true

# Permissões e porta
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
