#!/bin/bash
DIRECTORY_VENDOR=$APACHE_DOCUMENT_ROOT/vendor
LOG=/tmp/entrypoint.out
if [ -d "$DIRECTORY_VENDOR" ]; then
composer install --working-dir=$APACHE_DOCUMENT_ROOT
echo "$DIRECTORY_VENDOR existe." > $LOG
else
composer install --working-dir=$APACHE_DOCUMENT_ROOT
fi

chmod -R 777 $APACHE_DOCUMENT_ROOT

apache2ctl -D FOREGROUND