FROM php:8.2-apache

# Timezone Roma
ENV TZ=Europe/Rome
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Abilita mysqli
RUN docker-php-ext-install mysqli

# Configura timezone PHP
RUN echo "date.timezone = Europe/Rome" > /usr/local/etc/php/conf.d/timezone.ini

# Copia tutti i file nella cartella web
COPY . /var/www/html/

# Permessi corretti
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
