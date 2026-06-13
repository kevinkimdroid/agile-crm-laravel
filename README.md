<p align="center">
  <img src="public/images/agile-craft-logo.png" width="220" alt="Agile Craft CRM">
</p>

# Agile Craft CRM

A Laravel-based CRM for life‑assurance / financial‑services operations. It wraps an existing **vTiger** database and an **Oracle ERP** behind a modern UI, adding ticketing, complaints, marketing, call‑centre (PBX) integration, mail, SMS, and finance receipt reprinting.

> Formerly branded "Geminia CRM". The application has been rebranded to **Agile Craft**.

---

## Features

- **Dashboard** — live KPIs (contacts, leads, deals, clients, sales by person) with caching.
- **Contacts / Leads / Deals** — backed by the vTiger database.
- **Support** — client lookup (Group / Individual Life, Mortgage, Group Pension), maturities, mortgage renewals.
- **Tickets** — creation, SLA reminders, auto‑create from email, feedback collection.
- **Compliance** — complaints management (create / edit / resolve), auto‑complaints from email.
- **Marketing** — campaigns and broadcast (email + SMS).
- **Finance** — receipt reprint rendered from an RTF template (Oracle FMS source), discharge vouchers.
- **Mail** — Microsoft Graph (Office 365), IMAP, and SMTP for inbound/outbound email.
- **Telephony** — Asterisk/Issabel PBX click‑to‑call, CDR sync, call reports.
- **Reports** — calls, contacts, work activities, audit, with Excel/PDF export.
- **Settings & Admin** — users, roles/modules, departments, PBX extension mapping.

## Tech stack

- **Backend:** PHP 8.1+ (tested on 8.2), Laravel 8.83
- **Frontend:** Blade, Bootstrap, Tailwind, Vite
- **Databases:** MySQL (vTiger), Oracle (ERP/FMS, via `yajra/laravel-oci8`)
- **Integrations:** Microsoft Graph + IMAP (mail), Advanta (SMS), Asterisk (PBX), Laravel Socialite (social)
- **PDF/Docs:** dompdf, TCPDF/FPDI, Maatwebsite Excel
- **ERP bridge:** a Python Flask service in [`erp-clients-api/`](erp-clients-api/) that exposes Oracle data over HTTP

## Architecture

```
Browser ──► Laravel app ──► MySQL (vTiger)         core CRM data
                        ├─► Oracle (OCI8)           direct ERP/receipts
                        ├─► erp-clients-api (Flask) ──► Oracle   clients over HTTP
                        ├─► Microsoft Graph / IMAP / SMTP        email
                        ├─► Advanta API                          SMS
                        └─► Asterisk PBX (AMI / AGI / CDR)        calls
```

## Requirements

- PHP 8.1+ with `pdo_mysql`, `oci8` (for ERP), `imap`, `gd`, `mbstring`, `openssl`
- Composer 2.x and Node.js 18+ (npm)
- MySQL access to the vTiger database
- (Optional) Oracle access for ERP/receipts, Python 3 for `erp-clients-api`

## Installation

```bash
git clone https://github.com/kevinkimdroid/agile-crm-laravel.git
cd agile-crm-laravel

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Then edit `.env` with your database, mail, PBX, ERP, and SMS credentials (see `.env.example` for every supported key).

> This app connects to an **existing** vTiger schema, so it does not create the core CRM tables. Only run the bundled migrations (Laravel‑specific tables such as `user_departments`, ticket feedback, PDF templates) if your environment needs them:
>
> ```bash
> php artisan migrate
> ```

## Running locally

```bash
php artisan serve
```

Or run the full dev stack (server + queue + scheduler + Vite + ERP API) in one command:

```bash
composer dev
```

## Production optimization

Use the bundled script, which caches routes, views, and events and optimizes the autoloader:

```bat
optimize-for-production.bat
```

> ⚠️ **Do not run `php artisan config:cache`** (and avoid the `composer deploy`/`composer optimize` scripts that call it). This app reads several values with `env()` directly in application code — e.g. the PBX `PBX_AMI_*` settings — and `env()` returns `null` once the config is cached, which breaks those features. The `optimize-for-production.bat` script intentionally skips config caching.

To reverse all caching:

```bash
php artisan optimize:clear
```

## Project layout

| Path | Purpose |
| --- | --- |
| `app/Http/Controllers` | Feature controllers (dashboard, tickets, finance, PBX, reports…) |
| `app/Services` | Integrations & business logic (CRM, ERP, Mail, PBX, Receipts) |
| `erp-clients-api/` | Python Flask service exposing Oracle clients over HTTP |
| `resources/views` | Blade templates and UI |
| `deploy/` | systemd units, nginx config, CentOS setup |
| `docs/` | Setup and deployment guides |

## Security

- Secrets live in `.env` and are **not** committed (`.env`, credential JSON files, and keys are git‑ignored).
- This repository is public — never commit real credentials. Use `.env.example` as the template.

## License

Proprietary / internal. Built on the Laravel framework (MIT).
