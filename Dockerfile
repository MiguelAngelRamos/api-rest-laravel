# Usar la imagen oficial de PHP 8.3.12 con Apache
FROM php:8.3.12-apache

# Establecer el directorio de trabajo en /var/www/html
WORKDIR /var/www/html

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar los archivos del proyecto al contenedor
COPY . /var/www/html

# Asignar permisos correctos
RUN chown -R www-data:www-data /var/www/html

# Habilitar el módulo de reescritura de Apache
RUN a2enmod rewrite

# Exponer el puerto 80 para Apache
EXPOSE 80

# Comando por defecto al ejecutar el contenedor
CMD ["apache2-foreground"]
