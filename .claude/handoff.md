# Passagem de sessão

Resumo para continuar o desenvolvimento em outro computador (ou voltar a este). Quem terminar uma
sessão atualiza este arquivo; quem começar lê este arquivo primeiro.

Última atualização: 2026-10-20.

## Onde paramos

- **Plano em execução:** `.claude/plans/v2.md` (19 etapas, branch `feature/v2`), executado com a
  skill `modo-codando` (`.claude/skills/modo-codando/SKILL.md`).
- **Etapa 1 (skill `modo-codando` + este arquivo):** confirmada em 2026-10-20.
- **Etapa 2 (ferramentas de qualidade):** confirmada em 2026-10-20.
  `docker compose exec php composer test | stan | cs | cs:fix` funcionam. Estado de partida:
  testes verdes (1 placeholder), `stan` com 39 erros (nível 6) e `cs` com 27 de 29 arquivos a
  corrigir. Os dois devem ser zerados nas etapas 7–10. Guzzle fixado em `^8.3` (estável), por
  decisão do dev. O `www/composer.lock` passou a ser versionado (saiu do `.gitignore`).
- **Etapa 3 (`run.sh` aprimorado → `justfile`):** entregue em 2026-10-20, aguardando
  conferência. Por decisão do dev, o `run.sh` foi trocado por um `justfile` ([just](https://just.systems),
  `sudo apt install just`). O `just` não aceita `:` em nomes: os comandos são `cs-fix`,
  `db-reset` e `admin-create`. `migrate`, `db-reset`, `admin-create` e a parte do `install` que
  migra/cria admin foram movidos para a etapa 4.

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
- **Sem comandos compostos:** comandos pequenos, de propósito único, com caminhos absolutos.
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

- Os problemas encontrados na análise da v1 estão listados (1–19) no início do plano e são
  tratados nas etapas 7, 8 e 10.
