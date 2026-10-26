# Testes (PHPUnit)

## O que é

Duas suítes (`phpunit.xml`): **Unit** (`tests/Unit/`), uma classe sozinha, sem banco nem HTTP; e
**Integration** (`tests/Integration/`), o app de verdade com banco real (`wecker_test`) e Docker
falso.

## Analogia

Um **simulador de voo**: o avião é real (app, rotas, Postgres), o mundo lá fora é simulado (o
Docker responde o que o teste programou), e cada voo começa do mesmo ponto (rollback no fim).

## Como funciona aqui

1. **`tests/bootstrap.php`** roda uma vez: troca `DB_NAME` para `wecker_test` e recria esse banco
   a partir das migrações.
2. **`IntegrationTestCase`**, antes de cada teste: sessão, container e app novos, transação
   aberta e Docker falso sem respostas. Depois: `rollBack()`.
3. Os testes de rota ficam em `UserRoutesTest` e `DashboardRoutesTest` e registram o
   comportamento **atual**, inclusive bugs (ex.: rota inexistente → 500), que mudam na etapa 8.

| Ajudante | Para quê |
|---|---|
| `request($method, $path, $body)` | Entrega uma requisição ao app e devolve a resposta. |
| `fakeDocker([...respostas])` | O Docker responde essas respostas, na ordem. |
| `actingAs($userId)` | Põe o usuário na sessão. |
| `$this->connection` | Conexão com o `wecker_test`, para preparar ou conferir dados. |

```php
$this->actingAs(1);
$this->fakeDocker([new Response(200, [], '[...]')]);
$response = $this->request('POST', '/dashboard/list');
$this->assertSame(200, $response->getStatusCode());
```

## Pegadinhas

- **Chame `fakeDocker()` antes da requisição**, com uma resposta para cada chamada ao Docker; se
  faltar, o Guzzle lança `OutOfBoundsException`.
- **Cada execução apaga o `wecker_test`.** Não guarde nada nele.
- **Erro esperado? Chame `$this->expectErrorLog()`.** O Slim registra todo 4xx/5xx com
  `error_log()`, e o PHPUnit imprime esse log no meio da saída se o teste não o declarar.
- **A sessão é real (Redis), mas sem cookie** (`phpunit.xml`): aberta no `setUp`, destruída no
  `tearDown`.

## Comandos úteis

```bash
just test               # tudo
just test SmokeTest     # uma classe ou um teste (--filter)
```
