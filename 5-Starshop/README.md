# Starshop

A spaceship repair shop built with Symfony 8.1 — starships, parts, droids, full Doctrine relations, authentication, and a live integration with the ISS position API.

## Tech stack

- **Symfony 8.1** / PHP 8.4+
- **Doctrine ORM 3** + Migrations, Gedmo extensions (slug, timestampable)
- **Zenstruck Foundry** for factories and fixtures
- **Pagerfanta** for pagination
- **Symfony Form** + **Validator**
- **Symfony Security** — `form_login`, stateless CSRF (double submit, Stimulus JS controller)
- **HttpClient** + named cache pools — integration of the public `wheretheiss.at` API
- **Tailwind CSS v4**
- **Docker Compose** — Postgres 16, Mailpit, Mercure
- **PHPUnit 13** + Foundry (`ResetDatabase`, `Factories`) for the tests

## Getting started

```bash
# 0. Local configuration (.env files are not versioned)
cp .env.example .env

# 1. PHP dependencies
composer install

# 2. Services (Postgres, Mailpit, Mercure)
docker compose up -d

# 3. Database schema
php bin/console doctrine:migrations:migrate --no-interaction

# 4. Demo data (starships, parts, droids)
php bin/console doctrine:fixtures:load --no-interaction

# 5. An account to log in with (the whole site is behind a login)
php bin/console app:user:create me@example.com --admin

# 6. Tailwind styles (only once: it downloads a 130 MB binary, hence the raised memory limit)
php -d memory_limit=-1 bin/console tailwind:build

# 7. Start the server (keep this terminal open; without the Symfony CLI: php -S 127.0.0.1:8000 -t public)
symfony serve
```

The site is then available at `http://127.0.0.1:8000`. See [TESTING.md](TESTING.md) for the
manual verification commands.

On Windows, `symfony serve -d` (background mode) can fail with "The process cannot access the file
because it is being used by another process": run `symfony serve` in a dedicated terminal instead.

## Automated tests

```bash
# Dedicated test database (only once)
php bin/console --env=test doctrine:database:create --if-not-exists
php bin/console --env=test doctrine:migrations:migrate --no-interaction

php bin/phpunit
```

The tests use a separate database and a mocked HTTP client for the ISS API call — the suite never
depends on the network or on the dev data.

The GitHub Actions workflow (`.github/workflows/starshop-ci.yaml` at the root of the repository) runs the
coding-style check, the Twig/YAML linters and the test suite on every change to this folder.

## Authentication

The whole site requires a login (`config/packages/security.yaml`). Two levels:

- `ROLE_USER` — browse the starship and part catalogues
- `ROLE_ADMIN` — additionally access `/admin` (create/edit/delete starships and parts)

Login attempts are throttled: after 5 failed attempts (per username and IP address), the login is blocked for 15 minutes.

```bash
# Read-only demo account
php bin/console app:user:create demo@example.com

# Administrator account
php bin/console app:user:create admin@example.com --admin
```

## Documentation

- [PRESENTATION.md](PRESENTATION.md) — a more detailed presentation of the project (features, architecture choices, ideas for improvement)
- [TESTING.md](TESTING.md) — manual verification commands
- [docs/controllers.md](docs/controllers.md) — the controllers and their routes
- [docs/forms.md](docs/forms.md) — the form types
- [docs/factories.md](docs/factories.md) — the Foundry factories and the fixtures
- [docs/twig-runtime.md](docs/twig-runtime.md) — the custom Twig filter and function (`ago`, `get_iss_location_data`)
- [docs/create-user-command-imports.md](docs/create-user-command-imports.md) — what each import of the `app:user:create` command is for

## Deploying a demo on Render

The repository root contains a [`render.yaml`](../render.yaml) blueprint and this folder a `Dockerfile`:

1. On [render.com](https://render.com): **New → Blueprint**, connect the GitHub repository and select it.
2. Render reads `render.yaml` and proposes a web service (`starshop`) and a PostgreSQL database (`starshop-db`), both on the free plan.
3. Enter a value for `ADMIN_PASSWORD` when prompted (it is never stored in the repository), then **Apply**.
4. Wait for the first build (a few minutes). The site is then available at `https://starshop-<id>.onrender.com`.
5. Log in with the read-only demo account `demo@starshop.dev` / `starshop-demo`, or with `admin@starshop.dev` and the password chosen in step 3.

Demo data is recreated every time the service starts. On the free plan the service goes to sleep after
15 minutes without traffic (about one minute to wake up) and the free database expires after 30 days.
