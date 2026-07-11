# Frontend

Server-rendered **Twig** templates + **jQuery** page modules. No build step, no SPA framework, no bundler — all libraries are vendored as static files and included with `<script>`/`<link>` tags.

## Where things live

- Templates: `www/templates/`
  - `layout/base.html.twig` — the shell (see below).
  - `home/index.html.twig` — public landing page.
  - `crud/user/{login,register}.html.twig` — auth forms.
  - `dashboard/index.html.twig` — container table + logs modal.
- Page JS: `www/public/assets/js/pages/{login,register,dashboard}.js`.
- Vendored libs: `www/public/assets/js/{jquery,boostrap,datatables,sweetalert2}/` and `assets/css/{boostrap,datatables,reset.css,style.css}`. (Note the `boostrap` folder spelling — keep it.)

## Template structure

Every page does `{% extends "/layout/base.html.twig" %}` and fills three blocks:

- `{% block content %}` — page body, laid out with Bootstrap 5 utility classes.
- `{% block scripts %}` — **must call `{{ parent() }}` first** to keep the base libs (bootstrap bundle, jQuery, SweetAlert2, DataTables), then add the page script and an inline `$(document).ready(...)` that calls the module's `init()`.
- `{% block title %}` / `{% block head %}` — optional overrides.

`base.html.twig` renders the navbar using the Twig global `auth.logged` (Login/Register vs. Logout) and the footer uses `global.year`. Both globals come from `config/twig.php`.

Server → template data for the dashboard is passed as a `docker` object (`docker.running/stopped/total`), rendered into the stat cards with a `|default(0)` filter. The table body is left empty and filled client-side.

## JS module pattern

Each page script is an **IIFE returning `{ init }`** assigned to a global (`Login`, `Register`, `Dashboard`). Structure to follow:

```js
const Foo = (function () {
    const handleX = () => { /* wire DOM/events */ };
    function helper() { /* ... */ }
    const init = () => { handleX(); };
    return { init };
})();
```

- jQuery throughout (`$`), delegated events via `$(document).on('click', '.selector', ...)`.
- Feedback uses **SweetAlert2** (`Swal.fire({ icon, title, html/text })`).
- Client-side form validation runs before AJAX (empty-field checks, password match) and shows a `warning` Swal; the server re-validates.

## AJAX ↔ API contract

- Forms serialize with `$(this).serialize()` and POST to the same path as the page (`/login`, `/register`).
- Dashboard actions POST `{ id }` to `/dashboard/{start|stop|log}`; the DataTable loads rows from `POST /dashboard/list` with `dataSrc: 'data'` (matches the service's `{ data: [...] }` shape).
- Row shape consumed by `dashboard.js`: `{ id, name, status, image, ports }`. `status === 'running'` decides Stop-vs-Start button and the status badge colour; `ports` falls back to `-`.
- Success responses for start/stop are read as an **array** (`response[0]`) and the HTTP status drives the Swal icon (`defineIcon`: 200→success, 304→warning, else error). Logs are rendered into the `#logModal` `<pre>` (only the last 10 lines are returned by the API).

⚠️ Contract mismatch to be aware of: `login.js`/`register.js` read validation errors from `response.messages`, but the backend `HttpErrorHandler` emits them under `error` (and success is `{success: true}`). If you touch auth flows, align the two — don't assume the current client keys are correct.

## Known gaps / WIP

- `dashboard/index.html.twig` calls `Create.init()` and there is a `POST /dashboard/create` route, but no `create.js` module and no controller `create()` method exist yet — "Create containers" is on the README roadmap.
- `www/public/assets/js/sweetalert2` etc. are minified vendor drops; don't edit them.
