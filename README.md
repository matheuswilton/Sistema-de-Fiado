# FiadoFácil: Store Credit System

A web system that helps small stores track purchases on credit ("fiado", very common in Brazilian neighborhood shops), payments and customer debts, with reports, a reliability ranking and an area where customers check what they owe.

🔗 **Live site:** https://fiadofacil.free.je (test accounts below)

Capstone project (Projeto Integrador) for the IT Management program at FPM (Goiânia, Brazil), built by Matheus Wilton.

> The system itself is in Portuguese, because it was built for Brazilian stores.

## Why this project exists

Many neighborhood stores still write down credit purchases in a notebook. It's hard to know who has owed money for too long, how much each customer can still buy and who pays on time. FiadoFácil organizes this: every purchase respects the customer's credit limit, every payment pays off the oldest debt first, and the owner sees in one report who is up to date, owing or in default.

The problem is big: in 2026 Serasa recorded more than 84 million Brazilians in default (half of all adults), and Sebrae surveys show that about 4 in 10 micro-entrepreneurs sell on credit, often tracking everything on paper. Numbers and sources are in the [pitch](docs/PITCH.md).

## Capstone documentation (in Portuguese)

**Start here: [full documentation index](docs/README.md).**

| Document | Contents |
|---|---|
| [Technical documentation](docs/DOCUMENTACAO_TECNICA.md) | Requirements, roles, business rules, architecture, data dictionary, multi-store, customer account, security, integrations, setup, deploy, tests |
| [User manual](docs/MANUAL_DO_USUARIO.md) | Step by step for each screen and role, with screenshots |
| [API reference](docs/API.md) | Authentication, endpoints, responses and error codes |
| [Diagrams](docs/DIAGRAMAS.md) | Database (ERD and UML class diagram), use cases, system flow, purchase and payment flows, customer states, sequence and architecture |
| [Case study](docs/CASE.md) | Problem, requirements, solution and results |
| [Pitch](docs/PITCH.md) | Presentation script, data behind the project, comparison with other solutions and sources |
| [Live demo](docs/APRESENTACAO.md) | Script to show the system on a computer and on a phone |
| [Images](docs/img/) | Diagrams as PNG files for the slides, and [screenshots](docs/img/telas/) |

![Store home page](docs/img/telas/03-inicio.jpg)

![Database diagram](docs/img/banco-der.png)

## Tech

PHP · MySQL (SQL, transactions) · HTML · CSS · JavaScript (responsive layout: works on desktop and mobile) · GSAP (scroll-driven animation) · Git and GitHub · GitHub Actions (tests and automatic deploy) · Playwright (browser tests) · Snyk Code (security analysis) · InfinityFree hosting

## Technical highlights

- **Relational database with 7 tables** (`comercios`, `clientes`, `usuarios`, `compras`, `pagamentos`, `contas_clientes`, `contas_vinculos`) and reports built with SQL queries (filters by period and status, top debtors, financial summary).
- **Locked transactions** (`SELECT ... FOR UPDATE`): two purchases made at the same time can't push a customer over their credit limit.
- **Security**:
  - prepared statements, against SQL injection;
  - passwords stored with `password_hash`;
  - CSRF protection on forms;
  - output escaped with `htmlspecialchars`, against XSS;
  - `HttpOnly` and `SameSite` session cookie, and a new session on every login;
  - the database password stays out of the code.
- **Role-based access**: administrator, regular user and customer (read only).
- **Company-style workflow**: every change goes to the `teste` branch first; GitHub Actions runs the browser tests (Playwright, on a fresh MariaDB database) and publishes to a **staging site** (fiadofacilteste.free.je). A pull request then takes it to `main`, the tests run again, and only then the live site is updated by FTP.
- **Security analysis**: Snyk Code scans the code on every push and pull request; results show in the repository's Security tab. Its first scan found 2 medium issues (an open redirect and an unfiltered value from the database in an SQL command), both fixed.

## Features

