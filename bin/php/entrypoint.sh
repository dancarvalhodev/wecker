#!/bin/sh

SOCK=/var/run/docker.sock

if [ -S "$SOCK" ]; then
    GID=$(stat -c '%g' "$SOCK")

    if ! getent group "$GID" > /dev/null 2>&1; then
        addgroup -g "$GID" dockersock
    fi

    addgroup www-data "$(getent group "$GID" | cut -d: -f1)"
fi

exec php-fpm