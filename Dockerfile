FROM php:8.0-apache

# Instalar dependencias adicionales si es necesario
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
    && docker-php-ext-install pdo_mysql gd mbstring zip

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de la aplicación
COPY . .

# Ajustar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer los puertos
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
