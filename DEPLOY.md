# Deploy
ES
---
## Local
_Pre requisitos_:
- Motor de base de datos MySQL en local.


Instrucciones para levantar el proyecto Laravel Local:

:information_source:
> Ejecuta los siguientes comandos con sudo o root. 

1) Crear el directorio si no existe:
    ```bash
        mkdir -p /opt/docker
    ```

2) Clonar el repositorio remoto en tu pc local.
    ```bash
        cd /opt/docker/
    ```
    ```bash
        git clone https://github.com/francodevarg/known-online-challenge/tree/master /opt/docker/known-online-challenge
    ```
3) Crear variable de Entorno local (.env) : Copiar los datos del .env.example al .env

    ```bash
        cp .env.local .env 
    ```
4) Agregar las credenciales de VTEX a las variables de Entorno:
    
    ```bash
        VTEX_ACCOUNT_NAME=
        VTEX_ENVIRONMENT=
        VTEX_API_APP_KEY=
        VTEX_API_APP_TOKEN=
    ```

5) Agregar la conección a MYSQL Local:
    
    ```bash
        DB_CONNECTION=
        DB_HOST=
        DB_PORT=
        DB_DATABASE=
        DB_USERNAME=
        DB_PASSWORD=
    ```

6) Correr Script de deploy
    ```bash
        cd /opt/docker/known-online-challenge/docker-files/scripts
    ```

    ```bash
        ./deploy-local.sh
    ```
   Este proceso demora 1 o 2 minutos. Podes verificar el estado mirando logs.

    ```bash
        docker logs -f known-online-challenge-local
    ```
7) Correr migraciones en la DB:
    
    ```bash
        php artisan migrate
    ```
    Podes hacerlo dentro del contenedor, ingresando con :

    ```bash
        docker exec -it known-online-challenge-local bash
    ```

9) Abrir el navegador http://known-online-challenge.local/
