# Starshop

Une boutique/atelier de réparation de vaisseaux spatiaux construite avec Symfony 8.1 — vaisseaux, pièces, droïdes, relations Doctrine complètes, authentification, et une intégration temps réel avec l'API de position de l'ISS.

## Stack technique

- **Symfony 8.1** / PHP 8.5
- **Doctrine ORM 3** + Migrations, extensions Gedmo (slug, timestampable)
- **Zenstruck Foundry** pour les factories et les fixtures
- **Pagerfanta** pour la pagination
- **Symfony Form** + **Validator**
- **Symfony Security** — `form_login`, CSRF stateless (double-soumission, contrôleur Stimulus JS)
- **HttpClient** + pools de cache nommés — intégration de l'API publique `wheretheiss.at`
- **Tailwind CSS v4**
- **Docker Compose** — Postgres 16, Mailpit, Mercure
- **PHPUnit 13** + Foundry (`ResetDatabase`, `Factories`) pour les tests

## Démarrer le projet

```bash
# 0. Configuration locale (les fichiers .env ne sont pas versionnés)
cp .env.example .env

# 1. Dépendances PHP
composer install

# 2. Services (Postgres, Mailpit, Mercure)
docker compose up -d

# 3. Schéma de base de données
php bin/console doctrine:migrations:migrate --no-interaction

# 4. Données de démonstration (vaisseaux, pièces, droïdes)
php bin/console doctrine:fixtures:load --no-interaction

# 5. Un compte pour se connecter (le site entier est derrière un login)
php bin/console app:user:create moi@example.com --admin

# 6. Styles Tailwind (une seule fois : télécharge un binaire de 130 Mo, d'où la limite mémoire levée)
php -d memory_limit=-1 bin/console tailwind:build

# 7. Lancer le serveur (laisser ce terminal ouvert ; sans Symfony CLI : php -S 127.0.0.1:8000 -t public)
symfony serve
```

Le site est ensuite accessible sur `http://127.0.0.1:8000`. Voir [TESTING.md](TESTING.md) pour
le détail des commandes de vérification manuelle.

## Tests automatisés

```bash
# Base de test dédiée (une seule fois)
symfony console --env=test doctrine:database:create --if-not-exists

php bin/phpunit
```

Les tests utilisent une base séparée et un client HTTP mocké pour l'appel à l'API ISS — la
suite ne dépend jamais du réseau ni des données de dev.

## Authentification

Le site entier nécessite d'être connecté (`config/packages/security.yaml`). Deux niveaux :

- `ROLE_USER` — consultation du catalogue de vaisseaux et de pièces
- `ROLE_ADMIN` — accès en plus à `/admin` (création/édition/suppression de vaisseaux et pièces)

```bash
# Compte de démonstration en lecture seule
php bin/console app:user:create demo@example.com

# Compte administrateur
php bin/console app:user:create admin@example.com --admin
```

Voir [PRESENTATION.md](PRESENTATION.md) pour une présentation plus détaillée du projet
(fonctionnalités, choix d'architecture, pistes d'amélioration).
