# Migrações do banco

## O que é

Uma **migração** é um arquivo SQL numerado que descreve **uma mudança** no schema do banco: criar
tabela, adicionar coluna, inserir dados fixos. O banco guarda quais migrações já rodaram, e um
programa (o *runner*) aplica só as que faltam, em ordem.

O problema que isso resolve: antes, o schema vivia num `docs/database.sql` que cada pessoa
carregava à mão uma vez. Quando o schema mudasse (as permissões da etapa 14, por exemplo), não
haveria como saber o que cada banco já tinha, e a saída seria apagar e recarregar, perdendo os
dados. Com migrações, cada mudança vira um arquivo novo, e `just migrate` leva qualquer banco, novo
ou antigo, até a versão atual.

## Analogia

O **livro de obras de um prédio**.

- Cada migração é uma **reforma numerada**: "001 — construir os andares", "002 — instalar
  elevador".
- A tabela `schema_migrations` é o **livro do síndico**, onde cada reforma concluída é anotada.
- O `migrate` é o **mestre de obras**: abre o livro, compara com a lista de reformas e executa só
  as que ainda não foram anotadas, na ordem. Chamar o mestre de obras duas vezes não faz nada na
  segunda vez: todas já estão anotadas.
- Se uma reforma dá errado no meio, a obra é **desfeita por inteiro** (transação) e nada é
  anotado. Na próxima vez, ela é tentada de novo do zero.
- O `--fresh` é **demolir o prédio** e reconstruir a partir da reforma 001.

## Como funciona aqui

### Os arquivos

```
www/
├── database/migrations/
│   └── 001_create_roles_and_users.sql   ← tabelas roles e users + papéis ADMIN(1) e USER(2)
└── bin/
    ├── bootstrap.php     ← autoload, .env, connectTo() e fail()
    ├── migrate.php       ← o runner
    └── admin-create.php  ← cria um usuário admin
```

O nome do arquivo sem `.sql` é a **versão** (`001_create_roles_and_users`), e é isso que vai para o
livro. O prefixo numérico define a ordem: o `glob()` devolve os arquivos em ordem alfabética, e
`001` < `002` < `010`. Por isso o número tem três dígitos.

### O que o `migrate.php` faz, passo a passo

```
php bin/migrate.php [--fresh] [banco]
```

1. **Decide o banco.** Usa o argumento, se houver (`wecker_test`); senão, o `DB_NAME` do
   `www/.env` (`wecker`).
2. **Valida o nome do banco** com a regex `^[a-z_][a-z0-9_]*$`. Nome de banco não pode ir como
   parâmetro `?` no SQL (parâmetros servem para valores, não para nomes), então ele é colado no
   comando. A regex garante que só letras minúsculas, números e `_` cheguem lá.
3. **Conecta no banco `postgres`.** Todo servidor Postgres tem esse banco de manutenção. Ele é
   necessário porque não dá para criar ou apagar um banco estando conectado nele mesmo.
4. **Se `--fresh`:** `DROP DATABASE IF EXISTS ... WITH (FORCE)`. O `FORCE` derruba conexões abertas
   (o php-fpm pode estar conectado), que de outro modo bloqueariam o `DROP`.
5. **Se o banco não existe, cria.** Foi assim que o `wecker_test` nasceu no primeiro
   `just migrate`.
6. **Conecta no banco-alvo e cria o livro**, se ainda não existir:

   ```sql
   CREATE TABLE IF NOT EXISTS schema_migrations (
       version VARCHAR(255) PRIMARY KEY,
       applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
   )
   ```

7. **Lê o livro** (`SELECT version FROM schema_migrations`) e percorre os `.sql` da pasta. Para
   cada um que **não** está no livro:
   - `BEGIN`;
   - executa o arquivo inteiro;
   - anota a versão no livro;
   - `COMMIT`.

   Se qualquer coisa falhar: `ROLLBACK`, mensagem `✖ Migration X failed: ...` e saída com erro. As
   migrações seguintes não rodam.
8. **Resumo:** `✔ wecker: 1 migration(s) applied` ou `✔ wecker is up to date`.

Um detalhe que faz o passo 7 funcionar: no Postgres, até `CREATE TABLE` respeita transação. Se a
`001` criasse `roles` e falhasse em `users`, o `ROLLBACK` apagaria `roles` também, e o banco
ficaria como estava antes. Nem todo banco faz isso: no MySQL, `CREATE TABLE` confirma a transação
sozinho.

