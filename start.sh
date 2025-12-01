#!/bin/sh

# 1. Iniciar PHP-FPM en segundo plano.
# Usamos el binario específico 'php-fpm82' para garantizar que se ejecute la versión 8.2 instalada 
# en la imagen Alpine (se ejecuta en background con -D).
echo "Iniciando gestor de procesos PHP-FPM (php-fpm82) en puerto 9000..."
php-fpm82 -D

# 2. Iniciar NGINX en primer plano (foreground).
# Nginx escuchará en el puerto 80, y el comando 'exec' asegura que el contenedor 
# permanezca vivo, resolviendo el error de 'Port scan timeout'.
echo "Iniciando servidor web NGINX en puerto 80..."
exec nginx -g "daemon off;"