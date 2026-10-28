# Testes (PHPUnit)

## O que é

Testes de integração (`tests/Integration/`): o app de verdade, com banco real (`wecker_test`) e
**Docker real**, exercitado como o navegador faria.

## Analogia

Um **test-drive**: carro de verdade, estrada de verdade. Só o motorista é um robô que repete o
mesmo percurso (logar, parar e iniciar um container) e confere o painel.

## Como funciona aqui

1. **`tests/bootstrap.php`** roda uma vez: troca `DB_NAME` para `wecker_test`, recria esse banco a
   partir das migrações e baixa a imagem `busybox`.
2. **`IntegrationTestCase`**, antes de cada teste: sessão, container de DI e app novos, transação
   aberta. Depois: remove os containers criados e faz `rollBack()`.

| Ajudante | Para quê |
|---|---|
| `request($method, $path, $body)` | Entrega uma requisição ao app e devolve a resposta. |
| `login()` | Cria um usuário e faz `POST /login`, como o navegador. |
| `createRunningContainer()` | Sobe um `busybox` que imprime `hello` e fica rodando; devolve o id. |

```php
$this->login();
$id = $this->createRunningContainer();
$this->request('POST', '/dashboard/stop', ['id' => $id]);
$this->assertSame('exited', $this->statusInList($id));
```

## Pegadinhas

- **Precisa do Docker no ar.** Os testes rodam no container `php`, que fala com o daemon pelo
  socket.
- **Erro esperado? Chame `$this->expectErrorLog()`.** O Slim registra todo 4xx/5xx com
  `error_log()`, e o PHPUnit imprime esse log se o teste não o declarar.
- **Cada execução apaga o `wecker_test`.** Não guarde nada nele.

## Comandos úteis

```bash
just test                               # tudo
just test testStopAndStartARealContainer # um teste (--filter)
```
