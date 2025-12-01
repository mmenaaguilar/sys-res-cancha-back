# Usar la imagen base de PHP con FPM (FastCGI Process Manager)
# Usamos 'alpine' para que la imagen sea ligera y eficiente.
FROM php:8.1-fpm-alpine

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
# Aquí es donde se copiará tu código y se ejecutarán los comandos.
WORKDIR /var/www/html

# 4. Copiar los archivos de tu repositorio al contenedor.
# El primer '.' es la carpeta de origen (todo tu proyecto); el segundo '.' es la carpeta de destino (/var/www/html).
COPY . .

# 5. Instalar dependencias de PHP usando Composer.
# --no-dev: Omite dependencias de desarrollo para producción.
# --optimize-autoloader: Mejora la velocidad de carga de clases.
RUN composer install --no-dev --optimize-autoloader

# 6. Exponer el puerto por defecto de PHP-FPM
# Este puerto es el que escucha Nginx o el servidor web.
EXPOSE 9000

# NOTA: El comando de inicio (ENTRYPOINT/CMD) está especificado en Render como 'start.sh', 
# por lo que no es necesario definir CMD aquí.