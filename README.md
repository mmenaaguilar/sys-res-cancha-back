
# SysReserCancha Back-end

Este es el repositorio del servicio de **Back-end** para el Sistema de Reserva de Canchas Deportivas. Está construido en PHP nativo utilizando principios de Programación Orientada a Objetos (POO) y el patrón MVC simplificado, con Composer para la gestión de dependencias.

## Requisitos del Sistema

Para ejecutar este proyecto en tu entorno local, necesitas tener instalado lo siguiente:

  * **PHP:** Versión 8.0 o superior.
  * **Servidor Web:** Se recomienda usar el servidor de desarrollo de PHP o Apache/Nginx.
  * **Base de Datos:** MySQL o MariaDB.
  * **Gestor de Dependencias:** [Composer](https://getcomposer.org/)

-----

## Configuración y Puesta en Marcha

Sigue estos pasos para dejar el sistema listo y funcionando.

### 1\. Clonar el Repositorio

Clona este repositorio en tu máquina y navega hasta el directorio del proyecto:

```bash
git clone https://github.com/[tu-usuario]/sys-res-cancha-back.git
cd sys-res-cancha-back
```

### 2\. Instalar Dependencias

Usa Composer para instalar todas las librerías necesarias del proyecto (incluyendo `phpdotenv`, `vlucas/phpdotenv` y otras).

```bash
composer install
```

### 3\. Configurar Variables de Entorno

El proyecto usa un archivo `.env` para la configuración sensible.

### 4\. Crear la Base de Datos

Ejecuta el script SQL para crear la estructura de la base de datos y todas las tablas necesarias:

1.  Abre tu cliente de MySQL (MySQL Workbench, HeidiSQL, o la consola).
2.  Ejecuta el contenido del archivo **`docs/database/sistema_reservas_canchas.sql`** (o donde sea que tengas tu script de creación de tablas).

### 5\. Iniciar el Servidor de Desarrollo

Inicia el servidor de desarrollo de PHP desde el directorio raíz del proyecto. **Es crucial especificar el directorio `public/` como raíz del documento para que el *router* funcione.**

```bash
php -S localhost:8000 -t public
```

El servidor estará disponible en: **`http://localhost:8000`**

-----