- Login with two staff roles: **Administrator** and **Regular user**.
- Add, edit, search (by name or phone) and delete customers. Only the administrator can delete.
- Statement for each customer with debt, available limit, purchases and payments.
- Credit purchases with credit limit validation.
- Payments, blocking payments larger than the debt, plus a "Pay off full debt" button.
- Debt report with filters by period and status, ready to print.
- Customer reliability ranking.
- Financial summary and top debtors (administrator only).
- Staff management: create, block, unblock and reset passwords (administrator only).
- Everyone can change their own password and switch dark mode under "Minha conta".
- **Collections ("Cobranças")**: who will be overdue in the next 3 days and who already is, with a ready WhatsApp message.
- **Store photo**: each store uploads a photo shown behind its sign-in page (re-encoded on the server for safety).
- Lean text-only menu: a "Nova venda" button (purchase or payment), 4 main items and 2 secondary ones; grouped pages show tabs.
- **Customer access (read only):** the customer creates one account with their CPF and sees their debt at every store that confirmed them (see "Customer account" below). The older per-store login request still works.

## Multi-store (each store has its own login)

Each store has its own address, login, data and look, and no store can see another store's data:

| Store | Address |
|---|---|
| Mercearia do Zé | `/loja/mercearia-do-ze` |
| Padaria Pão Quente | `/loja/padaria-pao-quente` |
| Açougue Bom Corte | `/loja/acougue-bom-corte` |

- Every query is filtered by the logged-in store (`comercio_id` taken from the session, never from the form or URL), and every action by id checks that the record belongs to the store (protection against IDOR).
- The platform owner uses `painel.php` to see all stores, create and block stores, and enter any store as administrator.
- The database is upgraded automatically on the first visit after the update (`migracoes.php`): existing data becomes the "Loja principal" (`/loja/principal`).

## Customer account (one login for every store)

- **Staff** open the site, type the store name ("Qual é a sua loja?") and then their username and password. The direct address `/loja/store-name` still works, in any letter case.
- **Customers** create one account with their **CPF** (Brazilian ID number) and a password, and see their store credit at **every** store on a single page, each with its own Pix QR code.
- Safety: the account can only be created if a store already has that CPF **with the same phone number**, and each store must **confirm** the link before its data shows up. Stores never see what the customer owes at other stores.
- CPF is checked (check digits), unique per store, and shown masked (`***.982.247-**`).

## Integrations (APIs)

Each store administrator manages them in **Minha loja** (`minha_loja.php`):

| Integration | What it does | How |
|---|---|---|
| **Pix QR code** | The customer sees a Pix QR code and a "copy and paste" code with the debt amount on the "Minha dívida" page | Static BR Code (Central Bank EMV format with CRC16) built in PHP from the store's Pix key; QR drawn in the browser |
| **WhatsApp** | "Cobrar no WhatsApp" button on the statement and on the home page opens a chat with a ready reminder (amount + Pix key) | `wa.me` click-to-chat link, no key needed |
| **ViaCEP** | Typing a postcode fills in the customer's address | Public API called from the browser |
| **FiadoFácil API** | Read-only REST API returning the store's data in JSON (`resumo`, `clientes`, `cliente&id=N`) | `api.php` with the store key in the `X-Api-Key` header; only the SHA-256 hash of the key is stored |

Pix payments are confirmed by the store, which then records the payment. Automatic confirmation (webhook from a payment provider) and e-mail need paid hosting, because InfinityFree blocks outgoing server calls. Its anti-bot protection also blocks API calls made outside a browser, so the API is fully usable on XAMPP or paid hosting.

## Business rules

- **Customer status**: *em dia* (no debt), *devendo* (owing) or *inadimplente* (in default: the oldest unpaid purchase is more than 30 days old). Payments pay off the oldest purchases first.
- **Ranking score**: starts at 100, loses 2 points per day open beyond 7 days, loses 10 if the debt is over 50% of the limit, and gains 5 if the customer has paid at least once.
- Regular users add customers with a default limit of R$ 500. Only the administrator changes limits.
- A customer with open debt can't be deleted. Deleting a customer erases their history and access.

## Test accounts

| Role | User | Password |
|---|---|---|
| Administrator | `admin` | `admin123` |
| Regular user | `usuario` | `user123` |

Sample customers: João Silva `(62) 99999-9999`, Maria Souza `(62) 98888-8888`, Carlos Pereira `(62) 97777-7777` and Ana Lima `(62) 96666-6666`.

