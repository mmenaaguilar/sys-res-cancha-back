# --------------------------------------------------------------------------------
# Solución Final: Usar Nginx como base e instalar PHP y dependencias.
# Esto asegura que Nginx esté configurado por defecto para escuchar en el puerto 80.
# --------------------------------------------------------------------------------

# Usar una imagen base de Nginx + Alpine (muy ligera y estable)
FROM nginx:1.25-alpine

# 1. Instalar las dependencias necesarias de PHP y otras herramientas.
# Incluye paquetes para PHP, PHP-FPM, Composer, y extensiones de MySQL.
RUN apk add --no-cache \
    php82 \
    php82-fpm \
    php82-mysqli \
    php82-pdo \
    php82-pdo_mysql \
    php82-json \
    php82-curl \
    php82-mbstring \
    php82-xml \
    php82-tokenizer \
    php82-fileinfo \
    php82-dom \
    composer \
    git \
    mysql-client

# 2. Configurar el directorio de trabajo
WORKDIR /var/www/html

# 3. Eliminar la configuración predeterminada de Nginx
RUN rm /etc/nginx/conf.d/default.conf

# 4. Configuración CRÍTICA de Nginx para el proyecto (public/index.php)
# Nginx escuchará en el puerto 80 y pasará las peticiones PHP a PHP-FPM (127.0.0.1:9000).
RUN echo "server { \
    listen 80; \
    root /var/www/html/public; \
    index index.php index.html; \
    \
    location / { \
        try_files \$uri \$uri/ /index.php?\$query_string; \
    } \
    \
    location ~ \.php$ { \
        try_files \$uri /index.php =404; \
        fastcgi_split_path_info ^(.+\.php)(/.+)$; \
        # PHP-FPM se ejecuta en el puerto 9000 localmente en este mismo contenedor
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name; \
        fastcgi_param PATH_INFO \$fastcgi_path_info; \
    } \
}" > /etc/nginx/conf.d/app.conf

# 5. Copiar los archivos de tu repositorio al contenedor
COPY . .

# 6. Instalar dependencias de PHP usando Composer.
RUN composer install --no-dev --optimize-autoloader

# 7. Asegurar que el script de inicio tenga permisos de ejecución
RUN chmod +x start.sh

# 8. Exponer el puerto 80 (Nginx) para Render
EXPOSE 80

# 9. Definir el comando de inicio que ejecuta el script 'start.sh'
CMD ["/bin/sh", "./start.sh"]