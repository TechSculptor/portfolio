# Dossier [Factory/](.)

Contient les **factories** (via le bundle [Zenstruck Foundry](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html)) qui savent générer des entités avec des données factices réalistes, en s'appuyant sur [Faker](https://fakerphp.github.io/). Chaque factory étend `PersistentObjectFactory<TonEntité>`.

Ces factories sont consommées par [`AppFixtures`](../DataFixtures/AppFixtures.php) (et pourraient l'être par [`AppStory`](../Story/AppStory.php)) pour peupler la base de données avec un jeu de données de démo/test, généralement via `bin/console doctrine:fixtures:load`.

## Anatomie d'une factory

Chaque factory implémente 3 méthodes clés :

- **`class(): string`** — indique à Foundry quelle entité elle construit (ex: `User::class`).
- **`defaults(): array|callable`** — les valeurs par défaut de chaque champ quand on crée une instance sans les préciser. Utilise `self::faker()` (Faker) pour générer des valeurs aléatoires mais réalistes (email, nombre, élément pris au hasard dans une liste...).
- **`initialize(): static`** — permet de brancher un hook `afterInstantiate()` pour exécuter du code après la création de l'objet (utile quand une valeur dépend d'une autre, ou nécessite un service).

Deux méthodes statiques sont ensuite utilisées pour instancier des objets à partir de ces règles : `createOne(array $attributes = [])` (une instance, en persistant en base) et `createMany(int $n, array|callable $attributes = [])` (plusieurs instances).

## [UserFactory.php](UserFactory.php)

- Génère un email unique factice (`safeEmail()`), aucun rôle par défaut (`roles: []` → donc juste `ROLE_USER` via [`User::getRoles()`](../Entity/User.php)), et un mot de passe en clair par défaut (`'password'`).
- Contrairement aux 3 autres factories, celle-ci a besoin d'un **service injecté** (`UserPasswordHasherInterface`) — c'est pour ça qu'elle a un vrai constructeur. Foundry sait instancier les factories comme des services Symfony quand elles ont des dépendances.
- Utilise `initialize()` + `afterInstantiate()` pour **hacher le mot de passe après coup** : `defaults()` fournit un mot de passe en clair, puis le hook le remplace par sa version hachée (même logique que dans [`CreateUserCommand`](../Command/CreateUserCommand.php)) — impossible de le faire directement dans `defaults()` car le hachage a besoin de l'objet `User` déjà construit (`hashPassword($user, ...)`).

## [StarshipFactory.php](StarshipFactory.php)

- Pas de dépendance de service (constructeur vide, laissé en `@todo` par le scaffolding Foundry par défaut).
- Tire au hasard un nom, une classe, un capitaine et un statut (`StarshipStatusEnum`) parmi des listes de constantes internes (`SHIP_NAMES`, `CLASSES`, `CAPTAINS`) plutôt que du texte Faker générique — pour garder une ambiance "univers Star Trek/Star Wars" cohérente dans les données de démo.

## [StarshipPartFactory.php](StarshipPartFactory.php)

- Choisit une pièce au hasard dans `$partIdeas`, un dictionnaire `nom => description spatiale et temporelle ` (ex: `'mind tech to the new era ' => 'timeless'`).
- Génère un prix aléatoire (`randomNumber(5)`, jusqu'à 5 chiffres).
- **`starship: StarshipFactory::randomOrCreate([...])`** — lie chaque pièce à un vaisseau : soit un vaisseau existant est réutilisé au hasard, soit un nouveau est créé à la volée avec le statut `IN_PROGRESS`, si aucun n'existe encore. C'est ce qui permet d'enchaîner les factories entre elles sans les appeler manuellement dans le bon ordre.

## [DroidFactory.php](DroidFactory.php)

- Génère un nom de droïde (`R2-D2`, `C-3PO`...) et une fonction primaire (`astromech`, `protocol`, `assassin`...) tirés de listes fixes.
- La plus simple des 4 : pas de dépendance, pas de relation vers une autre entité (le lien vaisseau ↔ droïde est géré côté [`AppFixtures`](../DataFixtures/AppFixtures.php), pas ici).

## Où c'est utilisé : [AppFixtures.php](../DataFixtures/AppFixtures.php)

C'est la classe orchestratrice (`Doctrine\Bundle\FixturesBundle\Fixture`, exécutée par `doctrine:fixtures:load`) :

1. Crée 20 vaisseaux aléatoires (`StarshipFactory::createMany(20)`).
2. Crée 3 vaisseaux "à la main" (sans factory) avec des valeurs 100% fixes, un clin d'œil Star Trek assumé (`Jean-Luc Pickles`, `James T. Quick!`...).
3. Recrée 2 de ces mêmes vaisseaux via la factory cette fois (attributs forcés en paramètre de `createOne()`, qui écrasent les valeurs de `defaults()`).
4. Crée 100 droïdes, puis 100 vaisseaux supplémentaires en leur associant 1 à 5 droïdes chacun (`DroidFactory::randomRange(1, 5)`).
5. Crée 100 pièces détachées (chacune se rattachant automatiquement à un vaisseau via `StarshipFactory::randomOrCreate()` dans la factory elle-même).

## Et [AppStory.php](../Story/AppStory.php) ?

Une **Story** Foundry est un scénario de données nommé et réutilisable (`#[AsFixture(name: 'main')]`), une façon alternative/complémentaire aux fixtures classiques d'orchestrer des factories. Ici, elle est encore vide (scaffolding par défaut, `build()` ne fait rien) — pas encore utilisée dans ce projet, contrairement à `AppFixtures` qui fait déjà tout le travail.
