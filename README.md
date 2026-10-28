# Wecker
**Uma interface web simples, inspirada no Portainer, para controlar os containers Docker da sua máquina.**

![img.png](docs/img2.png)
![img.png](docs/img.png)

## O que faz
- Lista todos os containers, com status, imagem e portas publicadas.
- Inicia e para containers, e mostra as últimas linhas do log.
- Cria containers pela interface: imagem (baixada se não existir), nome, portas, variáveis de ambiente, restart policy e "iniciar após criar".
- Acesso por login; os usuários são criados pelo terminal.

## Stack
PHP 8.5 (Slim 4) no php-fpm, Caddy, PostgreSQL e Redis, tudo no Docker Compose. O app fala direto com a API do Docker Engine.

## Instalação
1. Instale o Docker. No Windows e no macOS, use o Docker Desktop (no Windows, com WSL2).
2. Instale o [just](https://just.systems) (ex.: `sudo apt install just`, `brew install just` ou `winget install Casey.Just`).
3. Rode `just install`: ele cria os arquivos `.env`, constrói e sobe os containers, instala as dependências, aplica as migrações e pede nome, e-mail e senha do primeiro admin.
4. Abra http://localhost.

Depois de reiniciar a máquina, rode `just up`: os containers não sobem sozinhos.

## Comandos do dia a dia
`just` lista todos. Os mais usados:

```bash
just up | stop | status | logs   # containers
just admin-create                # cria outro usuário
just migrate                     # aplica as migrações pendentes
just test | stan | cs            # testes, análise estática e padrão de código
```

Os testes rodam contra o Docker real (com containers descartáveis) e um banco próprio, `wecker_test`.

## Como funciona
As notas de arquitetura ficam em `.claude/arch-explain/`. Comece por `request-flow.md`.

## Roadmap

### Controle de usuários
Hoje todo usuário é criado pelo terminal (`just admin-create`) e pode fazer tudo. O próximo passo é gerenciar o acesso pela interface:
- **Papéis:** *Admin* faz tudo; *User* só acompanha (vê os containers e os logs, sem iniciar, parar ou criar).
- **Tela de usuários** (só Admin): listar, criar, trocar o papel e ativar/desativar contas.
- **Acesso revogado na hora:** um usuário desativado perde a sessão já na requisição seguinte.
- **Proteções:** o admin não desativa a própria conta nem rebaixa o último admin.

### Depois
- Health status dos containers e informações da instalação do Docker.
- Logs em tempo real e console web.
- Registro das operações (quem iniciou, parou ou criou cada container).
- Volumes e redes na criação de containers.
