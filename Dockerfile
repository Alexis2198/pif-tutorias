FROM php:8.2-apache

# Extensión PDO para MySQL. mysqli no es necesaria.
RUN docker-php-ext-install pdo_mysql

# El docroot de la app es public/, no la raíz del proyecto.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY . /var/www/html/

# Railway expone el puerto vía la variable PORT; Apache escucha 80 por defecto
# y Railway enruta hacia él. No se requiere ajuste adicional para el plan estándar.
EXPOSE 80
