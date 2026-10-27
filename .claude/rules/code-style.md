# Estilo e convenções de PHP

Alvo: **PHP 8.5**. Siga o arquivo ao redor: o código é consistente internamente.

## Clareza acima de esperteza

O código precisa ser fácil de ler para um dev júnior ou pleno. Use um recurso da linguagem só quando ele deixa o código *mais fácil de ler* que a alternativa, não porque é novo ou mais curto. Antes de usar, pergunte: alguém novo no projeto entende na primeira leitura? Remove código ou risco, ou só rearruma? Combina com o resto do código?

- Prefira uma variável nomeada por passo a uma cadeia longa de `|>` ou chamadas aninhadas.
- Prefira um `if` simples a ternários aninhados ou `?:`/`??` encadeados.
- Prefira um método explícito a uma closure guardada em constante ou a um property hook que só encurta um getter claro.
- Poucos comentários: só o *porquê* que o código não mostra.

## Namespaces e organização

- PSR-4: `App\` → `www/src/`, `App\Bootstrap\` → `www/bootstrap/`, `Tests\` → `www/tests/` (ver `www/composer.json`).
- Uma classe por arquivo, com o mesmo nome. **Exceção:** `App\Service\AuthMidleware` mantém a grafia errada; não "corrija" sem renomear em todo lugar.
- Sub-namespaces por domínio: `Service\User\Validator`, `Service\User\Normalizer`, `Service\Api`, `Repository\User`, `Model\User`.
- Lógica fica em classes de `src/`; os scripts de `www/bin/` só leem argumentos, chamam uma classe e imprimem pelo `App\Console\Terminal`.

## Nomes

- Sufixo pelo papel: `*Controller`, `*Service`, `*Repo`, `*Model`, `*Validator`, `*Normalizer`, `*Exception`, `*Factory`. Entidades são substantivos simples (`User`, `Role`).
- Classes abstratas começam com `Abstract*` (`AbstractController`, `AbstractModel`, `AbstractRepo`).
- Métodos e propriedades em `camelCase`; constantes de classe em `UPPER_SNAKE_CASE`.
- Métodos com nome de ação (`extractContainerId()`, não `containerId()`).

## Tipos e recursos do PHP 8.5 em uso

- Propriedades e parâmetros de construtor sempre tipados; nulável com `?Type`.
- **Constantes tipadas**: `const string RUNNING_STATE = ...`.
- `new Main()->run()`: método direto no `new`, sem parênteses extras.
- `match ($x) { 204 => '...', default => '...' }` para mapear valores.
- Argumento padrão com `new` (`Carbon $createdAt = new Carbon()`).
- `declare(strict_types=1)` está nos arquivos novos e nas entradas, mas **não** em todo o `src/`; coloque nos arquivos novos.

## PHPDoc

Métodos têm docblock completo com `@param`, `@return` e `@throws`, mesmo com a assinatura tipada. Propriedades costumam ter um `/** @var Type $name */` redundante. Mantenha esse estilo.

## Injeção de dependência

Prefira **injeção pelo construtor** com parâmetros tipados; o PHP-DI faz o autowiring. Guarde cada dependência numa propriedade privada tipada.

Nunca faça `new` de um colaborador dentro de uma classe (ex.: `new DockerClient()` num service). Objetos que precisam de preparo em tempo de execução ganham uma factory e uma definição em `config/*.php` (ver `DockerClientFactory` + `config/docker.php`).

## Acesso a dados (repositories)

- Estenda `AbstractRepo` e use a `Connection` do DBAL injetada. **Só SQL parametrizado, sem ORM nem query builder.**
- Sempre placeholders (`?` ou `:nome`); nunca interpole entrada do usuário no SQL.
- Escritas: `beginTransaction()` / `commit()`, e no `catch` → `rollback()`, `error_log($e)` e relançar.
- `hydrate(array $data)` transforma a linha numa entidade; datas viram `Carbon`.

## Validação e normalização

- Normalize primeiro (`GeneralNormalizer::clean()` tira espaços e põe o `email` em minúsculas), depois valide.
- Validators usam `respect/validation` (`use Respect\Validation\ValidatorBuilder as v;`).
- Validators **lançam `ValidationException($errors)`** na falha; o `LoginValidator` é a referência.
- Dado de requisição que vai para uma URL ou caminho é validado antes (ex.: id de container só hex, `ctype_xdigit`).

## Erros e formato de resposta

- Lance as exceções tipadas de `App\Exception\*` (com um **array de mensagens**); o `HttpErrorHandler` as transforma em JSON `{success: false, error: [...]}`.
- Services que chamam algo externo devolvem `['status' => int, 'message' => mixed]`; o controller repassa com `returnWithJson($response, [$msg], $status)`.
- Controllers devolvem JSON (`returnWithJson()` / `returnWithSuccess()`) ou renderizam Twig.

## Frontend

- Templates Twig estendem `layout/base.html.twig`; o bloco `scripts` chama `{{ parent() }}` primeiro.
- Cada página tem um módulo JS em `public/assets/js/pages/`, no padrão IIFE que devolve `{ init }`.
- Todo texto vindo do Docker inserido como HTML passa por `escapeHtml()`.
- Não edite as bibliotecas em `public/assets/js/{jquery,boostrap,datatables,sweetalert2}/` (a pasta é `boostrap` mesmo).

## Idioma

Código, identificadores e textos de interface em **inglês**. Documentação e conversa em **português**.
