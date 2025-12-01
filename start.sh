#!/usr/bin/env bash

# 1. Instalar dependencias de Composer y generar el autoload.php
# El --optimize-autoloader es CRÍTICO para arreglar el Class Not Found.
composer install --no-dev --optimize-autoloader

# 2. Iniciar el servidor web Nginx/PHP-FPM
# Ejecuta el proceso de inicio de FPM en primer plano
/usr/sbin/php-fpm7.4 -F