### Dois bancos: `wecker` e `wecker_test`

`just migrate` roda o runner duas vezes, uma para cada banco:

```just
migrate: (_running "php") _wait-db
    docker compose exec php php bin/migrate.php
    docker compose exec php php bin/migrate.php {{ test_db }}
```

O `wecker_test` é o banco dos testes de integração (etapa 7). Migrar os dois juntos garante que os
testes rodem sempre contra o schema atual. `just db-reset` faz o mesmo com `--fresh`, depois de
pedir confirmação.

### Criar o admin: `admin-create.php`

O schema não traz mais um usuário pronto. O `docs/database.sql` antigo vinha com
`admin@admin.com` e uma senha conhecida (item 6 da análise do plano). Agora o admin é criado por
você:

```
php bin/admin-create.php [--if-missing]
```

1. **Com `--if-missing`:** conta os usuários com `role_id = Role::ROLE_ADMIN`. Se já houver algum,
   imprime `✔ An admin already exists, skipping` e sai. É o modo que o `just install` usa, para
   não pedir admin toda vez que você reinstala.
2. Pergunta **nome**, **e-mail** e **senha**. Na senha, `stty -echo` desliga o eco do terminal
   (o que você digita não aparece) e `stty echo` religa em seguida.
3. Passa o e-mail para minúsculas. O login faz o mesmo (`GeneralNormalizer`); sem isso, um admin
   cadastrado como `Admin@X.com` nunca conseguiria entrar.
4. Grava com `password_hash()` (bcrypt, o mesmo do `UserModel`) e `role_id = Role::ROLE_ADMIN`.

O script insere direto pela conexão, sem passar pelo `UserRepo`, porque o `UserRepo::save()`
grava `ROLE_USER` sempre, ignorando o papel do usuário. Isso será tratado nas etapas de usuários
(14–15).

### Por que o `just` espera o Postgres (`_wait-db`)

No **primeiro** `up`, com a pasta `data/postgres` vazia, o container do Postgres:

1. sobe um servidor temporário, que só escuta no socket local;
2. cria o banco e o usuário do `.env`;
3. desliga esse servidor e sobe o definitivo, que escuta na rede.

Isso leva alguns segundos, e um `migrate` logo depois do `up` falharia. O `_wait-db` testa
`pg_isready -h 127.0.0.1` uma vez por segundo, por até 30 segundos. O `-h 127.0.0.1` força o teste
pela rede: o servidor temporário do passo 1 responderia "pronto" pelo socket e enganaria a
checagem.

## Pegadinhas

- **Nunca edite uma migração que já rodou.** O livro só guarda o nome: o runner não percebe que o
  conteúdo mudou, e o seu banco fica diferente do de quem rodar do zero. Para mudar algo, crie uma
  migração nova (`002_...`). Exceção: uma migração que ainda não saiu da sua máquina, e mesmo
  assim depois de um `just db-reset`.
- **Não há "desfazer" (down migration).** Para voltar atrás, crie uma migração nova que desfaça a
  anterior. Em desenvolvimento, `just db-reset` resolve.
- **O número define a ordem, não a data.** Duas branches criando `002_...` ao mesmo tempo vão
  conflitar: renumere a sua antes do merge.
- **`db-reset` apaga os usuários**, inclusive o admin. Depois dele, rode `just admin-create`.
- **`admin-create` não valida nada**, por decisão do dev (MVP). Enter em branco grava um admin com
  nome, e-mail e senha vazios. A única barreira é o `UNIQUE` do e-mail no banco, que recusa um
  e-mail repetido com um erro do Postgres.
- **A senha fica oculta só num terminal de verdade.** Por pipe (`printf ... | php
  bin/admin-create.php`), o `stty` falha em silêncio, sem quebrar o script.
- **`$_SERVER['argv']` em vez de `$argv`:** são o mesmo array, mas o PHPStan não sabe que `$argv`
  sempre existe na linha de comando e acusaria erro.

## Comandos úteis

```bash
just migrate            # aplica o que falta nos dois bancos
just --yes db-reset     # recria os dois bancos do zero, sem perguntar
just admin-create       # cria um admin (interativo)

# o que está no livro
docker compose exec postgres psql -U root -d wecker -c 'SELECT * FROM schema_migrations'

# tabelas do banco
docker compose exec postgres psql -U root -d wecker -c '\dt'
```
