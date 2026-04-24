# Rainwaves Starter

Secure Laravel 13 + Vue starter template for Rainwaves apps, with auth, roles, media, and PayFast integration.

## Purpose

This repository is the clean internal baseline for new Rainwaves applications.
It exists to replace the older template-driven starter with a stack we own end to end.

Core direction:

- Laravel 13 backend
- Vue 3 SPA frontend
- Vuetify UI baseline
- Pinia state management
- Sail-first local development
- auth, authorization, auditability, media, and PayFast treated as core concerns

Security is the primary constraint.
If scope needs to shrink, visual ambition and optional tooling are cut before auth, permissions, payments, or auditability.

## Current Status

Implemented so far:

- fresh Laravel 13 scaffold
- Sail with MySQL, Redis, Mailpit, and MinIO
- Vue 3 + Vue Router + Pinia + Vuetify SPA shell
- MinIO bucket bootstrap for local public image access
- Node 20.19.0 pinned for the frontend toolchain

Not implemented yet:

- `rainwaves/lara-auth-suite`
- `rainwaves/payfast-payment`
- permissions/admin baseline
- profile/media flows
- production deployment docs

## Local Requirements

- PHP `8.4+`
- Composer `2.x`
- Docker / Docker Compose
- Node `20.19.0+`

This repository includes [.node-version](/home/eclaims/htdocs/rainwaves-starter/.node-version:1) with the pinned Node version.

On this machine, Node 20 was installed user-locally under `/home/eclaims/.local`.
For local frontend commands in the current shell:

```bash
export PATH=/home/eclaims/.local/bin:$PATH
```

## Local Setup

1. Install backend dependencies:

```bash
composer install
```

2. Create the local env file if needed:

```bash
cp .env.example .env
php artisan key:generate
```

3. Install frontend dependencies with Node 20:

```bash
export PATH=/home/eclaims/.local/bin:$PATH
npm install
```

4. Start Sail:

```bash
./vendor/bin/sail up -d
```

5. Run migrations inside Sail:

```bash
./vendor/bin/sail artisan migrate
```

6. Start the frontend dev server with Node 20:

```bash
export PATH=/home/eclaims/.local/bin:$PATH
npm run dev
```

## Key Local Services

- app: `http://localhost`
- Vite: `http://localhost:5173`
- Mailpit UI: `http://localhost:8025`
- MinIO API: `http://localhost:9000`
- MinIO console: `http://localhost:8900`

## MinIO Notes

Local media is configured to use the S3 disk by default.

The Sail stack includes a `minio-create-bucket` sidecar that:

- creates the configured bucket
- enables anonymous download on that bucket
- applies permissive local CORS

This is intentional for local image retrieval and frontend media testing.
Do not carry those anonymous-read assumptions into production infrastructure.

## Verification

Backend checks:

```bash
php artisan route:list
php artisan test
```

Frontend build check:

```bash
export PATH=/home/eclaims/.local/bin:$PATH
npm run build
```

## Working Agreement

Implementation is being done in small committed phases.
The current rule is:

- finish a coherent slice
- verify it
- commit it
- move to the next phase
