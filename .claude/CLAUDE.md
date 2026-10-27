# CLAUDE.md

Orientações para o Claude Code (claude.ai/code) neste repositório.

## O que é

Wecker é uma interface web, inspirada no Portainer, para gerenciar containers Docker. Um app PHP (Slim 4) fala com a API HTTP do Docker Engine para listar, iniciar e parar containers e ler logs, atrás de um login por sessão. A stack inteira (caddy, php-fpm, postgres, redis) roda no Docker Compose. O código da aplicação fica em `www/`.

## Comandos

Tudo passa pelo `justfile` ([just](https://just.systems), na raiz). `just` sem argumentos lista as receitas:

```bash
just install          # primeira vez: setup + up + composer install + migrate + criar admin
just up | stop | status | logs
just shell [serviço]  # sh dentro de um container (padrão: php)
just migrate          # aplica as migrações no banco do app e no wecker_test
just db-reset         # recria os dois bancos a partir das migrações (pede confirmação)
just admin-create     # cria um admin (interativo)
just test [filtro]    # PHPUnit
just stan             # PHPStan (nível 6)
just cs | cs-fix      # PHP-CS-Fixer (PER-CS 2.0)
```

PHP e Composer rodam **dentro do container `php`** (PHP 8.5 no Ubuntu 24.04, código em `/var/www/html`). Migração nova = arquivo `.sql` numerado novo em `www/database/migrations/`; nunca edite uma que já rodou.

## Antes de mexer no código

- Convenções de código: @rules/code-style.md
- Como cada parte funciona: `.claude/arch-explain/`, um arquivo por assunto, começando por `request-flow.md` (`di`, `auth`, `docker-client`, `error-handling`, `frontend`, `infra`, `testing`, `just`, `migrations`). Leia o do assunto antes de mexer nele, e atualize-o só quando a mudança invalidar o que está escrito.
- Plano em execução: `.claude/plans/v2.md`; onde paramos: `.claude/handoff.md`.

<!-- CODEGRAPH_START -->
## CodeGraph

Em repositórios indexados pelo CodeGraph (existe `.codegraph/` na raiz), use-o ANTES de grep/find ou de ler arquivos para entender ou localizar código:

- **Ferramenta MCP** (quando disponível): `codegraph_explore` responde a maioria das perguntas numa chamada: o código-fonte literal dos símbolos relevantes e os caminhos de chamada entre eles. Cite um arquivo ou símbolo na consulta para ler o código atual com número de linha. Se estiver listada como adiada, carregue-a pelo nome na busca de ferramentas.
- **Shell** (sempre funciona): `codegraph explore "<símbolos ou pergunta>"` imprime o mesmo resultado.

Se não houver `.codegraph/`, ignore o CodeGraph: indexar é decisão do usuário.
<!-- CODEGRAPH_END -->
