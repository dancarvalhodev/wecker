# Passagem de sessão

Resumo para continuar o desenvolvimento em outro computador (ou voltar a este). Quem terminar uma
sessão atualiza este arquivo; quem começar lê este arquivo primeiro.

Última atualização: 2026-10-27.

## Onde paramos

- **A v2 está concluída** na branch `feature/v2`. O que vem depois está no roadmap do `README.md`;
  o primeiro item é o controle de usuários.
- **Estado do app:** login por sessão, sem cadastro público (usuários só pelo `just admin-create`,
  todos com acesso total). O dashboard lista, inicia, para, mostra logs e cria containers (pull se
  a imagem faltar). Visual: console escuro arroxeado, cantos retos, JetBrains Mono, tokens em
  `www/public/assets/css/style.css`.
- **Testes:** contra o **Docker real** (containers `busybox` descartáveis) e o banco `wecker_test`,
  recriado a cada `just test`. Detalhes em `.claude/arch-explain/testing.md`.

## Decisões que valem

- **CSRF** via cookie de sessão `SameSite=Strict`, sem token.
- **`APP_ENV`** vem do `www/.env` (`$_ENV`), não do Caddyfile.
- **Sem `restart: always`** no `docker-compose.yml`: depois de reiniciar a máquina, rode `just up`.
- **Postgres e Redis** sem porta publicada no host.
- **Cada mudança deixa limpos** (PHPStan e CS) os arquivos que tocou; o legado fica como está.
- **Idioma:** código, UI, commits e textos de ferramentas em inglês; documentação e conversa em
  português.

## Como o dev quer trabalhar

- **Uma etapa por vez:** entregar, dizer como conferir, perguntar "está tudo correto?" e só seguir
  depois de um sim claro. Um comentário ou um "commitei" não é aprovação. Para um plano com etapas,
  usar a skill `modo-codando`.
- **MVP sempre:** o mínimo de arquivos e classes. Questionar o que não vale o custo, com
  recomendação; a decisão é do dev.
- **Clareza acima de esperteza** (`.claude/rules/code-style.md`): código para júnior e pleno, poucos
  comentários (só o porquê; regex sempre com um comentário breve).
- **Explicações com analogias**, curtas. **Documentação e relatórios enxutos.** `arch-explain/` só é
  atualizado quando a mudança invalida o que está escrito.
- **Testes que provam o que importa** (o comportamento real), nada de teste que só confirma o óbvio.
- **CodeGraph primeiro** para entender código. **Editar com o Edit, não com `sed`.**
- **Comandos simples:** um por chamada, sem laços nem `;`/`||` encadeando casos.
- **Não criar dados reais no banco de dev** sem avisar; o que exige digitação, o dev testa.
- **Git é do dev:** o Claude nunca roda `git add/commit/push/stash` nem abre PR. Quando pedido, só
  escreve a mensagem de commit: em inglês, título + poucos tópicos, sem `:sparkles:`, terminando
  com `Co-Authored-By: <modelo atual> <noreply@anthropic.com>`. Antes, confere o `git status` e
  avisa se o staging estiver incompleto.
- **Apagar arquivos é com o dev:** o Claude lista o `! rm ...`.
- **Não ler nem editar o `.env` da raiz** sem permissão explícita.

## Pegadinhas do ambiente

- **Mount de arquivo único no Docker Desktop/WSL:** depois de editar `config/php/php.ini` ou o
  `Caddyfile`, `docker compose restart` falha; use `docker compose up -d --force-recreate <serviço>`.
- **Cache de 1 ano em CSS/JS** (Caddyfile): depois de mudar um asset, Ctrl+Shift+R no navegador.
