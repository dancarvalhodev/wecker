# DockerClient (falando com o Docker)

## O que é

O Docker tem dois lados: o **daemon** (`dockerd`), que de fato cria e roda os containers, e os
clientes, como o comando `docker`. Eles conversam por uma **API HTTP**, a Docker Engine API:
`docker ps` é, por baixo, um `GET /containers/json`.

O Wecker é mais um cliente dessa API. O `App\Service\Api\DockerClient` faz as chamadas HTTP com o
Guzzle e devolve os resultados para o `DashboardService`.

## Analogia

O **interfone para o prédio do Docker**.

- O prédio (o daemon) atende por mais de uma entrada. Pode haver um **interfone na rua** (TCP,
  porta 2375) ou uma **linha interna** (o socket `/var/run/docker.sock`), que só funciona para quem
  está dentro.
- Antes do primeiro pedido, um **zelador** (o `DockerClientFactory`) **testa os interfones em
  ordem**: aperta e diz "alô?" (`_ping`). O primeiro que responde "OK" é entregue ao `DockerClient`,
  que só faz os pedidos por ele.
- Cada pedido é uma frase padronizada da API: "inicie o apartamento X" é
  `POST /containers/X/start`. O prédio responde com um código: 204 "feito", 304 "já estava assim",
  404 "não conheço esse apartamento".

## Como funciona aqui

### Duas classes: quem descobre e quem chama

- **`DockerClientFactory::create()`** descobre por qual entrada o daemon responde e devolve um
  `GuzzleHttp\ClientInterface` já apontado para ela.
- **`DockerClient`** só recebe esse cliente pronto no construtor e faz as chamadas.

Quem liga as duas é o `config/docker.php`, quando alguém pede um `DockerClient` ao container de
DI:

```php
DockerClient::class => function (DockerClientFactory $factory) {
    return new DockerClient($factory->create());
},
```

Assim o `DockerClient` só faz chamadas, e a descoberta fica num lugar só.

### A descoberta do daemon

`DockerClientFactory::create()` cria um `GuzzleHttp\Client` para cada candidato e testa
`GET _ping`, que deve responder `OK`:

| Ordem | `base_uri` | Quando funciona |
|---|---|---|
| 1 | `http://host.docker.internal:2375/v1.54/` | Docker Desktop, com "Expose daemon on tcp://localhost:2375 without TLS" ligado |
| 2 | `http://127.0.0.1:2375/v1.54/` | Daemon exposto em TCP na mesma máquina |
| 3 | `http://localhost/v1.54/` + `CURLOPT_UNIX_SOCKET_PATH => /var/run/docker.sock` | Linux (e WSL), pelo socket montado no container `php` |

No terceiro, o `localhost` da URL é só enfeite: a opção do cURL manda a requisição pelo arquivo de
socket, e o host não é usado para conectar.

Se nenhum responder, lança `RuntimeException('Docker não acessível')`.

O socket só funciona porque o `docker-compose.yml` monta `/var/run/docker.sock` no container `php`
e o `bin/php/entrypoint.sh` põe o usuário `www-data` (o do php-fpm) no grupo dono do socket
(ver [infra.md](infra.md)).

### As chamadas

| Método | Chamada à API | Devolve |
|---|---|---|
| `getContainers()` | `GET containers/json?all=true` | Lista de objetos (`json_decode` sem `true`: acesso com `->Id`, `->State`...) |
| `startContainer($id)` | `POST containers/$id/start` | Só o código HTTP |
| `stopContainer($id)` | `POST containers/$id/stop` | Só o código HTTP |
| `logs($id)` | `GET containers/$id/logs?stdout=1&stderr=1&tail=10` | `['statusCode' => ..., 'body' => ...]` |

A versão da API vem da constante `DockerClientFactory::VERSION = 'v1.54'`, no começo de toda URL. Fixar a versão
garante que as respostas tenham sempre o mesmo formato, mesmo se o Docker for atualizado.

### O que o `DashboardService` faz com isso

- **Contadores** (`getDockerStats()`): conta `running` (estado `running`) e `stopped` (`exited`,
  `created` ou `dead`). O total desconta os containers do próprio Wecker.
- **Tabela** (`getDockerContainerData()`): monta `{id, name, status, image, ports}` por container.
- **Esconde o próprio Wecker:** pula todo container cuja imagem contém `wecker`
  (`SYSTEM_IMAGE_SUFIX`), como `wecker-php` e `wecker-caddy`. Assim ninguém para o Wecker pelo
  próprio Wecker.
- **Start/stop:** traduz o código em mensagem com `match` (204 → "Container started", 304 →
  "Container already started").

## Pegadinhas

- **O `id` vai na URL, então é validado antes.** `DashboardController::extractContainerId()` só aceita
  caracteres hex (`ctype_xdigit`); sem isso, um `id` como `../../algo` mudaria o endpoint chamado.
- **O 404 nunca chega ao `match`.** O Guzzle, por padrão (`http_errors`), **lança exceção** em
  respostas 4xx e 5xx. Um container inexistente gera uma `ClientException`, que vira 500 no
  `HttpErrorHandler`, e não "Container not found". Só o 204 e o 304 chegam ao `match`.
- **A descoberta roda em toda requisição que usa o Docker.** O container de DI monta o
  `DockerClient` uma vez por requisição, e o PHP não guarda nada entre requisições. São até três
  `_ping` por requisição do dashboard (no Linux, os dois primeiros falham rápido).
- **Lista vazia vira erro.** Sem nenhum container, `getContainers()` devolve `[]`, e o `if
  (!$containers)` do service lança "Docker containers not found": o dashboard quebra (problema 8).
- **Portas montadas errado.** `Ports[0]->PublicPort . ':' . Ports[1]->PrivatePort` mistura duas
  entradas diferentes da lista (problema 9).
- **Logs com bytes estranhos.** Sem TTY, o Docker envia os logs multiplexados: cada bloco começa
  com um cabeçalho binário de 8 bytes, que aparece como lixo no modal (problema 10). O
  `header('Content-Type: text/plain')` dentro do `logs()` também não tem efeito útil, porque a
  resposta final é montada pelo controller como JSON.
- **Filtrar pela imagem é frágil.** Uma imagem reconstruída deixa o container antigo apontando
  para um `sha256:...` sem nome, que não contém `wecker` e passa a aparecer na tabela.
- **`request('GET', ...)`, não `get(...)`.** O `ClientInterface` do Guzzle só declara `request()`;
  os atalhos `get()`/`post()` existem só na classe `Client`. Como o `DockerClient` recebe a
  interface, usa `request()`.

## Comandos úteis

```bash
# as mesmas chamadas que o Wecker faz, pelo socket, do host
curl --unix-socket /var/run/docker.sock http://localhost/v1.54/_ping
curl --unix-socket /var/run/docker.sock 'http://localhost/v1.54/containers/json?all=true'

# versão da API que o daemon suporta
docker version --format '{{.Server.APIVersion}}'
```
