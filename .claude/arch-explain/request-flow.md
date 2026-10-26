# O caminho de uma requisição

## O que é

Tudo o que acontece entre o navegador pedir uma URL e receber a resposta. No Wecker, uma
requisição atravessa dois containers (Caddy e PHP) e, dentro do PHP, cinco camadas: entrada,
rotas, controller, service e, no fim, o repositório (banco) ou o `DockerClient` (Docker).

Este arquivo é o mapa geral. Cada parada tem o seu arquivo com o detalhe:
[infra.md](infra.md), [di.md](di.md), [auth.md](auth.md), [docker-client.md](docker-client.md),
[error-handling.md](error-handling.md) e [frontend.md](frontend.md).

## Analogia

Um **restaurante**.

| No restaurante | No Wecker |
|---|---|
| **Recepcionista** na porta: entrega na hora o que está pronto na vitrine (água, cardápio) e leva o resto para dentro | **Caddy**: serve arquivos de `www/public` (CSS, JS, imagens) e repassa o resto para o PHP |
| **Porta da cozinha**: todo pedido entra por ela | **`public/index.php`**: o único arquivo PHP que o Caddy chama |
| **Gerente** que abre a cozinha: liga o gás, confere a equipe | **`Bootstrap\Main`**: carrega o `.env`, monta o container de DI, registra rotas e o tratamento de erros |
| **Cardápio**: cada prato aponta para quem o prepara | **`routes/web.php`**: cada URL aponta para um método de controller |
| **Segurança na porta do salão VIP**: confere a pulseira | **`AuthMidleware`**: confere a sessão nas rotas do dashboard |
| **Garçom**: anota o pedido, confere se faz sentido, leva à cozinha e traz o prato | **Controller**: lê a requisição, valida o básico, chama o service e monta a resposta |
| **Cozinheiro**: sabe fazer o prato | **Service / Model**: a regra de negócio |
| **Despensa** e **fornecedor** | **Repository** (Postgres) e **`DockerClient`** (Docker Engine) |
| **Balcão de reclamações**: qualquer problema vira uma resposta padrão | **`HttpErrorHandler`**: toda exceção vira JSON `{success: false, error: ...}` |

## Como funciona aqui: um clique em "Start"

O exemplo é o botão **Start** de um container parado no dashboard.

### 1. No navegador (`dashboard.js`)

O botão foi desenhado pela DataTable com `data-id="<id do container>"`. O clique cai neste
handler:

```js
$(document).on('click', '.start-container', function (e) {
    let id = $(this).data('id');
    startStopContainer(id, 'start');
});
```

`startStopContainer()` faz um `POST /dashboard/start` com o corpo `id=<id>`. O cookie de sessão
(`PHPSESSID`) vai junto automaticamente.

### 2. No Caddy (`config/caddy/Caddyfile`)

`/dashboard/start` não é um arquivo de `www/public`. Então o `php_fastcgi php:9000` repassa a
requisição ao container `php`, pela porta 9000, mandando executar `index.php` e definindo
`APP_ENV=development`.

### 3. A entrada (`www/public/index.php`)

```php
require __DIR__ . '/../vendor/autoload.php';
session_start();
new Main()->run();
```

- O autoload do Composer permite usar qualquer classe sem `require`.
- `session_start()` lê o cookie `PHPSESSID` e carrega `$_SESSION` do Redis (ver [auth.md](auth.md)).
- `new Main()` monta o app. O construtor lê o `www/.env` (`loadConfigs()`), pede o container de
  DI ao `ContainerFactory` (ver [di.md](di.md)) e o app Slim ao `AppFactory`, que liga o
  tratamento de erros e registra as rotas. Os testes usam as mesmas duas factories.
- `run()` entrega a requisição ao Slim.

### 4. A rota (`www/routes/web.php`)

```php
$app->post('/dashboard/start', [DashboardController::class, 'start'])->add(AuthMidleware::class);
```

O Slim encontra a rota pelo método (`POST`) e pelo caminho. Antes de chamar o controller, passa
pelo `AuthMidleware`: se `$_SESSION['user_id']` não existe, a resposta é um 302 para `/login`, e o
controller nem roda.

### 5. O controller (`DashboardController::start()`)

O container de DI cria o controller (com `Twig` e `DashboardService`) e o Slim chama `start()`
passando `$request` e `$response`.

