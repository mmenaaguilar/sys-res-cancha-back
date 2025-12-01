# --------------------------------------------------------------------------------
# ETAPA 1: Construcción (PHP con dependencias)
# --------------------------------------------------------------------------------
# Usar la imagen base de PHP 8.2 con FPM (FastCGI Process Manager)
FROM php:8.2-fpm-alpine AS builder

# 1. Instalar dependencias del sistema y extensiones de PHP.
RUN apk add --no-cache \
    git \
    mysql-client \
    # Instalar la extensión de MySQL para PHP
    && docker-php-ext-install pdo_mysql

# 2. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Establecer el directorio de trabajo y copiar archivos.
WORKDIR /var/www/html
COPY . .

# 4. Instalar dependencias de PHP usando Composer.
RUN composer install --no-dev --optimize-autoloader

# --------------------------------------------------------------------------------
# ETAPA 2: Producción (PHP-FPM + NGINX)
# --------------------------------------------------------------------------------
# Usamos una imagen base más completa que incluye Nginx
FROM wodby/nginx:1.24-alpine-3.18-5.3

# 1. Copiar el código de la aplicación (incluyendo /vendor y /public)
# El directorio de trabajo ya está configurado en /var/www/html en esta imagen base
WORKDIR /var/www/html
COPY --from=builder /var/www/html .

# 2. Copiar los archivos de configuración de PHP-FPM
# Necesitamos que la imagen de Nginx sepa dónde está PHP-FPM (que está dentro del mismo contenedor)
# Esta imagen de Wodby ya trae un PHP-FPM que podemos usar.

# 3. Configuración CRÍTICA de Nginx:
# Creamos la configuración para que Nginx sepa que la raíz es /public
RUN echo "server { \
    listen 80; \
    root /var/www/html/public; \
    index index.php index.html; \
    location / { \
        try_files \$uri \$uri/ /index.php?\$query_string; \
    } \
    location ~ \.php$ { \
        try_files \$uri /index.php =404; \
        fastcgi_split_path_info ^(.+\.php)(/.+)$; \
        # El FPM ahora lo ejecutaremos en segundo plano dentro del mismo contenedor, en el puerto 9000
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name; \
        fastcgi_param PATH_INFO \$fastcgi_path_info; \
    } \
}" > /etc/nginx/conf.d/default.conf

# 4. Exponer el puerto que Nginx está escuchando (Render lo necesita)
EXPOSE 80

# 5. El punto de entrada será el script 'start.sh'
ENTRYPOINT ["/bin/sh", "./start.sh"]