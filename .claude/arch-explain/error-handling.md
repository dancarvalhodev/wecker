# Tratamento de erros

## O que é

Quando algo dá errado no meio de uma requisição (um campo inválido, o Docker fora do ar, um bug), o
código **lança uma exceção**. A exceção sobe pelas chamadas, de método em método, até alguém a
capturar. No Wecker, quem captura tudo é o middleware de erro do Slim, que entrega a exceção ao
`App\Handler\HttpErrorHandler`. Ele transforma qualquer exceção numa resposta JSON padrão.

## Analogia

O **balcão de reclamações** de uma loja.

- Qualquer funcionário que encontra um problema preenche um **formulário** (a exceção) e o manda
  para cima, sem precisar resolver ali mesmo.
- Há **formulários tipados**: "dado inválido" (`ValidationException`, com a lista do que está
  errado) e "problema desconhecido" (qualquer outra exceção).
- O balcão (o `HttpErrorHandler`) recebe todos e responde ao cliente sempre com a **mesma carta
  padrão**: `{success: false, error: ...}`. Se o formulário é tipado, a carta diz o que estava
  errado; se não, diz só "Internal Server Error", sem expor detalhes internos.
- Uma **cópia de cada formulário** vai para o arquivo morto (o log do container).

## Como funciona aqui

### A ligação (`AppFactory::create()`)

```php
$errorMiddleware = $app->addErrorMiddleware(
    !Application::isProduction(), // displayErrorDetails
    true, // logErrors
    true, // logErrorDetails
);
$errorMiddleware->setDefaultErrorHandler($container->get(HttpErrorHandler::class));
```

O middleware de erro envolve todas as rotas: qualquer exceção não capturada lá dentro termina nele.

### As exceções do projeto (`src/Exception/`)

| Classe | Herda de | Código | Para quê |
|---|---|---|---|
| `ValidationException` | `Exception` | 422 | Dados inválidos. Recebe um **array de mensagens** (`getMessages()`). |
| `UserException` | `ValidationException` | 422 | Erros de usuário (ex.: `['Wrong password.']`). |

Por herdar de `ValidationException`, a `UserException` é tratada do mesmo jeito pelo handler.

### O que o handler responde (`HttpErrorHandler::respond()`)

| Exceção | Status | `error` |
|---|---|---|
| `ValidationException` e filhas | o código da exceção (422) | o array de mensagens: `["Wrong password."]` |
| `Slim\Exception\HttpException` (404, 405) | o código da exceção | `[mensagem]`, ex.: `["Not found."]` |
| qualquer outra | 500 | `"Internal Server Error"` (texto, não array) |

```json
{"success": false, "error": ["Wrong password."]}
```

Uma exceção comum (um erro do Postgres, um `TypeError`) nunca expõe a mensagem real ao navegador:
o cliente vê só o 500 genérico, e o detalhe fica no log.

### Onde ver o detalhe

Com `logErrors` e `logErrorDetails` ligados, o Slim grava a mensagem e o stack trace com
`error_log()`. O php-fpm manda isso para a saída do container: `docker compose logs php`.

## Pegadinhas

- **`HttpException` é a do Slim.** Um `use HttpException;` sem namespace apontaria para uma classe
  global da extensão PECL `http`, e os 404/405 virariam 500.
- **`error` muda de forma.** Pode ser uma lista (`["Not found."]`), um objeto por campo
  (`{"email": "..."}`, do `LoginValidator`) ou um texto (no 500). O `login.js` trata os três.
- **Toda validação gera um stack trace no log.** Um login com senha errada é uma
  `UserException`, e o Slim registra o trace completo. É ruído, não um bug.
- **`displayErrorDetails` não tem efeito.** O `respond()` customizado nunca inclui os detalhes,
  nem em desenvolvimento. Para depurar, use o log.
- **Lance com array.** `new ValidationException('mensagem')` dá `TypeError`: o construtor exige
  `array $messages`.

## Comandos úteis

```bash
docker compose logs -f php                               # erros e stack traces ao vivo
curl -s -d 'email=x@y.z&password=a' http://localhost/login   # um 422 de exemplo
curl -s -i http://localhost/nao-existe                   # um 404
```
