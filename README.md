# Wecker
**Uma interface simples, inspirada no Portainer, para controlar seus containers visualmente.**

![img.png](docs/img2.png)
![img.png](docs/img.png)

Obs.: no Windows e no macOS, requer o Docker Desktop.

# Instalação
1. Instale o Docker.
    1. No Windows, ative o WSL2 e a opção "Expose daemon on tcp://localhost:2375 without TLS".
2. Instale o [just](https://just.systems) (ex.: `sudo apt install just`, `brew install just` ou `winget install Casey.Just`).
3. Rode `just install`: ele cria os arquivos `.env`, constrói e sobe os containers, instala as dependências do Composer, aplica as migrações e pede nome, e-mail e senha do admin.
4. Abra http://localhost.

# Comandos do dia a dia
`just` lista todos. Os mais usados:

```bash
just up | stop | status | logs   # containers
just migrate                     # aplica as migrações pendentes
just admin-create                # cria outro admin
just test | stan | cs            # testes, análise estática, padrão de código
```

# Como funciona
As notas de arquitetura ficam em `.claude/arch-explain/`. Comece por `request-flow.md`.

# Próximos passos
- Revamp visual.
- Criar containers pela interface.
- Usuários e papéis (Admin / User).
- Depois: health status, informações da instalação do Docker, console web, log de operações e logs em tempo real.
