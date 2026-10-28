# Injeção de dependência (PHP-DI)

## O que é

Uma classe raramente trabalha sozinha: o `UserController` precisa do `UserModel`, que precisa do
`UserRepo`, que precisa de uma conexão com o banco. **Injeção de dependência** é a classe
*receber* essas peças prontas no construtor, em vez de criá-las com `new` lá dentro.

O **container** é quem monta essas peças. Você pede "um `UserController`" e ele descobre, pelos
tipos do construtor, tudo o que precisa criar antes, e em que ordem.

## Analogia

O **almoxarifado** de uma obra.

- O pedreiro (o controller) não fabrica as próprias ferramentas. Ele chega ao balcão e diz "preciso
  de uma furadeira", e o almoxarife entrega uma pronta, com broca e bateria.
- A **ficha de cada ferramenta** é o construtor da classe: os tipos dos parâmetros dizem o que ela
  precisa. Na maioria das vezes, o almoxarife lê a ficha sozinho (**autowiring**).
- Algumas ferramentas exigem montagem especial, como a conexão com o banco, que depende de usuário,
  senha e host. Essas têm uma **instrução escrita** em `config/*.php` (as **definições**).
- O almoxarife **empresta a mesma ferramenta** para quem pedir de novo durante o dia (a mesma
  requisição): não fabrica outra.

## Como funciona aqui

### Onde o container nasce

`App\Bootstrap\ContainerFactory::create()` é usado pela web (`Main`), pelos scripts de terminal
(`bin/bootstrap.php`) e pelos testes (`IntegrationTestCase`):

```php
$builder = new ContainerBuilder();
$builder->useAttributes(true);
$builder->useAutowiring(true);

if (Application::isProduction()) {
    $builder->enableCompilation(dirname(__DIR__) . '/storage/cache');
    $builder->writeProxiesToFile(true, dirname(__DIR__) . '/storage/cache');
}

$builder->addDefinitions(dirname(__DIR__) . '/config/database.php');
$builder->addDefinitions(dirname(__DIR__) . '/config/twig.php');
$builder->addDefinitions(dirname(__DIR__) . '/config/handler.php');
$builder->addDefinitions(dirname(__DIR__) . '/config/docker.php');
```

- `useAutowiring(true)`: o container lê os tipos dos construtores sozinho.
- `useAttributes(true)`: permite atributos como `#[Inject]` (não usados hoje).
- Em produção, `enableCompilation()` gera um PHP com as receitas já resolvidas, para não ler os
  construtores por reflexão a cada requisição.

### Autowiring: o caso comum

```php
public function __construct(Twig $twig, UserModel $userModel, LoginValidator $loginValidator,
                            GeneralNormalizer $generalNormalizer, AuthService $auth)
```

Para criar o `UserController`, o container vê cinco tipos e cria cada um, descendo a árvore:

```
UserController
├── Twig               ← definição em config/twig.php
├── UserModel
│   └── UserRepo
│       └── Connection ← definição em config/database.php
│           └── ConnectionFactory (autowiring)
├── LoginValidator     (sem dependências)
├── GeneralNormalizer  (sem dependências)
└── AuthService        (sem dependências)
```

Nada disso está escrito em lugar nenhum: o container deduz pelos tipos. Uma classe nova com
construtor tipado funciona sem configuração.

### Definições: quando o autowiring não basta

Cada arquivo de `config/` devolve um array `[Classe => como criar]`.

| Arquivo | Classe | Por que precisa de definição |
|---|---|---|
| `database.php` | `Doctrine\DBAL\Connection` | O DBAL não tem um construtor que o container saiba preencher: precisa de host, usuário e senha. A closure pede um `ConnectionFactory` (por autowiring, pelo tipo do parâmetro) e chama `create($_ENV['DB_NAME'])`. |
| `twig.php` | `Slim\Views\Twig` | Precisa da pasta `templates/`, do cache (só em produção) e das variáveis globais `auth.logged` e `global.year`. |
| `handler.php` | `ResponseFactoryInterface` | É uma **interface**: o container não sabe qual implementação escolher. `DI\create(ResponseFactory::class)` diz: use a do `slim/psr7`. |
| `handler.php` | `HttpErrorHandler` | `DI\autowire()` só torna explícito o autowiring; o construtor do `ErrorHandler` do Slim pede o `ResponseFactoryInterface` acima. |
| `docker.php` | `DockerClient` | O construtor pede um `GuzzleHttp\ClientInterface` (uma interface) já apontado para o daemon. A closure pede o `DockerClientFactory`, que descobre o daemon, e entrega o cliente pronto. |

### Os controllers e o Slim

O `php-di/slim-bridge` (`Bridge::create($container)` em `AppFactory::create()`) faz duas coisas:

1. O Slim pede ao container o controller da rota (`[UserController::class, 'login']`).
2. Os parâmetros do **método** são preenchidos pelo nome e pelo tipo: `$request`, `$response` e,
   se houvesse, argumentos da rota como `{id}`.

## Pegadinhas

- **Não faça `new` de um colaborador.** Uma classe que faz `new DockerClient()` por conta própria
  ignora o container: não dá para trocar a peça nem configurá-la num lugar só. Receba pelo construtor.
- **Mesma instância na requisição.** `$container->get(X::class)` devolve sempre o mesmo objeto
  durante a requisição. Uma classe que guarda estado numa propriedade compartilha esse estado com
  todo mundo que a recebeu.
- **Interface sem definição dá erro.** Pedir uma interface que não está em `config/` gera um erro
  do PHP-DI dizendo que ela não pode ser instanciada. A solução é uma linha em `config/`.
- **Não injete o container.** Uma classe que recebe o `ContainerInterface` e chama `get()` lá
  dentro esconde as dependências (o construtor não diz mais o que ela usa). Só o bootstrap, o
  `config/` e os scripts de `bin/` tocam no container.
- **Cache em produção.** Com a compilação ligada, mudar um construtor exige apagar
  `www/storage/cache`, senão o container usa a receita antiga.

## Comandos úteis

```bash
# ver o que o container monta para uma classe
docker compose exec php php -r 'require "vendor/autoload.php"; Dotenv\Dotenv::createImmutable(".")->safeLoad(); var_dump(get_class(App\Bootstrap\ContainerFactory::create()->get(App\Model\User\UserModel::class)));'
```
