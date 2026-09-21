# [AppExtensionRuntime.php](../src/Twig/Runtime/AppExtensionRuntime.php)

Adds **custom Twig functions and filters**, usable directly in the `.html.twig` templates, in addition to Twig's built-in filters/functions (`|upper`, `|date`, `path()`...).

## How it is wired

Twig separates the **declaration** from the **logic** in 2 classes:

- **[AppExtension.php](../src/Twig/AppExtension.php)** — the declaration: tells Twig "there is an `ago` filter and a `get_iss_location_data` function", and points to the method that implements it.
  ```php
  new TwigFilter('ago', [AppExtensionRuntime::class, 'ago']);
  new TwigFunction('get_iss_location_data', [AppExtensionRuntime::class, 'getIssLocationData']);
  ```
- **`AppExtensionRuntime` (this file)** — the actual implementation, in a separate class that implements `RuntimeExtensionInterface`. Twig instantiates it **only if the filter/function is actually used** in a template — useful here because this class has "heavy" dependencies (HTTP client, cache) that should not be built on every page render if they are not needed.

## The 2 injected dependencies

- **`HttpClientInterface $client`** — Symfony's HTTP client, to call an external API.
- **`CacheInterface $issLocationPool`** — a **dedicated** cache pool (not the default cache), defined in [config/packages/cache.yaml](../config/packages/cache.yaml):
  ```yaml
  cache:
      pools:
          iss_location_pool:
              default_lifetime: '%iss_location_cache_ttl%'
  ```
  The `#[Autowire(service: 'iss_location_pool')]` attribute forces Symfony to inject **this specific pool** rather than the application's default `CacheInterface` (otherwise autowiring would not know which one to choose, since several services implement the same interface).

## `getIssLocationData(): ?array`

Fetches the current position of the International Space Station (ISS) through the public `wheretheiss.at` API, and **caches the result**:

- `$this->issLocationPool->get('iss_location_data', function (ItemInterface $item) { ... })` — the "cache with callback" pattern: if the `iss_location_data` key already exists in the cache and has not expired (lifetime defined by `iss_location_cache_ttl`), the cached value is returned directly, **without calling the API**. Otherwise the callback runs, makes the HTTP call, and its result is automatically stored in the cache for next time.
- Why cache: to avoid spamming the external API on every page display (the ISS position does not change fast enough to justify a call on every request).
- On a network error (`HttpClientExceptionInterface`), it returns `null` instead of crashing the page — used on the template side to display a fallback state.
- Exposed to the template through the Twig function `get_iss_location_data()` (see [templates/main/homepage.html.twig](../templates/main/homepage.html.twig)).

## `ago(?\DateTimeInterface $date): string`

Converts a date into human-readable relative text ("3 days ago", "in 2 hours"), a bit like `moment.js`/`date-fns` on the JS side:

- If `$date` is `null` → `'Not yet arrived'` (probable use case: a starship/droid not yet delivered).
- Computes the difference in seconds with the present moment (`\DateTimeImmutable`), detects whether it is in the past or the future (`$future`), then takes the absolute value.
- Walks through a table of units (year → second) **from the largest to the smallest**, and stops as soon as a unit fits at least once in the difference (e.g. 400,000 seconds ≈ 4 days → stops on "day", not "hour" or "second").
- Handles the plural (`'day' . ($count > 1 ? 's' : '')`) and the past/future wording (`"%s ago"` vs `"in %s"`).
- Exposed to the template through the Twig filter `|ago`, e.g. `{{ starship.createdAt|ago }}`.
