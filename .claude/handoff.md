# Passagem de sessão

Resumo para continuar o desenvolvimento em outro computador (ou voltar a este). Quem terminar uma
sessão atualiza este arquivo; quem começar lê este arquivo primeiro.

Última atualização: 2026-10-23.

## Onde paramos

- **Plano em execução:** `.claude/plans/v2.md` (19 etapas, branch `feature/v2`), executado com a
  skill `modo-codando` (`.claude/skills/modo-codando/SKILL.md`).
- **Etapa 1 (skill `modo-codando` + este arquivo):** confirmada em 2026-10-20.
- **Etapa 2 (ferramentas de qualidade):** confirmada em 2026-10-20.
  `docker compose exec php composer test | stan | cs | cs:fix` funcionam. Estado de partida:
  testes verdes (1 placeholder), `stan` com 39 erros (nível 6) e `cs` com 27 de 29 arquivos a
  corrigir. Os dois devem ser zerados nas etapas 7–10. Guzzle fixado em `^8.3` (estável), por
  decisão do dev. O `www/composer.lock` passou a ser versionado (saiu do `.gitignore`).
- **Etapa 3 (`run.sh` aprimorado → `justfile`):** confirmada em 2026-10-21. Por decisão do dev,
  o `run.sh` foi trocado por um `justfile` ([just](https://just.systems), `sudo apt install
  just`). O `just` não aceita `:` em nomes: os comandos são `cs-fix`, `db-reset` e
  `admin-create`.
- **Etapa 4 (migrações):** confirmada em 2026-10-23. Migrações em
  `www/database/migrations/`, lógica em `App\Database\Migrator` e `ConnectionFactory`, I/O de
  terminal em `App\Console\Terminal`, container compartilhado em
  `App\Bootstrap\ContainerFactory`. `www/bin/migrate.php` (`--fresh` recria o banco) e
  `www/bin/admin-create.php` (`--if-missing` usado no `just install`) são só portas de entrada:
  por pedido do dev, a lógica fica em classes de `src/`, e não em scripts soltos.
- **Etapa 4b (incluída em 2026-10-23, a pedido do dev):** PHP em Ubuntu 24.04 + PPA do Ondřej e
  nginx → Caddy. Decisões: só HTTP local, logs do Caddy no stdout, `PORT_NGINX` → `PORT_HTTP`,
  código em `/var/www/html`. Confirmada em 2026-10-23. `just migrate`
  aplica no banco do app e no `wecker_test`. Por decisão do dev, o `admin-create` **não tem
  validação** (MVP); o `UNIQUE` do e-mail é a única barreira. A pedido do dev, a documentação
  começou antes da etapa 6: `.claude/arch-explain/just.md` e `migrations.md`. A linha
  `xdebug.log` saiu do `php.ini` (fpm e cli disputavam o `/tmp/xdebug.log`).
- **Etapa 5 (skill `modernize-php`):** descartada em 2026-10-23 por decisão do dev — só rodaria
  uma vez. A etapa 9 gera o relatório direto (critérios no plano), e a regra "clareza > código
  rebuscado" entra no `code-style.md` na etapa 6.
- **Etapa 6 (documentação do estado atual):** entregue em 2026-10-23, aguardando conferência.
  `arch-explain/`: `request-flow`, `di`, `auth`, `docker-client`, `error-handling`, `frontend`,
  `infra` (além de `just` e `migrations`). Decisões do dev (2026-10-23): as
  specs de `docs/specs/` são escritas no início de cada etapa de feature (14–18), e o
  `arch-explain/testing.md` vai para a etapa 7.

## Decisões da entrevista (2026-10-19)

O plano tem o detalhe; aqui só o resumo.

- **Permissões granulares:** tabela `permissions` + `role_permissions`, editável pela UI. As chaves
  são fixas no código (enum); a UI só muda quais papéis têm quais chaves.
- **Cadastro:** só o admin cria usuários; o `/register` público sai.
- **Criar container (essencial):** imagem (pull se faltar), nome, portas, env vars, restart policy,
  "iniciar após criar".
- **Testes:** Docker mockado (Guzzle `MockHandler`) + Postgres real no banco `wecker_test`,
  recriado a cada execução.
- **Idioma:** código e UI em inglês; documentação e specs em pt-BR.

## Como o dev quer trabalhar

- **Uma etapa por vez, com a skill `modo-codando`:** entregar, dizer como conferir, perguntar
  "está tudo correto?" e só seguir depois de um sim claro. Um comentário ou um "commitei" não é
  aprovação.
- **Etapas grandes demais, dividir em subetapas** (a, b, c...) perguntando ao dev, e registrar a
  divisão no plano e aqui antes de escrever qualquer arquivo da etapa.
- **Questionar a etapa quando não vale o custo**, com recomendação; a decisão é do dev.
- **MVP sempre:** o mínimo de arquivos e classes que resolve a etapa de hoje.
- **Explicações com analogias**, curtas, começando pela analogia e depois ligando ao código. Em
  assunto novo para o dev, conceito antes do código.
- **Código claro para júnior e pleno, com poucos comentários** (só o porquê que o código não
  mostra).
- **CodeGraph primeiro** (`codegraph_explore`) para entender ou localizar código; `Read` só para o
  que o índice não cobre (Markdown, shell, JSON, Twig).
- **Sem comandos compostos:** comandos pequenos, de propósito único, com caminhos absolutos —
  um teste por chamada, sem funções de shell, laços ou `;` encadeando vários casos (o dev
  interrompeu na etapa 4 por isso).
- **Não testar o que o plano não pediu** e **não criar dados reais no banco de dev** (ex.: um
  admin) sem avisar antes: o que exige digitação, o dev testa no terminal dele.
- **PHP roda no container:** `docker compose exec php ...` (ou `just ...`, a partir da etapa 3).
- **Git é do dev.** O Claude nunca roda `git add`, `commit`, `push`, `stash` nem abre PR. Quando
  pedido, só escreve a mensagem de commit: em **inglês**, título + corpo **curto** (poucos tópicos, só o essencial), **sem** `:sparkles:` no
  início, terminando com `Co-Authored-By: <modelo atual> <noreply@anthropic.com>`.
- **Apagar arquivos é com o dev:** o Claude lista `! rm ...` para ele rodar.
- **Inglês em tudo que é do projeto:** código, UI, mensagens de commit e as mensagens/descrições
  de ferramentas (`justfile`, scripts em `www/bin/`). Português só na documentação, nas specs e na
  conversa.
- O dev escreve em português; respostas em português.

## Pendências conhecidas

- **Containers depois de reiniciar a máquina (visto em 2026-10-21 e 2026-10-23):** `php` e
  `nginx` ficam `Exited (127)` sem log, e o `postgres` sobe mas grava fora de `data/postgres`
  (as migrações feitas no dia 21 não estavam lá no dia 23). Hipótese: o Docker sobe os containers
  antes de as pastas do WSL estarem montadas. `just up` resolve na hora; falta investigar a causa.
- **Mount de arquivo único no Docker Desktop/WSL:** depois de editar `config/php/php.ini` ou o
  `Caddyfile`, `docker compose restart` falha; use `docker compose up -d --force-recreate <serviço>`.

- Os problemas encontrados na análise da v1 estão listados (1–19) no início do plano e são
  tratados nas etapas 7, 8 e 10.
