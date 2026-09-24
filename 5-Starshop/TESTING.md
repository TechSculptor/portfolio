# Testing guide — Starshop

Commands to manually verify all the implemented features (courses "Doctrine, Symfony 7 & the database" + "Symfony, Doctrine Relations & Warp Drive Basics").

## 0. Prerequisites

```bash
# Start the database and the mailer (Docker)
docker compose up -d

# Start the Symfony web server (keep this terminal open)
symfony serve

# Show the logs if needed
symfony server:log

# Server status
symfony server:status
```

## 1. Database & migrations

```bash
# Migration status (must be "Already at latest version")
symfony console doctrine:migrations:status

# Replay all the migrations from scratch (if needed)
symfony console doctrine:migrations:migrate --no-interaction

# Reload the fixtures (20 starships + 3 manual + 2 named + 100 starships with droids,
# 100 parts, 100 droids)
symfony console doctrine:fixtures:load --no-interaction
```

## 2. Direct SQL checks

```bash
# Count the starships (must be 125)
symfony console dbal:run-sql "SELECT count(*) FROM starship"

# Count the parts (must be 100)
symfony console dbal:run-sql "SELECT count(*) FROM starship_part"

# Count the droids (must be 100)
symfony console dbal:run-sql "SELECT count(*) FROM droid"

# Count the droid <-> starship assignments (~300+)
symfony console dbal:run-sql "SELECT count(*) FROM starship_droid"

# Show the schema of the join table (id, assigned_at, starship_id, droid_id)
symfony console dbal:run-sql "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'starship_droid'"

# List a few starships with their slug and status (useful for the following tests)
symfony console dbal:run-sql "SELECT slug, status FROM starship LIMIT 10"

# Check that no part has a null starship_id (NOT NULL constraint)
symfony console dbal:run-sql "SELECT count(*) FROM starship_part WHERE starship_id IS NULL"
```

## 3. Business console commands

```bash
# List the custom commands
symfony console list app

# Get the slug of a "completed" starship for the check-in
symfony console dbal:run-sql "SELECT slug FROM starship WHERE status = 'completed' LIMIT 1"

# Check-in: sets the status to "waiting" and updates arrivedAt (Starship::checkIn())
symfony console app:ship:check-in <slug-retrieved-above>

# Verify the change
symfony console dbal:run-sql "SELECT slug, status, arrived_at FROM starship WHERE slug = '<slug>'"

# Remove a starship (⚠️ irreversible)
symfony console app:ship:remove <slug>

# Error case: unknown slug → must display "Starship not found."
symfony console app:ship:check-in slug-that-does-not-exist
```

## 3bis. Authentication

The whole site is protected by a login (`config/packages/security.yaml`), so that a recruiter can
visit the site with a dedicated account instead of leaving it public.

```bash
# Create a standard user (randomly generated password, displayed only once)
symfony console app:user:create recruiter@starshop.dev

# Create a user with a chosen password
symfony console app:user:create someone@example.com --password="MyPassword!"

# Create an administrator (ROLE_ADMIN role, gives access to /admin)
symfony console app:user:create admin@starshop.dev --admin
```

Checks in the browser:

- [ ] Go to `http://127.0.0.1:8000/` while logged out → redirect to `/login`
- [ ] Log in with a wrong password → error message, no crash
- [ ] Log in with the credentials created above → redirect to the homepage
- [ ] The "Log out" link in the header logs out and redirects to `/login`
- [ ] A standard user gets a 403 on `/admin/starship`; an administrator gets the list
- [ ] After 5 wrong passwords for the same email, the 6th attempt shows "Too many failed login attempts" (blocked for 15 minutes, per username + IP address)

> The login form uses the same stateless CSRF system (JS-based, Stimulus `csrf-protection`
> controller) as the other forms of the site: it therefore cannot be tested with `curl` alone (the
> token keeps its "csrf-token" value until the JS has recomputed it), only in a real browser.

## 4. Web pages — HTTP codes

> ⚠️ The whole site requires being logged in (see section 3bis). Without an authenticated session,
> these requests redirect with a 302 to `/login` — this is the expected behaviour, not a
> regression. To test with a 200, log in first in a browser.

```bash
# Homepage: paginated list, sorted by ascending droid count
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/

# Next page (pagination)
curl -s -o /dev/null -w "%{http_code}\n" "http://127.0.0.1:8000/?page=2"

# Detail page of a starship (replace with a real slug)
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/starships/<slug>

# List of parts, sorted by descending price
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/parts

# Part search (name or notes, case-insensitive)
curl -s -o /dev/null -w "%{http_code}\n" "http://127.0.0.1:8000/parts?query=holodeck"
```

## 5. Visual checks (in the browser)

Open `http://127.0.0.1:8000/` and check:

- [ ] The starship list is displayed, sorted by ascending droid count (the first ones must show "Droids: none")
- [ ] Each card shows the starship name (a link to its detail page), a status badge matching its real status, "Parts: N" and "Droids: ..."
- [ ] The "Parts" link in the header leads to `/parts` (there is no "Contact" link)
- [ ] The pagination (Previous/Next) is displayed once, below the list, and works

