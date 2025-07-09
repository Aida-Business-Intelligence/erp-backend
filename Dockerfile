FROM php:8.1-apache

# rewrite
RUN a2enmod rewrite

# testando dependencias aida
RUN apt-get update && apt-get install -y \
    zip unzip \
    libzip-dev libpng-dev libjpeg-dev libonig-dev \
    libxml2-dev libicu-dev libtidy-dev libsnmp-dev \
    libpspell-dev libcurl4-openssl-dev libssl-dev \
    libc-client-dev libkrb5-dev \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install \
    zip pdo pdo_mysql mysqli mbstring soap curl gd imap \
    pspell snmp tidy intl bcmath

#xmlrpc deu erro
### copia o projeto
COPY . /var/www/html/
WORKDIR /var/www/html

# composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install || true

#porta
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
