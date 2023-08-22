# Deploy
---
ES

### Local
Pre requisitos:
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

4) Correr Script de deploy
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
5) Abrir el navegador http://known-online-challenge.local/