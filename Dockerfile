# Usar la imagen base de PHP con FPM (FastCGI Process Manager)
# CAMBIO CRÍTICO: Se actualiza a PHP 8.2 para cumplir con el requisito de composer.json
FROM php:8.2-fpm-alpine

# 1. Instalar dependencias del sistema y extensiones de PHP.
# git es necesario para Composer.
# mysql-client es necesario para la extensión pdo_mysql.
# pdo_mysql es CRÍTICO para conectarse a la base de datos MySQL.
RUN apk add --no-cache \
    git \
    mysql-client \
    # Instalar la extensión de MySQL para PHP
    && docker-php-ext-install pdo_mysql

# 2. Instalar Composer (la herramienta de gestión de dependencias de PHP)
# Copiamos la versión más reciente de una imagen oficial de Composer.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Establecer el directorio de trabajo dentro del contenedor.
WORKDIR /var/www/html

# 4. Copiar los archivos de tu repositorio al contenedor.
COPY . .

# 5. Instalar dependencias de PHP usando Composer.
RUN composer install --no-dev --optimize-autoloader

# 6. Exponer el puerto por defecto de PHP-FPM
EXPOSE 9000