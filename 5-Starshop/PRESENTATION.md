# Présentation du projet — Starshop (Symfony)

## En une phrase

Une boutique/atelier de réparation de vaisseaux spatiaux en Symfony 8.1 — relations Doctrine
complètes (ManyToOne, OneToMany, ManyToMany avec données supplémentaires), panel d'administration
CRUD, authentification par rôles, et une intégration temps réel avec l'API publique de position
de l'ISS.

## Pourquoi ce projet

Construit en suivant trois cours SymfonyCasts ("Doctrine, Symfony & la database", "Symfony,
Doctrine Relations & Warp Drive Basics" et "Symfony Forms: The Basics") pour aller au-delà du
tutoriel isolé : modéliser des relations Doctrine réalistes plutôt qu'un simple CRUD à une seule
entité, gérer un vrai cycle de formulaires avec validation, brancher un service externe, et poser
une authentification correcte plutôt que de laisser un panel d'administration ouvert à tous.

## Stack technique

- **Symfony 8.1** / PHP 8.5 — pas de framework "trop simplifié", le vrai écosystème Symfony
- **Doctrine ORM 3** + Migrations, extensions Gedmo (`slug`, `timestampable`)
- **Zenstruck Foundry** pour les factories et les données de démo/test
- **Pagerfanta** pour la pagination
- **Symfony Form** + **Validator** (contraintes avec arguments nommés, API moderne)
- **Symfony Security** — `form_login`, rôles `ROLE_USER`/`ROLE_ADMIN`, CSRF stateless
  (double-soumission via un contrôleur Stimulus JS, pas de session nécessaire)
- **HttpClient** + pools de cache nommés (config `framework.cache.pools`) pour l'appel à
  `api.wheretheiss.at`, avec extension Twig custom pour l'exposer aux templates
- **Tailwind CSS v4** (`@plugin`, `@tailwindcss/forms`)
- **Docker Compose** — Postgres 16, Mailpit, Mercure
- **PHPUnit 13** + Foundry (`ResetDatabase`, `Factories`, `MockHttpClient`)
- **CI GitHub Actions** — style (`php-cs-fixer`), lint Twig/YAML, migrations sur une vraie base
  Postgres de service, suite PHPUnit, à chaque push

### Commandes utilisées

```bash
docker compose up -d
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php bin/console app:user:create <email> --admin
symfony serve -d
php bin/phpunit
```

### Dépendances ajoutées via `composer require` (par fonctionnalité)

> Reconstruit à partir de `composer.json` plutôt que d'un historique de terminal — mais reflète
> exactement ce qui est réellement installé.

```bash
# Socle applicatif (squelette Symfony --webapp : Twig, Form, Validator, Mailer, Security,
# AssetMapper, Stimulus/UX Turbo, MakerBundle, Profiler...)
symfony new kozmodev_starshop --webapp

# Base de données & ORM
composer require doctrine

# Extensions Gedmo (slug automatique, timestamps created/updated)
composer require stof/doctrine-extensions-bundle

# Pagination
composer require babdev/pagerfanta-bundle pagerfanta/doctrine-orm-adapter

# Données de démo/test
composer require --dev doctrine/doctrine-fixtures-bundle zenstruck/foundry

# Authentification
composer require symfony/security-bundle

# Temps réel (Mercure — installé, pas encore branché à une fonctionnalité)
composer require mercure

# Styles
composer require symfonycasts/tailwind-bundle

# Tests
composer require --dev phpunit/phpunit symfony/browser-kit symfony/css-selector

# Style de code / CI
composer require --dev php-cs-fixer/shim
```

## Fonctionnalités

1. **Catalogue de vaisseaux** — pagination, tri par nombre de droïdes assignés, page de détail
   avec pièces liées et droïdes embarqués.
2. **Relation ManyToMany avec données supplémentaires** — les droïdes sont assignés à un
   vaisseau via une entité de jointure explicite (`StarshipDroid`) qui porte une date
   d'assignation (`assignedAt`), impossible à faire avec une ManyToMany native Doctrine.
3. **Catalogue de pièces** — recherche (nom + notes), tri par prix, `Doctrine\Criteria` pour
   filtrer les pièces "chères" directement sur la collection déjà chargée.
4. **Panel admin CRUD** — création/édition/suppression de vaisseaux et de pièces, formulaires
   Symfony avec validation (prix positif, champs requis), réservé aux `ROLE_ADMIN`.
5. **Authentification par rôles** — site entier derrière un login ; un compte "démo" en lecture
   seule (`ROLE_USER`) pour qui veut visiter le site, un compte admin séparé (`ROLE_ADMIN`) pour
   les actions de gestion.
6. **Intégration API externe temps réel** — position de l'ISS affichée sur l'accueil, mise en
   cache (TTL configurable via variable d'env) et tolérante à une panne de l'API externe (l'app
   continue de fonctionner si `wheretheiss.at` est injoignable).
7. **Commandes console métier** — check-in d'un vaisseau, suppression (avec gestion des cas
   d'erreur type slug inexistant), et un rapport (`app:ship-report`, filtrable par statut) qui
   agrège en une seule requête DQL le nombre de pièces, de droïdes et la valeur totale des
   pièces par vaisseau.

## Choix d'architecture à mentionner

- **Entité de jointure plutôt que ManyToMany native** dès qu'une donnée doit vivre sur la
  relation elle-même (`assignedAt`) — un des pièges classiques de Doctrine que beaucoup
  découvrent tard.
- **`fetch: EXTRA_LAZY` + `orphanRemoval`** sur la relation vaisseau → pièces, pour éviter de
  charger toute la collection juste pour un `count()`, et supprimer les pièces orphelines
  automatiquement quand un vaisseau est supprimé.
- **Jointures explicites (DQL, `addSelect`)** dans les repositories pour éviter le piège du N+1
  plutôt que de laisser Doctrine lazy-load chaque relation à l'affichage d'une liste.
- **Logique de cache déplacée dans une extension Twig runtime** (`AppExtensionRuntime`) plutôt
  que dans le contrôleur — le contrôleur ne s'occupe plus que de la pagination/rendu, la
  fonction `get_iss_location_data()` est réutilisable dans n'importe quel template.
- **CSRF stateless** (double-soumission via cookie + contrôleur JS) plutôt que basé sur la
  session — permet un cache HTTP plus agressif des pages contenant des formulaires.
- **Séparation nette démo / admin** sur le même firewall via `access_control` ordonné par
  spécificité (`/admin` avant le `/` générique) — un recruteur peut naviguer sans jamais pouvoir
  modifier ou supprimer une donnée par erreur.

*Détail "vécu" : en mettant en place la CI, la toute première migration
échouait sur une base neuve fraîchement clonée ("relation starship does not exist") — les toutes
premières tables avaient été créées via `schema:update` avant que les migrations ne soient
utilisées, donc l'historique de migrations ne partait pas de zéro. Diagnostiqué en testant
explicitement contre une base vide plutôt qu'en supposant que ça marcherait, puis corrigé en
régénérant un unique migration "schéma initial" avec `doctrine:migrations:diff` contre une base
vraiment vide. Sans la CI (et le réflexe de tester "from scratch"), ce bug serait resté invisible
jusqu'au jour où quelqu'un clone le repo pour la première fois.*

## Pistes d'amélioration — "et après ?"

- Inscription utilisateur self-service (actuellement les comptes sont créés en CLI)
- Rate limiting sur `/login` (`symfony/rate-limiter`) contre le bruteforce
- Vrai hébergement (Platform.sh / VPS) plutôt que local uniquement
- Notifications temps réel avec Mercure (déjà dans le stack Docker, pas encore branché à une
  fonctionnalité concrète)
- Déplacer les secrets hors du `.env` commité vers un vrai vault (`secrets:set` Symfony)

## Questions probables et pistes de réponse

- **"Pourquoi une entité de jointure plutôt qu'une ManyToMany simple ?"** — Parce qu'il faut
  stocker `assignedAt` sur la relation elle-même : une ManyToMany native de Doctrine ne peut pas
  porter de données propres, il faut modéliser explicitement la table pivot comme une entité à
  part entière.

- **"Comment tu as évité les requêtes N+1 sur la liste des vaisseaux ?"** — Jointures explicites
  dans le repository (DQL avec `JOIN` + `addSelect`) plutôt que de laisser Doctrine lazy-load
  chaque relation au moment de l'affichage.

- **"Pourquoi tout le site est-il derrière un login ?"** — Décision volontaire pour ce
  portfolio : un compte démo en lecture seule pour les visiteurs/recruteurs, un compte admin
  séparé pour les actions de gestion — exactement comme on limiterait l'accès à un back-office
  en production.

- **"Comment tu as testé cette app ?"** — PHPUnit + Foundry : tests unitaires sur les fonctions
  pures (filtre `ago`), tests fonctionnels sur les contrôleurs avec une base de test dédiée
  (jamais les données de dev) et un `MockHttpClient` pour ne jamais dépendre du réseau ou de
  l'API externe pendant les tests.

- **"Comment tu passerais ça en vrai produit ?"** — La CI existe déjà ; il manque le CD
  (déploiement automatique vers un vrai hébergement), un vault de secrets, l'inscription
  self-service, le rate limiting sur l'authentification, du monitoring des erreurs (Sentry), et
  les notifications Mercure branchées à une vraie fonctionnalité (ex: statut de réparation en
  direct).
