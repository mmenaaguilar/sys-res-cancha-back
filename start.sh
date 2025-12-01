#!/bin/bash
# Script de inicio para Render (Web Service con Docker)

echo "Iniciando el gestor de procesos PHP-FPM..."

# CRÍTICO: El comando 'exec php-fpm -F' inicia el proceso de PHP
# y lo mantiene en el 'foreground' (-F).
# Si no usamos -F, el proceso terminaría inmediatamente, y Render vería
# que el contenedor se ha apagado, resultando en un error de despliegue.
exec php-fpm -F