```php
$data = $request->getParsedBody();          // ['id' => '...']
if (!isset($data['id'])) {
    throw new ValidationException(['ID not found.']);
}
$dockerResponse = $this->dashboardService->startContainer($data['id']);
return $this->returnWithJson($response, [$dockerResponse['message']], $dockerResponse['status']);
```

O controller não sabe falar com o Docker. Ele só confere que veio um `id` e repassa.

### 6. O service (`DashboardService::startContainer()`)

```php
$statusCode = $this->dockerClient->startContainer($id);
$message = match ($statusCode) {
    204 => 'Container started',
    304 => 'Container already started',
    // ...
};
return ['status' => $statusCode == 204 ? 200 : $statusCode, 'message' => $message];
```

Traduz o código HTTP do Docker numa mensagem e num status para o navegador. O 204 (sucesso sem
corpo) vira 200, porque o `dashboard.js` usa 200 para mostrar o ícone de sucesso.

### 7. O `DockerClient` (`Service\Api\DockerClient`)

Recebe do container de DI um cliente HTTP já apontado para o Docker (o `DockerClientFactory`
descobriu se é por TCP ou socket, ver [docker-client.md](docker-client.md)) e faz
`POST containers/<id>/start` na API do Docker Engine `v1.54`. Devolve só o código de status.

### 8. A volta

- O service devolve `['status' => 200, 'message' => 'Container started']`.
- O controller monta o JSON `["Container started"]` com status 200 (`returnWithJson()`).
- O Slim envia a resposta. O Caddy a repassa ao navegador e grava uma linha de log.
- No `dashboard.js`, o callback `success` escolhe o ícone pelo status (`defineIcon`: 200 →
  sucesso, 304 → aviso), mostra `response[0]` num SweetAlert e, ao fechar, recarrega a tabela.

### E se algo falhar no caminho?

Qualquer exceção, em qualquer camada, sobe até o middleware de erro do Slim, que chama o
`HttpErrorHandler`. A resposta vira JSON `{success: false, error: ...}` com o status da exceção
(422 para `ValidationException`, 500 para o resto). No navegador, um status 4xx/5xx cai no callback
`error`, que mostra "A internal error occurred". Detalhes em [error-handling.md](error-handling.md).

## Os outros caminhos

| Rota | Controller | O que faz |
|---|---|---|
| `GET /` | `HomeController::index` | Renderiza a página inicial (Twig). |
| `GET/POST /login` | `UserController::login` | GET renderiza o formulário; POST autentica e grava a sessão. |
| `GET/POST /register` | `UserController::register` | Cadastro público (sai na etapa 15). |
| `GET /logout` | `UserController::logout` | Destrói a sessão e redireciona para `/`. |
| `GET /dashboard` | `DashboardController::index` | Busca os contadores no Docker e renderiza a página. |
| `POST /dashboard/list` | `DashboardController::list` | JSON `{data: [...]}` para a DataTable. |
| `POST /dashboard/{start,stop,log}` | `DashboardController::start/stop/log` | Ações num container (`id` no corpo). |
| `POST /dashboard/create` | — | Aponta para um método que não existe (problema 14 do plano). |

Todas as rotas `/dashboard*` passam pelo `AuthMidleware`.

## Pegadinhas

- **O PHP não guarda nada entre requisições.** Cada requisição monta o app do zero: lê o `.env`,
  cria o container, registra as rotas, descobre o Docker. Variáveis `static` também zeram. O que
  precisa sobreviver vai para a sessão (Redis) ou para o banco.
- **O caminho que o Caddy enxerga não é o do PHP.** O Caddy só monta `www/public`. Um arquivo fora
  dali (`www/src`, `www/.env`) não pode ser servido por engano, porque o Caddy nem o enxerga.
- **`APP_ENV` vem do Caddy.** `App::isProduction()` lê `getenv('APP_ENV')`, que é preenchido pelo
  `env APP_ENV development` do Caddyfile. Num script de terminal (`bin/`), ele fica vazio; nos
  testes, vem do `phpunit.xml` (`testing`).

## Comandos úteis

```bash
just logs                                          # Caddy (acesso) e PHP (erros) ao vivo
docker compose logs -f caddy                       # só o acesso
curl -i -X POST http://localhost/dashboard/start   # sem sessão: 302 para /login
```
