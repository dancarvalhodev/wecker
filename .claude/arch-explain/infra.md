# Infraestrutura (Docker Compose)

## O que é

O Wecker roda em quatro containers, descritos no `docker-compose.yml` da raiz: **caddy** (servidor
web), **php** (php-fpm com o código), **postgres** (banco) e **redis** (sessões). Um `just up`
(`docker compose up -d --build`) constrói as imagens e sobe tudo.

## Analogia

Um **condomínio**.

- O `docker-compose.yml` é a **planta do condomínio**: quantos apartamentos (containers), o que
  cada um tem e como se ligam.
- Cada **imagem** (`bin/*/Dockerfile`) é a **planta de um apartamento**: o que vem instalado de
  fábrica.
- A **rede interna** do Compose é o **interfone do condomínio**: os apartamentos se chamam pelo nome
  (`php`, `postgres`, `redis`), sem precisar de endereço.
- As **portas publicadas** (`ports:`) são as **portas que dão para a rua**: só o que está ali é
  acessível de fora da máquina.
- Os **volumes** são **depósitos compartilhados** entre o apartamento e a sua casa: a pasta `www/`
  aparece dentro do container `php`, e o que muda de um lado aparece do outro.

## Como funciona aqui

### Os quatro serviços

| Serviço | Imagem | Porta para a rua | Volumes | Papel |
|---|---|---|---|---|
| `caddy` | `caddy:2.11.7-alpine` | `${PORT_HTTP}:80` | `www/public` (leitura), `Caddyfile` (leitura) | Recebe o HTTP, serve estáticos, repassa o resto ao `php:9000` |
| `php` | `ubuntu:24.04` + `ppa:ondrej/php` | nenhuma | `www/` → `/var/www/html`, `php.ini`, `docker.sock` | Roda o PHP 8.5 (php-fpm), o Composer e os scripts de `bin/` |
| `postgres` | `postgres:18-alpine` | nenhuma | `data/postgres` | Banco `wecker` (e `wecker_test`) |
| `redis` | `redis:8.4-alpine` | nenhuma | `data/redis` | Guarda as sessões PHP |

Os nomes dos containers levam o prefixo `${CONTAINER_NAME}` (`wecker_`): `wecker_php`,
`wecker_caddy`... Nenhum tem política de `restart`: depois de reiniciar a máquina, suba com
`just up`.

### As duas configurações de ambiente

| Arquivo | Lido por | Conteúdo |
|---|---|---|
| `.env` (raiz, cópia do `env-example`) | Docker Compose | Prefixo dos containers, portas e usuário/senha do Postgres |
| `www/.env` (cópia do `www/env-example`) | O app (vlucas/phpdotenv) | `DB_*`: como o PHP se conecta ao banco (`DB_HOST="postgres"`, o nome do serviço) |

O `just setup` cria os dois a partir dos exemplos. Os dois estão no `.gitignore`.

### O container `php` por dentro

- **`bin/php/Dockerfile`:** Ubuntu 24.04, o PPA do Ondřej Surý (que empacota as versões novas do
  PHP para Ubuntu) e só as extensões que o projeto usa (curl, intl, mbstring, pgsql, redis, xdebug,
  xml, zip). O OPcache já vem embutido no PHP 8.5. O Composer é copiado da imagem oficial
  `composer:2`.
- **`bin/php/fpm-pool.conf`** (copiado como `pool.d/zz-docker.conf`): faz o php-fpm escutar em
  TCP `9000` (o Caddy está em outro container e não enxerga um socket local), ficar em primeiro
  plano (senão o container encerra) e mandar os logs, inclusive o `error_log()` do código, para a
  saída do container.
- **`config/php/php.ini`** (montado como `conf.d/99-wecker.ini` no fpm e no cli): sessões no Redis,
  Xdebug em modo `trigger` e `error_reporting` sem deprecações.