> The test accounts are public. When installing the system for real use, change the passwords on first login (menu **Minha conta**). The system shows a warning while a sample password is still in use.

## Team

| Member | Responsibilities |
|---|---|
| Matheus Wilton | Responsible for the whole site: business rules, development and database |

## Running locally (XAMPP)

1. Copy the `fiadofacil` folder to `C:\xampp\htdocs\fiadofacil`. `login.php` must be directly inside it.
2. In XAMPP, start **Apache** and **MySQL**.
3. At `http://localhost/phpmyadmin`, create a database called `sistema_fiado` (utf8mb4), select it, go to **Import** and upload `fiadofacil/sql/banco.sql`.
4. Copy `config.local.example.php` to `config.local.php` (inside `fiadofacil`) and check the values. XAMPP defaults are host `localhost`, user `root`, empty password and database `sistema_fiado`.
5. Open `http://localhost/fiadofacil/login.php`.

<details>
<summary><b>Automatic deploy to InfinityFree</b></summary>

Every time `main` receives changes in the `fiadofacil` folder, GitHub Actions uploads the files by FTP to the InfinityFree `htdocs` folder. `config.local.php` and the `sql` folder are never uploaded.

One-time setup: on GitHub, under **Settings → Secrets and variables → Actions → New repository secret**, create:

| Name | Value (InfinityFree panel → FTP Details) |
|---|---|
| `FTP_SERVER` | FTP Hostname (e.g. `ftpupload.net`) |
| `FTP_USERNAME` | FTP Username (e.g. `if0_12345678`) |
| `FTP_PASSWORD` | FTP Password |

To publish right away without changing code: **Actions → Publicar na InfinityFree → Run workflow**.

</details>

<details>
<summary><b>Manual deploy to InfinityFree</b></summary>

1. In the panel, create the MySQL database and write down host, user, password and database name.
2. In the host's phpMyAdmin, select the database, go to **Import** and upload `sql/banco.sql`. Importing again doesn't duplicate data.
3. Upload the contents of the `fiadofacil` folder to `htdocs`, without an extra subfolder. **You don't need to upload the `sql` folder.**
4. Edit `config.local.php` with the database details **directly on the server** (or fill it in before uploading).
5. Open the site address. `index.php` redirects to the login page.

</details>

<details>
<summary><b>Troubleshooting</b></summary>

- **404 when opening the login page:** the folder isn't in `htdocs` or is nested twice (`fiadofacil\fiadofacil`).
- **"Falta o arquivo config.local.php" (missing config file):** copy `config.local.example.php` to `config.local.php` and fill it in.
- **"Não foi possível conectar ao banco" (can't connect to the database):** check host, user, password and database name in `config.local.php`.
- **MySQL won't start in XAMPP:** another Windows MySQL service may be using port 3306. Stop it in `services.msc`.

</details>

## Configuration security

- The database password **never** goes in the code. It only lives in `config.local.php`, which is in `.gitignore`. **Careful: GitHub's "Upload files" button ignores `.gitignore`, so don't upload `config.local.php` through it.**
- If a database password was ever published on GitHub, change it in the hosting panel. Deleting it from the file isn't enough, because Git history keeps the old version.
- New passwords must have at least 8 characters.

## Structure

```
fiadofacil/
├── *.php                       system pages
├── cabecalho.php / rodape.php  shared layout and menu
├── funcoes.php                 shared functions (session, security, purchase and payment rules, customer status)
├── conexao.php                 connects to the database using config.local.php
├── config.local.example.php    config file template
├── css/estilo.css              styles (desktop and mobile)
├── api.php                     read-only REST API (JSON) for each store
├── minha_loja.php              store integrations: Pix key, WhatsApp and API key
├── js/script.js                mobile menu, tables as cards on mobile, confirmations, phone mask, Pix QR, postcode lookup
├── js/qrcode.js                QR code generator (MIT, Kazuhiko Arase)
├── manifest.json               lets users add the site to the phone's home screen
├── img/                        app icons
└── sql/banco.sql               table creation and sample data
```

## Next steps

- Automatic Pix confirmation through a payment provider webhook (needs paid hosting).
- E-mail for password recovery and overdue reminders.
- Customer access link sent by WhatsApp, so the customer gets in without waiting for approval.
- Export the report to CSV.
