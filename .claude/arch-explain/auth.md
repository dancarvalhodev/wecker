# Autenticação (sessão)

## O que é

HTTP não tem memória: cada requisição chega sem saber das anteriores. Para o servidor lembrar que
você já fez login, ele guarda um registro do seu lado (a **sessão**) e entrega ao navegador só um
número que aponta para esse registro (o **cookie** `PHPSESSID`). Em toda requisição, o navegador
devolve o número, e o servidor reencontra o registro.

## Analogia

A **pulseira de um evento**.

- Na entrada (login), você mostra o documento (e-mail e senha). Conferido, ganha uma **pulseira com
  um número** (o cookie `PHPSESSID`).
- A organização guarda numa **lista** (o Redis): "pulseira 8f3a… = usuário 1".
- O **segurança da área VIP** (o `AuthMidleware`) não pede documento de novo: olha a pulseira e
  confere na lista.
- Quando você sai (logout), a organização **risca o número da lista**. A pulseira continua no seu
  pulso, mas não abre mais nada.
- A pulseira é **trocada na hora do login** (`session_regenerate_id`): se alguém tivesse anotado o
  número da sua pulseira de visitante antes, ele não vale mais.

## Como funciona aqui

### As peças

| Peça | Papel |
|---|---|
| `public/index.php` | `session_start()` em **toda** requisição, antes do app. |
| `config/php/php.ini` | `session.save_handler = redis` e `session.save_path = "tcp://redis:6379"`: a lista fica no Redis, não em arquivos. |
| `Service\AuthService` | Lê e grava `$_SESSION['user_id']`. |
| `Service\AuthMidleware` | Barra as rotas `/dashboard*` sem sessão. |
| `Model\User\UserModel::authenticateUser()` | Confere e-mail e senha. |
| `config/twig.php` | Global `auth.logged` para o menu (Login ou Logout). |

### O login, passo a passo

1. `login.js` confere se os campos não estão vazios e faz `POST /login` com `email` e `password`.
2. `UserController::login()`:
   - se já está logado, redireciona para `/dashboard`;
   - `GeneralNormalizer::clean()`: tira espaços das pontas e põe o e-mail em minúsculas;
   - `LoginValidator::validate()`: e-mail válido (até 150 caracteres) e senha de 1 a 255; senão,
     `ValidationException` (422);
   - `UserModel::authenticateUser()`: busca pelo e-mail (`UserRepo::findByEmail()`) e confere com
     `password_verify()`. Usuário inexistente **ou** senha errada geram a mesma
     `UserException(['Wrong password.'])`, para não revelar quais e-mails existem;
   - `AuthService::login($user)`: `session_regenerate_id(true)` e `$_SESSION['user_id'] = id`.
3. Resposta `{"success": true}`; o `login.js` redireciona para `/dashboard`.

### A senha

`password_hash($senha, PASSWORD_DEFAULT)` gera um hash **bcrypt** com sal aleatório embutido:
o mesmo texto gera hashes diferentes a cada vez, e o banco nunca guarda a senha. O
`password_verify()` lê o sal de dentro do hash e refaz a conta. No PHP 8.4+, o custo padrão do
bcrypt é 12.

### O segurança: `AuthMidleware`

```php
public function __invoke($request, $handler)
{
    if (!$this->auth->isAuthenticated()) {
        $response = new \Slim\Psr7\Response();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    return $handler->handle($request);
}
```

Um **middleware** fica entre o roteador e o controller: decide se a requisição segue
(`$handler->handle()`) ou volta antes. Aqui, sem `user_id` na sessão, volta um 302 para `/login`.
É ligado rota a rota em `routes/web.php`, com `->add(AuthMidleware::class)`.

### O logout

`AuthService::logout()` chama `session_destroy()`: apaga o registro no Redis. O cookie fica no
navegador, mas aponta para nada.

## Pegadinhas

- **O nome é `AuthMidleware`, com um `d` só.** Está assim em todo lugar; não corrija em um ponto só.
- **A sessão guarda só o id.** Um usuário apagado do banco continua "logado" até a sessão expirar,
  porque ninguém confere se o id ainda existe. A etapa 12 passa a recarregar o usuário a cada
  requisição.
- **Ninguém confere o papel.** Qualquer usuário logado faz tudo no dashboard, e o `UserRepo::hydrate()`
  ainda monta todo usuário como `ROLE_USER` (etapa 12).
- **Não há cadastro pela web.** O `/register` saiu na etapa 8; até a etapa 12, usuários só pelo
  `just admin-create`.
- **A mensagem de erro do login não aparece.** O 422 cai no callback `error` do `login.js`, que
  mostra "A internal error occurred"; e o código procura os erros em `response.messages`, mas o
  servidor manda em `error` (problema 12).
- **CSRF via `SameSite=Strict`, sem token.** O `php.ini` marca o cookie de sessão como
  `SameSite=Strict`: o navegador não o envia em requisições vindas de outro site, então um `POST`
  forjado chega sem login. Efeito colateral: quem chega ao Wecker por um link de outro site vê a
  primeira página como deslogado.

## Comandos úteis

```bash
# sessões guardadas no Redis
docker compose exec redis redis-cli --scan --pattern 'PHPREDIS_SESSION:*'

# conteúdo de uma sessão
docker compose exec redis redis-cli get 'PHPREDIS_SESSION:<id>'

# login pelo terminal (guarda o cookie em cookies.txt)
curl -i -c cookies.txt -d 'email=voce@exemplo.com&password=...' http://localhost/login
```
