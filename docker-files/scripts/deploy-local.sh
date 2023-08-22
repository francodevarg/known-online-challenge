#!/bin/bash
PROJECT=known-online-challenge
GIT_DOCKER_DIR=/opt/docker/$PROJECT

echo "Insertando config en /etc/hosts"
grep -i $PROJECT.local /etc/hosts
if [ $? -ne 0 ];
then
echo "127.0.0.1 $PROJECT.local" >> /etc/hosts
fi

echo "Levantando contenedor"
cd $GIT_DOCKER_DIR && docker compose -f docker-compose-local.yml up -d