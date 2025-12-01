#!/bin/sh

# 1. Iniciar PHP-FPM en segundo plano.
# Lo iniciamos en el background (-D) para que no bloquee el inicio de Nginx.
echo "Iniciando gestor de procesos PHP-FPM en puerto 9000..."
php-fpm -D

# 2. Iniciar NGINX en primer plano (foreground).
# Nginx escuchará en el puerto 80, que es el que Render espera ver.
# El comando 'exec' es CRÍTICO: reemplaza el shell actual con Nginx, 
# lo que asegura que Nginx es el proceso principal del contenedor y no se apagará.
echo "Iniciando servidor web NGINX en puerto 80..."
exec nginx -g "daemon off;"