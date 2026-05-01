#!/usr/bin/env bash

set -e

case "$1" in

reset)
    echo "🧹 Limpando ambiente..."

    docker compose down -v --remove-orphans

    echo "🗑️ Removendo containers parados"
    docker container prune -f

    echo "🗑️ Removendo imagens"
    docker image prune -f

    echo "✔ Ambiente limpo"
;;

nuke)
    echo "☢️ Reset TOTAL do Docker"

    docker compose down -v --remove-orphans

    echo "⚠️ Executando docker system prune"

    docker system prune -af --volumes

    echo "🔥 Docker limpo completamente"
;;

setup)
    echo "📦 Setup do projeto..."

    if [ ! -f .env ]; then
        cp env-example .env
        echo "✔ .env criado"
    fi

    if [ ! -f www/.env ]; then
        cp www/env-example www/.env
        echo "✔ www/.env criado"
    fi

    echo "✔ Setup concluído"
;;

up)
    echo "🚀 Ambiente de desenvolvimento"

    docker compose up -d --build

    echo "✔ Containers rodando"
    echo "🌐 http://localhost"
;;

stop)
    echo "🛑 Parando containers"
    docker compose down
;;

logs)
    docker compose logs -f
;;

*)
    echo ""
    echo "Uso:"
    echo "  ./run setup   -> prepara .env"
    echo "  ./run up     -> iniciar sistema"
    echo "  ./run stop    -> parar containers"
    echo "  ./run logs    -> ver logs"
    echo "  ./run reset    -> limpa o docker do projeto"
    echo "  ./run nuke    -> limpa o docker do sistema"
    echo ""
;;

esac