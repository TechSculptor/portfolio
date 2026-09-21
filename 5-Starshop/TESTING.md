# Guide de test — Starshop

Commandes pour vérifier manuellement toutes les fonctionnalités mises en place (cours "Doctrine, Symfony 7 & la database" + "Symfony, Doctrine Relations & Warp Drive Basics").

## 0. Prérequis

```bash
# Démarrer la base de données et le mailer (Docker)
docker compose up -d

# Démarrer le serveur web Symfony (en arrière-plan)
symfony serve -d

# Voir les logs si besoin
symfony server:log

# Statut du serveur
symfony server:status
```

## 1. Base de données & migrations

```bash
# Statut des migrations (doit être "Already at latest version")
symfony console doctrine:migrations:status

# Rejouer toutes les migrations depuis zéro (si besoin)
symfony console doctrine:migrations:migrate --no-interaction

# Recharger les fixtures (20 vaisseaux + 3 manuels + 2 nommés + 100 vaisseaux avec droïdes,
# 100 pièces, 100 droïdes)
symfony console doctrine:fixtures:load --no-interaction
```

## 2. Vérifications SQL directes

```bash
# Compter les vaisseaux (doit faire 125)
symfony console dbal:run-sql "SELECT count(*) FROM starship"

# Compter les pièces (doit faire 100)
symfony console dbal:run-sql "SELECT count(*) FROM starship_part"

# Compter les droïdes (doit faire 100)
symfony console dbal:run-sql "SELECT count(*) FROM droid"

# Compter les assignations droïde <-> vaisseau (~300+)
symfony console dbal:run-sql "SELECT count(*) FROM starship_droid"

# Voir le schéma de la table de jointure (id, assigned_at, starship_id, droid_id)
symfony console dbal:run-sql "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'starship_droid'"

# Lister quelques vaisseaux avec leur slug et statut (utile pour les tests suivants)
symfony console dbal:run-sql "SELECT slug, status FROM starship LIMIT 10"

# Vérifier qu'aucune pièce n'a de starship_id null (contrainte NOT NULL)
symfony console dbal:run-sql "SELECT count(*) FROM starship_part WHERE starship_id IS NULL"
```

## 3. Commandes console métier

```bash
# Lister les commandes custom
symfony console list app

# Récupérer un slug de vaisseau "completed" pour le check-in
symfony console dbal:run-sql "SELECT slug FROM starship WHERE status = 'completed' LIMIT 1"

# Check-in : passe le statut à "waiting" et met à jour arrivedAt (Starship::checkIn())
symfony console app:ship:check-in <slug-recuperé-ci-dessus>

# Vérifier le changement
symfony console dbal:run-sql "SELECT slug, status, arrived_at FROM starship WHERE slug = '<slug>'"

# Suppression d'un vaisseau (⚠️ irréversible)
symfony console app:ship:remove <slug>

# Cas d'erreur : slug inexistant → doit afficher "Starship not found."
symfony console app:ship:check-in slug-qui-nexiste-pas
```

## 3bis. Authentification

Le site entier est protégé par un login (`config/packages/security.yaml`), pour permettre à un
recruteur de visiter le site avec un compte dédié plutôt que de le laisser public.

```bash
# Créer un utilisateur standard (mot de passe généré aléatoirement et affiché une seule fois)
symfony console app:user:create recruteur@starshop.dev

# Créer un utilisateur avec un mot de passe imposé
symfony console app:user:create quelquun@example.com --password="MonMotDePasse!"

# Créer un futur administrateur (rôle ROLE_ADMIN, pas encore utilisé par une access_control dédiée)
symfony console app:user:create admin@starshop.dev --admin
```

Vérifications dans le navigateur :

- [ ] Aller sur `http://127.0.0.1:8000/` sans être connecté → redirection vers `/login`
- [ ] Se connecter avec un mauvais mot de passe → message d'erreur, pas de crash
- [ ] Se connecter avec les identifiants créés ci-dessus → redirection vers la homepage
- [ ] Le lien "Log out" dans le header déconnecte et renvoie vers `/login`

