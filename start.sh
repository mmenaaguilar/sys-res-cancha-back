#!/usr/bin/env bash

# === CONFIGURACIÓN MÁS ROBUSTA PARA RENDER ===

# 1. Instalar dependencias de Composer y generar el autoload.php
# Forzamos la instalación de dependencias antes de iniciar.
composer install --no-dev --optimize-autoloader

# 2. Iniciar el servidor web interno de PHP (más simple que Nginx/PHP-FPM)
# Render inyecta el puerto en la variable $PORT (generalmente 10000)
# Usamos 'public/index.php' como el script router para que maneje todas las peticiones
# a la carpeta 'public/'.

echo "Iniciando el servidor PHP en el puerto $PORT..."
php -S 0.0.0.0:"$PORT" -t public/