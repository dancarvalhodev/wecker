# Passagem de sessão

Resumo para continuar o desenvolvimento em outro computador (ou voltar a este). Quem terminar uma
sessão atualiza este arquivo; quem começar lê este arquivo primeiro.

Última atualização: 2026-10-26.

## Onde paramos

- **Plano:** `.claude/plans/v2.md` (branch `feature/v2`), executado com a skill `modo-codando`.
  Revisado em 2026-10-26 (MVP): restam as etapas **8 Segurança essencial**, **9 Bugs visíveis**,
  **10 Revamp visual**, **11 Criar container pela UI** e **12 Usuários e papéis fixos**.
- **Etapa 8:** confirmada em 2026-10-27. Testes passaram a usar o Docker real.
- **Etapa 9:** confirmada em 2026-10-27.
- **Etapa 10 (revamp visual):** entregue em 2026-10-27, aguardando conferência. Direção escolhida:
  console (fonte JetBrains Mono), cantos retos, fundo preto-arroxeado e acento lavanda `#a78bfa`.
  Tokens e classes `wk-*` em `www/public/assets/css/style.css`, que sobrescreve o Bootstrap
  (`data-bs-theme="dark"`). `APP_ENV` agora vem do `www/.env`
  (`$_ENV`), não mais do Caddyfile.
- **Banco "sumindo" depois de reiniciar (resolvido em 2026-10-27):** a causa era o
  `restart: always` (o Docker Desktop subia os containers antes de o WSL expor as pastas, e o
  Postgres gravava numa pasta fantasma da VM). Saiu do `docker-compose.yml`; após reiniciar, rode
  `just up`. Postgres e Redis sem porta no host
  (`POSTGRES_PORT` saiu do `env-example`).
- **Feito (1–7):** skill `modo-codando`; PHPUnit/PHPStan/PHP-CS-Fixer; `justfile`; migrações
  (`Migrator`, `just migrate`, `just admin-create`); PHP em Ubuntu 24.04 + Caddy; `arch-explain/`;
  injeção do Docker (`DockerClientFactory`, `config/docker.php`, `AppFactory`) e 8 testes de rota
  com `IntegrationTestCase` (`fakeDocker()`, `actingAs()`, `wecker_test` recriado a cada execução).
- **Decisões que mudaram a entrevista:** papéis fixos (Admin/User) em vez de permissões granulares;
  CSRF via cookie `SameSite=Strict`; `/register` sai já na etapa 8; sem etapa própria de
  `cs-fix`/PHPStan (cada etapa limpa os arquivos que toca).

## Decisões da entrevista (2026-10-19)

O plano tem o detalhe; aqui só o resumo.

- **Permissões:** ~~granulares, editáveis pela UI~~ → papéis fixos Admin/User (revisão de
  2026-10-26).
- **Cadastro:** só o admin cria usuários; o `/register` público sai.
- **Criar container (essencial):** imagem (pull se faltar), nome, portas, env vars, restart policy,
  "iniciar após criar".
- **Testes:** ~~Docker mockado~~ → Docker real (revisão de 2026-10-26) + Postgres real no banco `wecker_test`,
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
- **Documentação enxuta** (pedido do dev em 2026-10-26): manter as seções, mas curtas; só o que o
  leitor precisa, sem passo a passo linha a linha.
- **Relatório de fim de etapa curto** (2026-10-26): poucas linhas por seção, só o essencial.
- **`arch-explain/` só é atualizado quando a mudança invalida o que está escrito** (2026-10-26).
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

- **Mount de arquivo único no Docker Desktop/WSL:** depois de editar `config/php/php.ini` ou o
  `Caddyfile`, `docker compose restart` falha; use `docker compose up -d --force-recreate <serviço>`.

- Os problemas encontrados na análise da v1 estão listados (1–19) no início do plano e são
  tratados nas etapas 8 e 9.