Click on a starship name (`/starships/{slug}`) and check:

- [ ] Name, status badge, captain, class, arrival ("Not yet arrived" if none) and slug
- [ ] "Droids on board": each droid with its function and how long ago it was assigned (or "No droids on board.")
- [ ] "Parts (N)": every part with its price (or "No parts assigned yet.")
- [ ] "Expensive parts (N)": only the parts above 50,000 credits
- [ ] The "Edit" and "Delete" buttons are displayed for an administrator only (a standard user does not see them)

Go to `/parts` and check:

- [ ] The parts are sorted by descending price
- [ ] Each part displays `(assigned to <starship name>)`
- [ ] The search bar works on the name AND the notes (e.g. `holodeck`, `controls`)
- [ ] The search field keeps its value after submission

Log in as an administrator and open `/admin/starship`:

- [ ] The table header is readable (light text on a dark background)

## 6. Quick regression tests (all in one)

```bash
# Full reset + check of the main pages
symfony console doctrine:fixtures:load --no-interaction && \
curl -s -o /dev/null -w "homepage: %{http_code}\n" http://127.0.0.1:8000/ && \
curl -s -o /dev/null -w "parts: %{http_code}\n" http://127.0.0.1:8000/parts && \
SLUG=$(symfony console dbal:run-sql "SELECT slug FROM starship LIMIT 1" | grep -oE '[a-z0-9-]+-[0-9]+|[a-z-]+' | tail -1) && \
curl -s -o /dev/null -w "show: %{http_code}\n" "http://127.0.0.1:8000/starships/$SLUG"
```

## 7. Automated tests

The project has a real PHPUnit suite (`phpunit/phpunit` + `symfony/browser-kit` +
`zenstruck/foundry` for the test data), configured in `phpunit.dist.xml`.

```bash
# Create the dedicated test database and its schema (only once, or after a credentials change)
php bin/console --env=test doctrine:database:create --if-not-exists
php bin/console --env=test doctrine:migrations:migrate --no-interaction

# Run the whole suite
php bin/phpunit

# Run a single file
php bin/phpunit tests/Controller/MainControllerTest.php

# Run with the detail of the PHPUnit notices/deprecations
php bin/phpunit --display-phpunit-notices
```

Important points about the current configuration:

- The tests use a **separate** database (`app_test`, automatically suffixed by Symfony through
  `dbname_suffix` in `config/packages/doctrine.yaml`) built from the `DATABASE_URL` of your `.env` —
  the tests never touch the 125 starships of the dev database.
- `KERNEL_CLASS` is set in `phpunit.dist.xml`, so no `.env.test` file is needed.
- The `WebTestCase` tests use the Foundry `Factories` + `ResetDatabase` traits, which recreate the
  schema and empty the tables between each test automatically.
- The real call to the ISS API (`api.wheretheiss.at`) is replaced in the test environment by a
  `MockHttpClient` (see `tests/Support/TestHttpClientFactory.php`, wired in
  `config/services.yaml` under `when@test`) — the suite never depends on the network.
- To log in within a test, use `$client->loginUser(UserFactory::createOne())` rather than
  submitting the real `/login` form: it uses a stateless CSRF based on a Stimulus JS controller,
  which cannot run in a PHP test (same limitation as for `curl`, see section 3bis).

Current files:

- `tests/Twig/Runtime/AppExtensionRuntimeTest.php` — pure unit test of the `ago` filter
- `tests/Controller/SecurityControllerTest.php` — anonymous redirect, rendering of the login form, access to the admin area, login throttling
- `tests/Controller/MainControllerTest.php` — authenticated homepage + mocked ISS display, clickable ship names, single pagination block
- `tests/Controller/StarshipControllerTest.php` — detail page (droids, parts, expensive parts, empty states), admin-only buttons, 404
- `tests/Controller/StarshipApiControllerTest.php` — JSON API (collection, by id, 404, anonymous redirect)
- `tests/Command/ShipReportCommandTest.php` — the `app:ship-report` command

### Official documentation

- Symfony — [Testing](https://symfony.com/doc/current/testing.html) (PHPUnit basics),
  [Testing with a Database](https://symfony.com/doc/current/testing/database.html) (DAMA/Foundry
  patterns to isolate the database between tests)
- Foundry — [Testing with Foundry](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#testing)
  (`ResetDatabase`, `Factories`, fake data)
- PHPUnit — [official manual](https://docs.phpunit.de/en/12.3/)
- SymfonyCasts also has a dedicated course: [PHPUnit & Symfony: Testing Your App](https://symfonycasts.com/screencast/phpunit)

## 8. What must be true in the database after a `fixtures:load`

| Table            | Expected number of rows |
|------------------|--------------------------|
| `starship`       | 125                       |
| `starship_part`  | 100                       |
| `droid`          | 100                       |
| `starship_droid` | variable (between 100 and 500, 1 to 5 per new starship) |
