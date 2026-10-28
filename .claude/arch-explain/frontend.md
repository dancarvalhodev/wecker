# Frontend (Twig + jQuery)

## O que é

O frontend do Wecker tem duas metades:

- **Twig**, no servidor: monta o HTML da página antes de enviá-la (menu, contadores, estrutura da
  tabela).
- **jQuery**, no navegador: depois que a página chegou, busca dados e executa ações sem recarregar
  (AJAX): preenche a tabela, inicia e para containers, abre os logs.

Não há build, bundler nem framework de SPA. As bibliotecas (Bootstrap 5, jQuery, DataTables,
SweetAlert2) estão copiadas em `www/public/assets/` e entram por `<script>` e `<link>`.

## Analogia

Um **restaurante com cardápio impresso e garçom de mesa**.

- O **Twig é a gráfica**: imprime o cardápio do dia antes de você sentar (a página, com o menu e os
  contadores do momento). Uma vez impresso, não muda.
- O **jQuery é o garçom** que fica na mesa: quando você pede algo ("inicie este container"), ele
  vai até a cozinha (AJAX), volta com a resposta e atualiza só aquele pedaço da mesa (um alerta,
  a tabela), sem trocar o cardápio inteiro.
- O **layout base** é o modelo de papel timbrado: cabeçalho e rodapé iguais em todas as páginas;
  cada página só preenche o miolo.

## Como funciona aqui

### Onde fica cada coisa

```
www/
├── templates/
│   ├── layout/base.html.twig        ← o papel timbrado: <head>, menu, rodapé, scripts base
│   ├── home/index.html.twig         ← página inicial
│   ├── crud/user/login.html.twig    ← formulário de login
│   └── dashboard/index.html.twig    ← contadores, tabela e modal de logs
└── public/assets/
    ├── js/pages/{login,dashboard}.js   ← um módulo por página
    ├── js/{jquery,boostrap,datatables,sweetalert2}/   ← bibliotecas (não editar)
    └── css/{reset.css,style.css,boostrap/,datatables/}
```

### Herança de templates

Toda página começa com `{% extends "/layout/base.html.twig" %}` e preenche **blocos**:

```twig
{% block content %} ... o miolo da página ... {% endblock %}

{% block scripts %}
    {{ parent() }}                                   {# mantém os scripts do base #}
    <script src="assets/js/pages/dashboard.js"></script>
    <script>
        $(document).ready(function () { Dashboard.init(); });
    </script>
{% endblock %}
```

O `{{ parent() }}` é obrigatório no bloco `scripts`: sem ele, a página perde o jQuery, o Bootstrap
e o resto, e o módulo da página quebra.

### Dados do servidor para o template

- **Globais** (`config/twig.php`), disponíveis em todo template: `auth.logged` (o menu mostra
  Login ou Logout) e `global.year` (o rodapé).
- **Por página**: o `DashboardController::index()` passa `docker.running`, `docker.stopped` e
  `docker.total`, mostrados nos cartões com `|default(0)`.

### O padrão dos módulos JS

Cada arquivo de `pages/` é uma função que se executa na hora (IIFE) e devolve só o `init`:

```js
const Dashboard = (function () {
    let table;                                   // privado: ninguém de fora acessa
    const handleTable = () => { /* DataTable */ };
    const handleButtons = () => { /* cliques */ };
    const init = () => { handleTable(); handleButtons(); };
    return { init };                             // público: só o init
})();
```

O IIFE cria um escopo fechado: as funções internas não vazam para o resto da página nem colidem com
as de outro módulo.

### O dashboard

1. A página chega com a tabela vazia (`<tbody></tbody>`).
2. A DataTable faz `POST /dashboard/list` e usa o campo `data` da resposta (`dataSrc: 'data'`).
   Cada linha é `{id, name, status, image, ports}`.
3. A coluna de ações desenha **Stop** se `status === 'running'`, senão **Start**, mais **Logs**,
   todos com `data-id`.
4. Os cliques usam **eventos delegados**: `$(document).on('click', '.start-container', ...)`. Como
   os botões são recriados a cada recarga da tabela, o evento fica no `document`, que nunca muda.
5. A resposta de start/stop é um array (`response[0]`), e o ícone do SweetAlert vem do status HTTP
   (`defineIcon`: 200 → sucesso, 304 → aviso, outro → erro).
6. Os logs vão para o `<pre id="logContent">` do modal com `.text()`, que não interpreta HTML.

### Os formulários

`login.js` confere campos vazios, mostra um aviso do SweetAlert e só então fazem o `POST` com `$(this).serialize()`. O servidor valida de novo:
a checagem do navegador é conforto, não segurança.

## Pegadinhas

- **Escape o que vem do Docker.** A DataTable insere o `render` como HTML. `name` e `image` passam
  por `escapeHtml()`; um campo novo vindo do Docker precisa do mesmo cuidado, senão um container
  chamado `<img src=x onerror=alert(1)>` executa código na página.
- **Erro chega no callback `error`.** Respostas 4xx/5xx não passam pelo `success` do `$.ajax`; leia
  `xhr.responseJSON.error` (ver [error-handling.md](error-handling.md)).
- **Caminhos relativos.** O `base.html.twig` usa `assets/...` sem `/` inicial. Funciona porque
  todas as páginas estão na raiz (`/dashboard`, `/login`); uma página em `/users/edit` buscaria
  `/users/assets/...`.
- **`boostrap`, não `bootstrap`.** É o nome real das pastas; não corrija sem atualizar os
  templates.
- **Não edite os `.min.js` das bibliotecas.** São cópias dos pacotes; para atualizar, substitua o
  arquivo inteiro.

## Comandos úteis

```bash
# a resposta que a DataTable recebe (com o cookie de um login feito via curl, ver auth.md)
curl -s -b cookies.txt -X POST http://localhost/dashboard/list

# conferir se um asset é servido e com cache
curl -sI http://localhost/assets/js/pages/dashboard.js
```
