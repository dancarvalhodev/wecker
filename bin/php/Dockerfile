FROM dancarvalhodev/php-alpine:8.5

RUN apk add postgresql-dev php85-pdo_pgsql php85-pgsql
RUN docker-php-ext-install pdo_pgsql pgsql

RUN apk add --no-cache tzdata
ENV TZ=America/Sao_Paulo
RUN cp /usr/share/zoneinfo/${TZ} /etc/localtime