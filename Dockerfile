FROM php:8.0-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    apache2 \
    libapache2-mod-fcgid \
    && docker-php-ext-install pdo_mysql gd mbstring zip \
    && a2enmod rewrite \
    && a2enmod proxy_fcgi setenvif

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de la aplicación
COPY . .

# Ajustar permisos
RUN chown -R www-data:www-data /var/www/html

# Configuración del servidor Apache
COPY ./apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Exponer los puertos
EXPOSE 80

# Iniciar Apache en segundo plano y luego PHP-FPM
CMD ["apache2-foreground"]
