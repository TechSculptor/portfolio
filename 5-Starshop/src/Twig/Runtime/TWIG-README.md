# [AppExtensionRuntime.php](AppExtensionRuntime.php)

Ajoute des **fonctions et filtres Twig personnalisés**, utilisables directement dans les templates `.html.twig`, en plus des filtres/fonctions natifs de Twig (`|upper`, `|date`, `path()`...).

## Comment c'est branché

Twig sépare la **déclaration** de la **logique** en 2 classes :

- **[AppExtension.php](../AppExtension.php)** — la déclaration : dit à Twig "il existe un filtre `ago` et une fonction `get_iss_location_data`", et pointe vers la méthode qui l'implémente.
  ```php
  new TwigFilter('ago', [AppExtensionRuntime::class, 'ago']);
  new TwigFunction('get_iss_location_data', [AppExtensionRuntime::class, 'getIssLocationData']);
  ```
- **`AppExtensionRuntime` (ce fichier)** — l'implémentation réelle, dans une classe séparée qui implémente `RuntimeExtensionInterface`. Twig ne l'instancie **que si le filtre/la fonction est réellement utilisé** dans un template — utile ici car cette classe a des dépendances "lourdes" (client HTTP, cache), qu'on ne veut pas construire à chaque rendu de page si elles ne servent pas.

## Les 2 dépendances injectées

- **`HttpClientInterface $client`** — le client HTTP de Symfony, pour appeler une API externe.
- **`CacheInterface $issLocationPool`** — un pool de cache **dédié** (pas le cache par défaut), défini dans [config/packages/cache.yaml](../../../config/packages/cache.yaml) :
  ```yaml
  cache:
      pools:
          iss_location_pool:
              default_lifetime: '%iss_location_cache_ttl%'
  ```
  L'attribut `#[Autowire(service: 'iss_location_pool')]` force Symfony à injecter **ce pool précis** plutôt que le `CacheInterface` par défaut de l'application (sinon l'autowiring ne saurait pas lequel choisir, plusieurs services implémentant la même interface).

## `getIssLocationData(): ?array`

Récupère la position actuelle de la Station Spatiale Internationale (ISS) via l'API publique `wheretheiss.at`, et **met le résultat en cache** :

- `$this->issLocationPool->get('iss_location_data', function (ItemInterface $item) { ... })` — pattern "cache avec callback" : si la clé `iss_location_data` existe déjà en cache et n'a pas expiré (durée définie par `iss_location_cache_ttl`), la valeur en cache est retournée directement, **sans appeler l'API**. Sinon, la fonction callback s'exécute, fait l'appel HTTP, et son résultat est automatiquement stocké en cache pour la prochaine fois.
- Pourquoi mettre en cache : éviter de spammer l'API externe à chaque affichage de page (la position ISS ne change pas assez vite pour justifier un appel à chaque requête).
- En cas d'erreur réseau (`HttpClientExceptionInterface`), retourne `null` plutôt que de faire planter la page — utilisé côté template pour afficher un état de repli.
- Exposé au template via la fonction Twig `get_iss_location_data()` (voir [templates/main/homepage.html.twig](../../../templates/main/homepage.html.twig)).

## `ago(?\DateTimeInterface $date): string`

Convertit une date en texte relatif humain ("il y a 3 jours", "in 2 hours"), un peu comme fait `moment.js`/`date-fns` côté JS :

- Si `$date` est `null` → `'Not yet arrived'` (cas d'usage probable : un vaisseau/droïde pas encore livré).
- Calcule la différence en secondes avec l'instant présent (`\DateTimeImmutable`), détecte si c'est dans le passé ou le futur (`$future`), puis prend la valeur absolue.
- Parcourt une table d'unités (année → seconde) **de la plus grande à la plus petite**, et s'arrête dès qu'une unité "tient" au moins une fois dans la différence (ex: 400 000 secondes ≈ 4 jours → s'arrête sur "day", pas "hour" ni "second").
- Gère le pluriel (`'day' . ($count > 1 ? 's' : '')`) et la formulation passé/futur (`"%s ago"` vs `"in %s"`).
- Exposé au template via le filtre Twig `|ago`, ex. `{{ starship.createdAt|ago }}`.
