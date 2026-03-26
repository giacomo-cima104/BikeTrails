FROM php:8.2-apache

# Abilita mysqli
RUN docker-php-ext-install mysqli

# Copia tutti i file nella cartella web
COPY . /var/www/html/

# Permessi corretti
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
