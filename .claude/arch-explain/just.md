# just (o `justfile`)

## O que é

[just](https://just.systems) é um executor de comandos: lê o arquivo `justfile` na raiz do
projeto e roda "receitas" pelo nome (`just up`, `just test`). Ele não compila nada nem guarda
estado: é só um jeito organizado de dar nome a comandos que você digitaria no terminal.

Substituiu o `run.sh` na etapa 3 do plano v2. O motivo: no bash, metade do arquivo era
infraestrutura (o `case`, o tratamento de argumentos, a tabela de ajuda). No `just`, isso vem
pronto, e cada comando vira uma receita de duas ou três linhas.

## Analogia

O `justfile` é o **cardápio de um restaurante**.

- Cada **receita** é um prato: tem nome, uma descrição de uma linha (o comentário acima dela) e o
  modo de preparo (os comandos).
- `just` sem nada é pedir o cardápio: ele lista os pratos agrupados por seção.
- As **dependências** são o pré-preparo: antes de servir o prato, a cozinha confere se tem gás e
  ingrediente. Se faltar algo, o prato nem começa.
- As receitas com `_` na frente são a **cozinha**: o cliente não vê no cardápio, mas elas rodam
  por trás dos pratos.

## Como funciona aqui

### Anatomia de uma receita

```just
# Run the tests (optional filter by test name)     ← descrição que aparece no `just --list`
[group('php')]                                      ← atributo: em qual seção do cardápio
test filter="": (_running "php")                    ← nome, parâmetro e dependência
    docker compose exec php composer test ...       ← corpo (indentado)
```

Lendo a terceira linha, da esquerda para a direita:

| Pedaço | Significado |
|---|---|
| `test` | Nome. É o que você digita: `just test`. Só letras, números, `-` e `_` (por isso `cs-fix`, e não `cs:fix`). |
| `filter=""` | Parâmetro com valor padrão vazio. `just test` → `filter` vazio; `just test fooBar` → `filter = "fooBar"`. |
| `:` | Separa o nome das dependências. |
| `(_running "php")` | Dependência que recebe argumento. Os parênteses são obrigatórios quando a dependência tem argumento. |

### Dependências

```just
reset: _docker _env
```

"Antes do corpo de `reset`, rode `_docker` e depois `_env`." Se qualquer uma falhar (sair com
código diferente de 0), o `just` para ali e o corpo não roda.

Dependências podem ser receitas públicas também. O `install` é feito só de dependências:

```just
install: setup up (composer "install --no-interaction") migrate _admin-if-missing
    @echo "✔ Install complete: http://localhost"
```

Elas rodam **na ordem escrita**, e o `echo` só roda no fim, se todas deram certo.

Se duas receitas da mesma execução dependem da mesma coisa com os mesmos argumentos, o `just` roda
essa coisa **uma vez só**. No `install`, `_docker` aparece na árvore de quase todas as receitas,
mas é conferida uma única vez.

### As receitas privadas (a cozinha)

| Receita | O que confere | Por quê |
|---|---|---|
| `_docker` | binário `docker`, `docker compose` v2, daemon respondendo | Sem isso, qualquer comando falha com erro confuso. |
| `_env` | existe `.env` na raiz | Sem ele o Compose roda com `CONTAINER_NAME` e portas vazios. |
| `_running service` | o container `service` está rodando | `docker compose exec` num container parado dá erro pouco claro. |
| `_wait-db` | o Postgres aceita conexões (tenta por 30 s) | No primeiro `up`, o Postgres leva alguns segundos para inicializar; o `migrate` logo depois falharia. |
| `_admin-if-missing` | — (roda `admin-create.php --if-missing`) | Usada só pelo `install`, para não pedir admin de novo se já existe. |

Qualquer nome que começa com `_` fica fora do `just --list`. O `default` usa o atributo
`[private]`, que tem o mesmo efeito: ele precisa se chamar `default`, sem `_`, porque é a primeira
receita do arquivo, a que roda quando você digita só `just`.

### Variáveis e interpolação

```just
test_db := "wecker_test"
...
    docker compose exec php php bin/migrate.php {{ test_db }}
```

- `:=` define uma variável do `just` (não do shell).
- `{{ ... }}` insere o valor no comando **antes** de o shell ver a linha. O shell recebe
  `... bin/migrate.php wecker_test`.
- Dentro de `{{ }}` dá para usar expressões. A receita `test` usa um `if`:

  ```just
  {{ if filter == "" { "" } else { "-- --filter " + quote(filter) } }}
  ```

  Se não houver filtro, não insere nada. Se houver, insere `-- --filter 'fooBar'`. O `quote()`
  põe aspas simples para o shell, para que um filtro com espaço não quebre o comando. O `--` é do
  Composer: diz "o que vem depois vai para o PHPUnit, não para mim".

### Parâmetro variádico

```just
composer *args: (_running "php")
    docker compose exec php composer {{ args }}
```

O `*` aceita zero ou mais argumentos e junta todos com espaço:
`just composer require foo/bar --dev` → `composer require foo/bar --dev`.

### Dois jeitos de escrever o corpo

**Linha a linha** (o padrão), como em `reset`:

```just
reset: _docker _env
    docker compose down -v --remove-orphans
    docker container prune -f
```

Cada linha roda **num shell separado**, e o `just` imprime a linha antes de rodá-la. Se uma linha
falha, as seguintes não rodam.

**Com shebang**, como em `setup`, `nuke` e `_wait-db`:

```just
setup:
    #!/usr/bin/env bash
    set -euo pipefail
    [ -f .env ] || { cp env-example .env; echo "✔ .env created"; }
```

A primeira linha `#!` faz o `just` salvar o corpo inteiro num arquivo temporário e executá-lo como
**um único script** bash. Use quando precisar de variável entre linhas (o `answer` do `nuke`), de
laço (o `for` do `_wait-db`) ou de `if`. O `set -euo pipefail` faz o script parar no primeiro erro,
como o modo linha a linha já faz sozinho.

### O `@` no começo da linha

Por padrão o `just` imprime cada comando antes de executá-lo (é o `docker compose ps` que aparece
antes da tabela do `just status`). O `@` desliga essa impressão naquela linha. Usamos em `echo` e
nas checagens, onde mostrar o comando seria só ruído.

### Atributos (as linhas entre colchetes)

| Atributo | Efeito |
|---|---|
| `[group('php')]` | Agrupa a receita nessa seção do `just --list`. |
| `[private]` | Esconde a receita da lista (igual ao `_` no nome). |
| `[confirm("...")]` | Mostra a pergunta e só roda se a resposta for `y`. Usado no `db-reset`. |

O `nuke` **não** usa `[confirm]`, de propósito: o `[confirm]` aceita só um `y`, e o `nuke` apaga
o Docker inteiro da máquina. Ele pede para digitar a palavra `nuke`, o que evita confirmar no
automático.

### Onde os comandos rodam

O `just` sempre roda as receitas **a partir da pasta do `justfile`**, mesmo se você chamar de uma
subpasta. Por isso `cp env-example .env` funciona sem caminho absoluto.

Os comandos PHP rodam **dentro do container** `php`, onde a pasta de trabalho é
`/var/www/html` (o `www/` montado). Por isso o caminho é `bin/migrate.php`, e não
`www/bin/migrate.php`.

## Pegadinhas

- **Cada linha é um shell novo.** Um `cd pasta` numa linha não vale para a linha de baixo. Se
  precisar de estado entre linhas, use o modo shebang.
- **`{{ }}` é do `just`, `$VAR` é do shell.** `{{ service }}` é trocado antes de rodar;
  `"$answer"` no `nuke` é variável do bash, resolvida durante a execução.
- **Sem `:` no nome da receita.** `cs:fix` só existe como script do Composer; a receita é `cs-fix`.
- **`-T` no `docker compose exec`.** Sem terminal (num pipe, num CI), o `exec` precisa de `-T`
  para não tentar alocar um TTY. O `_wait-db` usa `-T` porque roda num laço, sem interação.
- **`--yes` pula o `[confirm]`.** `just --yes db-reset` não pergunta nada. Útil em script, perigoso
  no dedo.
- **Código de saída repassado.** Se o PHPUnit falha, o `just test` sai com erro também, e um
  `just a && just b` para no `a`. A mensagem `error: Recipe 'x' failed on line N` é do `just`,
  avisando qual linha falhou, e não um erro a mais.

## Comandos úteis

```bash
just                     # cardápio agrupado
just --show test         # mostra o código de uma receita
just --dry-run migrate   # mostra o que rodaria, sem rodar
just --yes db-reset      # pula a confirmação
just --evaluate test_db  # valor de uma variável
```