> Le formulaire de login utilise le même système de CSRF stateless (basé sur JS, contrôleur
> Stimulus `csrf-protection`) que les autres formulaires du site : il n'est donc pas testable
> via `curl` seul (le token reste à sa valeur "csrf-token" tant que le JS ne l'a pas recalculé),
> uniquement dans un vrai navigateur.

## 4. Pages web — codes HTTP

> ⚠️ Le site entier requiert désormais d'être connecté (voir section 3bis). Sans session
> authentifiée, ces requêtes redirigent en 302 vers `/login` — c'est le comportement attendu,
> pas une régression. Pour tester en 200, connecte-toi d'abord dans un navigateur.

```bash
# Homepage : liste paginée, triée par nombre de droïdes croissant
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/

# Page suivante (pagination)
curl -s -o /dev/null -w "%{http_code}\n" "http://127.0.0.1:8000/?page=2"

# Page détail d'un vaisseau (remplacer par un vrai slug)
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/starships/<slug>

# Liste des pièces, triée par prix décroissant
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/parts

# Recherche de pièces (nom ou notes, insensible à la casse)
curl -s -o /dev/null -w "%{http_code}\n" "http://127.0.0.1:8000/parts?query=holodeck"
```

## 5. Vérifications visuelles (dans le navigateur)

Ouvrir `http://127.0.0.1:8000/` et vérifier :

- [ ] La liste des vaisseaux s'affiche, triée par nombre de droïdes croissant (les premiers doivent afficher "Droids: none")
- [ ] Chaque carte affiche "Arrived", "Parts: N" et "Droids: ..."
- [ ] Le lien "Parts" du header mène bien à `/parts`
- [ ] La pagination (Previous/Next) fonctionne en bas de liste

Cliquer sur un vaisseau (`/starships/{slug}`) et vérifier :

- [ ] Statut, capitaine, classe, "Arrived At" (relatif, via le filtre `ago`)
- [ ] Section "Droids" : `Nom (assigned X ago), Nom (assigned Y ago), ...` ou "No droids on board" si aucun
- [ ] Section "Expensive Parts (N)" : uniquement les pièces > 50 000 crédits

Aller sur `/parts` et vérifier :

- [ ] Les pièces sont triées par prix décroissant
- [ ] Chaque pièce affiche `(assigned to <nom du vaisseau>)`
- [ ] La barre de recherche fonctionne sur le nom ET les notes (ex: `holodeck`, `controls`)
- [ ] Le champ de recherche conserve sa valeur après soumission

## 6. Tests de non-régression rapides (tout en un)

```bash
# Reset complet + vérification des 4 pages principales
symfony console doctrine:fixtures:load --no-interaction && \
curl -s -o /dev/null -w "homepage: %{http_code}\n" http://127.0.0.1:8000/ && \
curl -s -o /dev/null -w "parts: %{http_code}\n" http://127.0.0.1:8000/parts && \
SLUG=$(symfony console dbal:run-sql "SELECT slug FROM starship LIMIT 1" | grep -oE '[a-z0-9-]+-[0-9]+|[a-z-]+' | tail -1) && \
curl -s -o /dev/null -w "show: %{http_code}\n" "http://127.0.0.1:8000/starships/$SLUG"
```

## 7. Tests automatisés

Le projet a une vraie suite PHPUnit (`phpunit/phpunit` + `symfony/browser-kit` +
`zenstruck/foundry` pour les données de test), configurée dans `phpunit.dist.xml`.

```bash
# Créer la base de test dédiée (une seule fois, ou après un changement de credentials)
symfony console --env=test doctrine:database:create --if-not-exists

# Lancer toute la suite
php bin/phpunit

# Lancer un seul fichier
php bin/phpunit tests/Controller/MainControllerTest.php

# Lancer avec le détail des notices/dépréciations PHPUnit
php bin/phpunit --display-phpunit-notices
```

Points importants sur la config actuelle :

- `.env.test` pointe vers une base **séparée** (`app_test`, suffixée automatiquement par
  Symfony via `dbname_suffix` dans `config/packages/doctrine.yaml`) — les tests ne touchent
  jamais aux 125 vaisseaux de la base de dev.
- Les tests `WebTestCase` utilisent les traits Foundry `Factories` + `ResetDatabase`, qui
  recréent le schéma et vident les tables entre chaque test automatiquement.
- L'appel réel à l'API ISS (`api.wheretheiss.at`) est remplacé en environnement de test par un
  `MockHttpClient` (voir `tests/Support/TestHttpClientFactory.php`, câblé dans
  `config/services.yaml` sous `when@test`) — la suite ne dépend jamais du réseau.
- Pour se connecter dans un test, utiliser `$client->loginUser(UserFactory::createOne())`
  plutôt que de soumettre le vrai formulaire `/login` : celui-ci utilise un CSRF stateless
  basé sur un contrôleur Stimulus JS, non exécutable dans un test PHP (même limitation que
  pour `curl`, voir section 3bis).

Fichiers actuels :

- `tests/Twig/Runtime/AppExtensionRuntimeTest.php` — test unitaire pur du filtre `ago`
- `tests/Controller/SecurityControllerTest.php` — redirection anonyme, rendu du formulaire de login
- `tests/Controller/MainControllerTest.php` — homepage authentifiée + affichage ISS mocké

### Documentation officielle

- Symfony — [Testing](https://symfony.com/doc/current/testing.html) (bases PHPUnit),
  [Testing with a Database](https://symfony.com/doc/current/testing/database.html) (patterns
  DAMA/Foundry pour isoler la base entre tests)
- Foundry — [Testing avec Foundry](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#testing)
  (`ResetDatabase`, `Factories`, données factices)
- PHPUnit — [manuel officiel](https://docs.phpunit.de/en/12.3/)
- SymfonyCasts a aussi un cours dédié : [PHPUnit & Symfony: Testing Your App](https://symfonycasts.com/screencast/phpunit)

## 8. Ce qui doit être vrai en base après un `fixtures:load`

| Table            | Nombre de lignes attendu |
|------------------|---------------------------|
| `starship`       | 125                        |
| `starship_part`  | 100                         |
| `droid`          | 100                         |
| `starship_droid` | variable (entre 100 et 500, 1 à 5 par nouveau vaisseau) |
