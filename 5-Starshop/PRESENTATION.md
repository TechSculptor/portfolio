# Project presentation — Starshop (Symfony)

## In one sentence

A spaceship repair shop built with Symfony 8.1 — full Doctrine relations (ManyToOne, OneToMany,
ManyToMany with extra data), a CRUD administration panel, role-based authentication, and a live
integration with the public ISS position API.

## Why this project

Built by following three SymfonyCasts courses ("Doctrine, Symfony & the database", "Symfony,
Doctrine Relations & Warp Drive Basics" and "Symfony Forms: The Basics") to go beyond an isolated
tutorial: model realistic Doctrine relations rather than a simple single-entity CRUD, handle a real
form lifecycle with validation, plug in an external service, and set up proper authentication
instead of leaving an administration panel open to everyone.

## Tech stack

- **Symfony 8.1** / PHP 8.4+ — not an over-simplified framework, the real Symfony ecosystem
- **Doctrine ORM 3** + Migrations, Gedmo extensions (`slug`, `timestampable`)
- **Zenstruck Foundry** for factories and demo/test data
- **Pagerfanta** for pagination
- **Symfony Form** + **Validator** (constraints with named arguments, modern API)
- **Symfony Security** — `form_login`, `ROLE_USER`/`ROLE_ADMIN` roles, stateless CSRF
  (double submit through a Stimulus JS controller, no session needed), login throttling
  (`symfony/rate-limiter`) against brute force
- **HttpClient** + named cache pools (`framework.cache.pools` config) for the call to
  `api.wheretheiss.at`, with a custom Twig extension to expose it to the templates
- **Tailwind CSS v4** (`@plugin`, `@tailwindcss/forms`)
- **Docker Compose** — Postgres 16, Mailpit, Mercure
- **PHPUnit 13** + Foundry (`ResetDatabase`, `Factories`, `MockHttpClient`)
- **GitHub Actions CI** — code style (`php-cs-fixer`), Twig/YAML lint, migrations against a real
  Postgres service database, PHPUnit suite, on every change to this folder

### Commands used

```bash
docker compose up -d
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php bin/console app:user:create <email> --admin
symfony serve
php bin/phpunit
```

### Dependencies added through `composer require` (by feature)

> Rebuilt from `composer.json` rather than from a terminal history — but it reflects exactly
> what is actually installed.

```bash
# Application base (Symfony --webapp skeleton: Twig, Form, Validator, Mailer, Security,
# AssetMapper, Stimulus/UX Turbo, MakerBundle, Profiler...)
symfony new kozmodev_starshop --webapp

# Database & ORM
composer require doctrine

# Gedmo extensions (automatic slug, created/updated timestamps)
composer require stof/doctrine-extensions-bundle

# Pagination
composer require babdev/pagerfanta-bundle pagerfanta/doctrine-orm-adapter

# Demo/test data
composer require --dev doctrine/doctrine-fixtures-bundle zenstruck/foundry

# Authentication
composer require symfony/security-bundle

# Real time (Mercure — installed, not yet wired to a feature)
composer require mercure

# Styles
composer require symfonycasts/tailwind-bundle

# Tests
composer require --dev phpunit/phpunit symfony/browser-kit symfony/css-selector

# Code style / CI
composer require --dev php-cs-fixer/shim
```

## Features

1. **Starship catalogue** — pagination, sorting by number of assigned droids, detail page with
   linked parts and onboard droids.
2. **ManyToMany relation with extra data** — droids are assigned to a starship through an
   explicit join entity (`StarshipDroid`) that carries an assignment date (`assignedAt`), which is
   impossible with a native Doctrine ManyToMany.
3. **Parts catalogue** — search (name + notes), sorting by price, `Doctrine\Criteria` to filter
   the "expensive" parts directly on the already-loaded collection.
4. **CRUD admin panel** — create/edit/delete starships and parts, Symfony forms with validation
   (positive price, required fields), restricted to `ROLE_ADMIN`.
5. **Role-based authentication** — the whole site is behind a login; a read-only "demo" account
   (`ROLE_USER`) for anyone who wants to visit the site, and a separate admin account
   (`ROLE_ADMIN`) for management actions.
6. **Live external API integration** — ISS position displayed on the homepage, cached (TTL
   configurable through an environment variable) and tolerant to an outage of the external API
   (the app keeps working if `wheretheiss.at` is unreachable).
7. **Business console commands** — check-in of a starship, deletion (handling error cases such as
   an unknown slug), and a report (`app:ship-report`, filterable by status) that aggregates, in a
   single DQL query, the number of parts, the number of droids and the total value of the parts
   per starship.

## Architecture choices worth mentioning

- **Join entity rather than a native ManyToMany** as soon as a piece of data must live on the
  relation itself (`assignedAt`) — one of the classic Doctrine traps that many people discover
  late.
- **`fetch: EXTRA_LAZY` + `orphanRemoval`** on the starship → parts relation, to avoid loading
  the whole collection just for a `count()`, and to automatically delete orphan parts when a
  starship is deleted.
- **Explicit joins (DQL, `addSelect`)** in the repositories to avoid the N+1 trap instead of
  letting Doctrine lazy-load each relation when a list is displayed.
- **Cache logic moved into a Twig runtime extension** (`AppExtensionRuntime`) rather than into the
  controller — the controller only handles pagination/rendering, and the `get_iss_location_data()`
  function is reusable in any template.
- **Stateless CSRF** (double submit through a cookie + JS controller) rather than session-based —
  allows more aggressive HTTP caching of pages containing forms.
- **Clear demo / admin separation** on the same firewall through `access_control` ordered by
  specificity (`/admin` before the generic `/`) — a recruiter can browse without ever being able to
  modify or delete data by mistake.

*A real-world detail: when setting up the CI, the very first migration failed on a freshly cloned
empty database ("relation starship does not exist") — the very first tables had been created
through `schema:update` before migrations were used, so the migration history did not start from
zero. It was diagnosed by explicitly testing against an empty database rather than assuming it
would work, then fixed by regenerating a single "initial schema" migration with
`doctrine:migrations:diff` against a really empty database. Without the CI (and the reflex of testing
"from scratch"), this bug would have stayed invisible until the day someone clones the repository
for the first time.*

## Ideas for improvement — "what next?"

- Self-service user registration (accounts are currently created from the CLI)
- Publish the live demo (a `Dockerfile` and a Render blueprint are provided, see the README)
- Real-time notifications with Mercure (already in the Docker stack, not yet wired to a concrete
  feature)
- Move the secrets out of the committed `.env` into a real vault (Symfony `secrets:set`)

## Likely questions and answer ideas

- **"Why a join entity rather than a simple ManyToMany?"** — Because `assignedAt` has to be stored
  on the relation itself: a native Doctrine ManyToMany cannot carry its own data, so the pivot
  table has to be modelled explicitly as a full entity.

- **"How did you avoid N+1 queries on the starship list?"** — Explicit joins in the repository
  (DQL with `JOIN` + `addSelect`) rather than letting Doctrine lazy-load each relation when it is
  displayed.

- **"Why is the whole site behind a login?"** — A deliberate decision for this portfolio: a
  read-only demo account for visitors/recruiters, a separate admin account for management
  actions — exactly like access to a back-office would be restricted in production.

- **"How did you test this app?"** — PHPUnit + Foundry: unit tests on pure functions (the `ago`
  filter), functional tests on the controllers with a dedicated test database (never the dev data)
  and a `MockHttpClient` so that the tests never depend on the network or on the external API.

- **"How would you turn this into a real product?"** — The CI already exists; what is missing is
  the CD (automatic deployment to real hosting), a secrets vault, self-service registration, rate
  limiting on authentication, error monitoring (Sentry), and Mercure notifications wired to a
  real feature (e.g. live repair status).