- **`bin/php/entrypoint.sh`:** antes de iniciar o fpm, descobre o grupo dono do
  `/var/run/docker.sock`, cria esse grupo se ele não existir e põe o `www-data` nele. É o que
  permite ao PHP falar com o Docker pelo socket (ver [docker-client.md](docker-client.md)).
- O container roda como `root` (`user: root`): o processo principal do fpm é root, e os workers que
  executam o PHP rodam como `www-data`.

### O Caddy (`config/caddy/Caddyfile`)

| Diretiva | O que faz |
|---|---|
| `root * /var/www/html/public` | A pasta servida (o `www/public` montado). |
| `log` | Log de acesso em JSON na saída do container. |
| `request_body { max_size 100MB }` | Limite de tamanho do corpo da requisição. |
| `@trailingSlash` + `redir ... 301` | `/login/` → `/login`, exceto se o caminho for um arquivo real. |
| `@hidden path */.ht*` + `respond 403` | Bloqueia `.htaccess` e afins. |
| `@static` + `header Cache-Control` | Cache de 1 ano para CSS, JS, imagens e fontes. |
| `php_fastcgi php:9000` | Repassa ao php-fpm. |
| `file_server` | Entrega os arquivos estáticos. |

O `php_fastcgi` já implementa o padrão "front controller": se o arquivo pedido existe em `public/`,
o `file_server` o entrega; se não, a requisição vai para o `index.php`.

## Pegadinhas

- **Postgres e Redis não têm porta para a rua.** Só a rede interna os alcança. Para abrir o banco
  numa ferramenta do host, use `just shell postgres` (ou publique a porta só na sua máquina, sem
  versionar).
- **Montar o `docker.sock` dá poder total.** Quem controla o PHP controla o Docker da máquina
  inteira: pode criar um container com o disco do host montado. Por isso o login e as permissões
  importam tanto.
- **Editou o `php.ini` ou o `Caddyfile`? Recrie, não reinicie.** São arquivos únicos montados, e no
  Docker Desktop com WSL o mount fica preso ao arquivo antigo quando o editor salva um arquivo novo.
  `docker compose restart php` falha; use `docker compose up -d --force-recreate php` (ou `caddy`).
- **Não volte com o `restart: always`.** No Docker Desktop com WSL, ele faz os containers subirem
  no boot antes de as pastas do projeto estarem disponíveis: as montagens caem em diretórios vazios
  da VM, o `php` e o `caddy` ficam `Exited (127)` e o Postgres cria um banco novo fora de
  `data/postgres` (os dados reais continuam lá, intactos). Recriar o container
  (`docker compose up -d --force-recreate`) volta a apontar para a pasta certa.
- **`depends_on` só ordena a subida.** Não espera o serviço estar pronto. Por isso o `just migrate`
  usa o `_wait-db` antes de falar com o Postgres (ver [migrations.md](migrations.md)).
- **Xdebug não liga sozinho.** Em modo `trigger`, só depura com o cookie ou o parâmetro
  `XDEBUG_TRIGGER`. O `client_host=172.17.0.1` é o gateway da rede padrão do Docker no Linux; no
  Docker Desktop, troque pela linha comentada `host.docker.internal`.
- **Rebuild só quando muda a imagem.** Mudou um `Dockerfile`, o `fpm-pool.conf` ou o
  `entrypoint.sh` (copiados para dentro da imagem)? `just up` reconstrói, porque já usa `--build`.
  Mudou o código em `www/`? Não precisa de nada: o volume já mostra a mudança.

## Comandos úteis

```bash
just up | stop | status | logs    # o dia a dia
just shell                        # sh dentro do php
just shell postgres               # sh dentro do postgres
docker compose up -d --force-recreate php    # depois de editar o php.ini
docker compose exec php php -m    # extensões carregadas
docker compose exec php php --ini # quais .ini foram lidos
docker compose exec caddy caddy validate --config /etc/caddy/Caddyfile
```
