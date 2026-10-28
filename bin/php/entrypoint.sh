#!/bin/sh

SOCK=/var/run/docker.sock

if [ -S "$SOCK" ]; then
    GID=$(stat -c '%g' "$SOCK")

    if ! getent group "$GID" > /dev/null 2>&1; then
        groupadd -g "$GID" dockersock
    fi

    usermod -aG "$(getent group "$GID" | cut -d: -f1)" www-data
fi

exec php-fpm
