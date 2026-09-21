# [Factory/](../src/Factory) folder

Contains the **factories** (through the [Zenstruck Foundry](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html) bundle) that know how to generate entities with realistic fake data, relying on [Faker](https://fakerphp.github.io/). Each factory extends `PersistentObjectFactory<YourEntity>`.

These factories are used by [`AppFixtures`](../src/DataFixtures/AppFixtures.php) (and could be used by [`AppStory`](../src/Story/AppStory.php)) to populate the database with a demo/test data set, usually through `bin/console doctrine:fixtures:load`.

## Anatomy of a factory

Each factory implements 3 key methods:

- **`class(): string`** — tells Foundry which entity it builds (e.g. `User::class`).
- **`defaults(): array|callable`** — the default value of each field when an instance is created without specifying them. Uses `self::faker()` (Faker) to generate random but realistic values (email, number, an item picked from a list...).
- **`initialize(): static`** — lets you register an `afterInstantiate()` hook to run code after the object is created (useful when one value depends on another, or needs a service).

Two static methods are then used to instantiate objects from these rules: `createOne(array $attributes = [])` (one instance, persisted in the database) and `createMany(int $n, array|callable $attributes = [])` (several instances).

## [UserFactory.php](../src/Factory/UserFactory.php)

- Generates a fake unique email (`safeEmail()`), no role by default (`roles: []` → so just `ROLE_USER` through [`User::getRoles()`](../src/Entity/User.php)), and a default plain password (`'password'`).
- Unlike the 3 other factories, this one needs an **injected service** (`UserPasswordHasherInterface`) — that is why it has a real constructor. Foundry can instantiate factories as Symfony services when they have dependencies.
- Uses `initialize()` + `afterInstantiate()` to **hash the password afterwards**: `defaults()` provides a plain password, then the hook replaces it with its hashed version (same logic as in [`CreateUserCommand`](../src/Command/CreateUserCommand.php)) — it cannot be done directly in `defaults()` because hashing needs the already-built `User` object (`hashPassword($user, ...)`).

## [StarshipFactory.php](../src/Factory/StarshipFactory.php)

- No service dependency (empty constructor, left as a `@todo` by the default Foundry scaffolding).
- Randomly picks a name, a class, a captain and a status (`StarshipStatusEnum`) from internal constant lists (`SHIP_NAMES`, `CLASSES`, `CAPTAINS`) rather than generic Faker text — to keep a consistent "Star Trek / Star Wars universe" feel in the demo data.

## [StarshipPartFactory.php](../src/Factory/StarshipPartFactory.php)

- Picks a random part from `$partIdeas`, a dictionary of `name => spatial and temporal description` (e.g. `'mind tech to the new era ' => 'timeless'`).
- Generates a random price (`randomNumber(5)`, up to 5 digits).
- **`starship: StarshipFactory::randomOrCreate([...])`** — links each part to a starship: either an existing starship is reused at random, or a new one is created on the fly with the `IN_PROGRESS` status if none exists yet. This is what allows chaining factories together without calling them manually in the right order.

## [DroidFactory.php](../src/Factory/DroidFactory.php)

- Generates a droid name (`R2-D2`, `C-3PO`...) and a primary function (`astromech`, `protocol`, `assassin`...) picked from fixed lists.
- The simplest of the 4: no dependency, no relation to another entity (the starship ↔ droid link is handled in [`AppFixtures`](../src/DataFixtures/AppFixtures.php), not here).

## Where it is used: [AppFixtures.php](../src/DataFixtures/AppFixtures.php)

This is the orchestrating class (`Doctrine\Bundle\FixturesBundle\Fixture`, run by `doctrine:fixtures:load`):

1. Creates 20 random starships (`StarshipFactory::createMany(20)`).
2. Creates 3 starships "by hand" (without a factory) with 100% fixed values, an assumed Star Trek wink (`Jean-Luc Pickles`, `James T. Quick!`...).
3. Re-creates 2 of these same starships through the factory this time (attributes forced as `createOne()` parameters, which override the values from `defaults()`).
4. Creates 100 droids, then 100 extra starships while associating 1 to 5 droids with each of them (`DroidFactory::randomRange(1, 5)`).
5. Creates 100 spare parts (each one automatically attaches itself to a starship through `StarshipFactory::randomOrCreate()` in the factory itself).

## And [AppStory.php](../src/Story/AppStory.php)?

A Foundry **Story** is a named, reusable data scenario (`#[AsFixture(name: 'main')]`), an alternative/complementary way to orchestrate factories compared to classic fixtures. Here it is still empty (default scaffolding, `build()` does nothing) — not used yet in this project, unlike `AppFixtures`, which already does all the work.
