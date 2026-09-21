# Dossier [Controller/](.)

Vue d'ensemble des contrôleurs de l'application. Chacun étend `AbstractController` (Symfony), qui fournit des helpers comme `render()`, `redirectToRoute()`, `createForm()`, `json()`, `addFlash()`, `getUser()`, `isCsrfTokenValid()`, etc.

## [MainController.php](MainController.php)

Page d'accueil publique.

- **`GET /` (`app_homepage`)** — récupère les vaisseaux incomplets triés par nombre de droïdes (`findIncompleteOrderedByDroidCount()`), pagine les résultats (5 par page, page courante lue dans `?page=`), et choisit aléatoirement un "vaisseau du moment" (`myShip`) parmi les résultats de la page affichée.

## [StarshipController.php](StarshipController.php)

Fiche publique d'un vaisseau.

- **`GET /starships/{slug}` (`app_starship_show`)** — utilise `#[MapEntity(mapping: ['slug' => 'slug'])]` pour que Symfony résolve automatiquement l'entité `Starship` à partir du paramètre d'URL `slug` (au lieu de charger manuellement via un repository). Si aucun vaisseau ne correspond, Symfony renvoie une 404 automatiquement.

## [PartController.php](PartController.php)

Catalogue public des pièces détachées.

- **`GET /parts` (`app_part_index`)** — affiche un formulaire de recherche (`PartSearchType`) et la liste des pièces triées par prix (`findAllOrderedByPrice($query)`), filtrée par le texte recherché si le formulaire est soumis et valide.

## [StarshipApiController.php](StarshipApiController.php)

API JSON en lecture seule, préfixée par `/api/starships`.

- **`GET /api/starships`** — retourne toutes les vaisseaux au format JSON (`$this->json($starships)` sérialise l'entité directement).
- **`GET /api/starships/{id}`** (avec contrainte `<\d+>` : l'`id` doit être numérique) — retourne un vaisseau par son id, ou lève une 404 via `createNotFoundException()` s'il n'existe pas.

## [SecurityController.php](SecurityController.php)

Authentification (formulaire de login Symfony Security).

- **`/login` (`app_login`)** — si l'utilisateur est déjà connecté, redirige vers l'accueil. Sinon récupère la dernière erreur d'authentification et le dernier nom d'utilisateur saisi (via `AuthenticationUtils`) pour réafficher le formulaire avec ces infos.
- **`/logout` (`app_logout`)** — le corps de la méthode n'est **jamais exécuté** : Symfony intercepte cette route au niveau du firewall (voir `config/packages/security.yaml`) avant qu'elle n'atteigne le contrôleur. Le `throw` sert de garde-fou si jamais la config de sécurité était mal branchée.

## [AdminController.php](AdminController.php)

Back-office, préfixé par `/admin`.

- **`GET|POST /admin/starship-part/new` (`app_admin_starship_part_new`)** — formulaire de création d'une pièce (`StarshipPartType`). Si soumis et valide : persiste la pièce, affiche un message flash de succès, puis redirige soit vers le même formulaire (si le bouton "créer et ajouter une nouvelle" a été cliqué — détecté via `SubmitButton::isClicked()`), soit vers la liste des pièces.

## [StarshipAdminController.php](StarshipAdminController.php)

Back-office CRUD complet pour les vaisseaux, préfixé par `/admin/starship`.

- **`GET /admin/starship` (`app_starship_admin_index`)** — liste tous les vaisseaux.
- **`GET|POST /admin/starship/new` (`app_starship_admin_new`)** — formulaire de création (`StarshipType`), persiste et redirige vers l'index si valide.
- **`GET /admin/starship/{id}` (`app_starship_admin_show`)** — fiche détail d'un vaisseau (résolution automatique par `id` via le nom du paramètre, sans `#[MapEntity]` explicite car Doctrine sait déjà mapper `{id}` par convention).
- **`GET|POST /admin/starship/{id}/edit` (`app_starship_admin_edit`)** — formulaire pré-rempli avec l'entité existante ; comme l'entité est déjà suivie par Doctrine (managée), un simple `flush()` suffit pour sauvegarder (pas besoin de `persist()`).
- **`POST /admin/starship/{id}` (`app_starship_admin_delete`)** — suppression protégée par un jeton CSRF (`isCsrfTokenValid()`) envoyé dans le corps de la requête (`_token`), pour éviter qu'un site tiers ne déclenche la suppression à l'insu de l'utilisateur.

## Points communs à retenir

- **Injection de dépendances par argument** : les repositories/`EntityManagerInterface` sont injectés directement en paramètre de chaque méthode d'action (pas dans un constructeur), Symfony les résout via l'autowiring à chaque requête.
- **Formulaires (`Form` component)** : pattern répété — `createForm()` → `handleRequest($request)` → `isSubmitted() && isValid()` → persist/flush → redirect (pattern Post/Redirect/Get, évite la re-soumission du formulaire au rafraîchissement de la page).
- **`#[Route(...)]`** : attribut PHP qui déclare directement l'URL, le nom de route et les méthodes HTTP autorisées sur la méthode (ou la classe, comme préfixe commun).
