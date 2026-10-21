# Passagem de sessão

Resumo para continuar o desenvolvimento em outro computador (ou voltar a este). Quem terminar uma
sessão atualiza este arquivo; quem começar lê este arquivo primeiro.

Última atualização: 2026-10-20.

## Onde paramos

- **Plano em execução:** `.claude/plans/v2.md` (19 etapas, branch `feature/v2`), executado com a
  skill `modo-codando` (`.claude/skills/modo-codando/SKILL.md`).
- **Etapa 1 (skill `modo-codando` + este arquivo):** entregue em 2026-10-20, aguardando
  conferência do dev.
- **Próxima:** etapa 2 — ferramentas de qualidade (`phpunit.xml`, PHPStan, PHP-CS-Fixer, scripts
  `composer test | stan | cs | cs:fix`). Até ela, não há como rodar `test`/`stan`/`cs`: relatar
  como "não verificado".

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
- **PHP roda no container:** `docker compose exec php ...` (ou `./run.sh ...` depois da etapa 3).
- **Git é do dev.** O Claude nunca roda `git add`, `commit`, `push`, `stash` nem abre PR. Quando
  pedido, só escreve a mensagem de commit: em **inglês**, título + corpo, **sem** `:sparkles:` no
  início, terminando com `Co-Authored-By: <modelo atual> <noreply@anthropic.com>`.
- **Apagar arquivos é com o dev:** o Claude lista `! rm ...` para ele rodar.
- O dev escreve em português; respostas em português.

## Pendências conhecidas

- Os problemas encontrados na análise da v1 estão listados (1–19) no início do plano e são
  tratados nas etapas 7, 8 e 10.
