# [Controller/](../src/Controller) folder

Overview of the application's controllers. Each one extends Symfony's `AbstractController`, which provides helpers such as `render()`, `redirectToRoute()`, `createForm()`, `json()`, `addFlash()`, `getUser()`, `isCsrfTokenValid()`, etc.

## [MainController.php](../src/Controller/MainController.php)

Homepage (behind the login, like the rest of the site).

- **`GET /` (`app_homepage`)** — fetches the incomplete starships ordered by droid count (`findIncompleteOrderedByDroidCount()`), paginates the results (5 per page, current page read from `?page=`), and randomly picks a "ship of the moment" (`myShip`) among the results of the displayed page.

## [StarshipController.php](../src/Controller/StarshipController.php)

Detail page of a starship.

- **`GET /starships/{slug}` (`app_starship_show`)** — uses `#[MapEntity(mapping: ['slug' => 'slug'])]` so that Symfony automatically resolves the `Starship` entity from the `slug` URL parameter (instead of loading it manually through a repository). If no starship matches, Symfony automatically returns a 404.

## [PartController.php](../src/Controller/PartController.php)

Catalogue of spare parts.

- **`GET /parts` (`app_part_index`)** — displays a search form (`PartSearchType`) and the list of parts ordered by price (`findAllOrderedByPrice($query)`), filtered by the searched text when the form is submitted and valid.

## [StarshipApiController.php](../src/Controller/StarshipApiController.php)

Read-only JSON API, prefixed with `/api/starships`.

- **`GET /api/starships`** — returns all starships as JSON (`$this->json($starships)` serializes the entities directly).
- **`GET /api/starships/{id}`** (with the `<\d+>` constraint: the `id` must be numeric) — returns a starship by its id, or throws a 404 through `createNotFoundException()` if it does not exist.

## [SecurityController.php](../src/Controller/SecurityController.php)

Authentication (Symfony Security login form).

- **`/login` (`app_login`)** — if the user is already logged in, redirects to the homepage. Otherwise it retrieves the last authentication error and the last username entered (through `AuthenticationUtils`) to redisplay the form with that information.
- **`/logout` (`app_logout`)** — the method body is **never executed**: Symfony intercepts this route at the firewall level (see `config/packages/security.yaml`) before it reaches the controller. The `throw` is a safeguard in case the security configuration is ever wired incorrectly.

## [AdminController.php](../src/Controller/AdminController.php)

Back-office, prefixed with `/admin`.

- **`GET|POST /admin/starship-part/new` (`app_admin_starship_part_new`)** — form to create a part (`StarshipPartType`). If submitted and valid: persists the part, shows a success flash message, then redirects either to the same form (if the "create and add new" button was clicked, detected through `SubmitButton::isClicked()`) or to the parts list.

## [StarshipAdminController.php](../src/Controller/StarshipAdminController.php)

Full CRUD back-office for starships, prefixed with `/admin/starship`.

- **`GET /admin/starship` (`app_starship_admin_index`)** — lists all starships.
- **`GET|POST /admin/starship/new` (`app_starship_admin_new`)** — creation form (`StarshipType`); persists and redirects to the index if valid.
- **`GET /admin/starship/{id}` (`app_starship_admin_show`)** — detail page of a starship (automatic resolution by `id` thanks to the parameter name, without an explicit `#[MapEntity]` because Doctrine already maps `{id}` by convention).
- **`GET|POST /admin/starship/{id}/edit` (`app_starship_admin_edit`)** — form pre-filled with the existing entity; since the entity is already tracked by Doctrine (managed), a simple `flush()` is enough to save it (no need for `persist()`).
- **`POST /admin/starship/{id}` (`app_starship_admin_delete`)** — deletion protected by a CSRF token (`isCsrfTokenValid()`) sent in the request body (`_token`), so that a third-party site cannot trigger the deletion without the user's knowledge.

## Common points to remember

- **Dependency injection through arguments**: repositories and `EntityManagerInterface` are injected directly as parameters of each action method (not in a constructor); Symfony resolves them through autowiring on each request.
- **Forms (`Form` component)**: a repeated pattern — `createForm()` → `handleRequest($request)` → `isSubmitted() && isValid()` → persist/flush → redirect (Post/Redirect/Get pattern, which avoids re-submitting the form when the page is refreshed).
- **`#[Route(...)]`**: a PHP attribute that directly declares the URL, the route name and the allowed HTTP methods on the method (or on the class, as a shared prefix).
