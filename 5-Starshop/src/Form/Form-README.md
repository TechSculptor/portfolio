# Dossier [Form/](.)

Contient les **types de formulaire** Symfony (`AbstractType`), utilisés par les contrôleurs via `$this->createForm(XxxType::class)`. Chaque classe définit quels champs afficher, leurs contraintes de validation, et éventuellement à quelle entité les données sont liées (`data_class`).

Deux méthodes reviennent dans chaque type :

- **`buildForm(FormBuilderInterface $builder, array $options)`** — déclare les champs du formulaire (`->add('champ', TypeDeChamp::class, [options])`).
- **`configureOptions(OptionsResolver $resolver)`** — configure des options globales au formulaire (ex: `data_class` pour le lier à une entité, méthode HTTP, protection CSRF...).

## [StarshipType.php](StarshipType.php)

Formulaire de création/édition d'un vaisseau, utilisé par [`StarshipAdminController`](../Controller/StarshipAdminController.php).

- Lié à l'entité `Starship` via `data_class` — les données du formulaire sont lues/écrites directement sur/depuis un objet `Starship`.
- Champs simples : `name`, `class`, `captain` — pas de type précisé (2ᵉ argument `null` implicite), donc Symfony **devine automatiquement** le type de champ HTML en inspectant les métadonnées Doctrine de l'entité (texte, etc.).
- `status` utilise `EnumType::class` — un type de champ spécial pour les enums PHP natifs, qui génère une liste déroulante à partir des cas de `StarshipStatusEnum` et convertit automatiquement entre la valeur du `<select>` et le cas d'enum côté PHP.

## [StarshipPartType.php](StarshipPartType.php)

Formulaire de création/édition d'une pièce détachée, utilisé par [`AdminController::newStarshipPart()`](../Controller/AdminController.php).

- Lié à l'entité `StarshipPart` via `data_class`.
- **`name`** — contrainte `NotBlank` (le validator Symfony rejette le formulaire si le champ est vide, avec un message d'erreur personnalisé).
- **`price`** — deux contraintes cumulées : `GreaterThan(0)` (interdit un prix nul ou négatif) et `NotBlank`, plus un texte d'aide (`help`) affiché sous le champ.
- **`notes`** — champ libre, aucune contrainte particulière.
- **`starship`** — utilise `EntityType::class` (champ Doctrine spécialisé) pour proposer une liste déroulante des vaisseaux existants :
  - `choice_label` : une fonction qui personnalise le texte affiché pour chaque option (`"Nom (by Capitaine)"`) plutôt que d'utiliser `__toString()` par défaut.
  - `query_builder` : personnalise la requête utilisée pour charger les choix (ici, triés par nom) plutôt que `findAll()` implicite.
  - `priority: 10` : influence l'ordre d'affichage du champ dans le formulaire par rapport aux autres.
- **`createAndAddNew`** — un second bouton de soumission (`SubmitType`), en plus du bouton "submit" implicite. Cela permet au contrôleur de savoir *lequel* des deux boutons a été cliqué (`$form->get('createAndAddNew')->isClicked()`, voir [AdminController.php:31-35](../Controller/AdminController.php#L31-L35)) pour décider où rediriger après la sauvegarde. `'validate' => false` désactive la validation HTML5 native du navigateur sur ce bouton précis.

## [PartSearchType.php](PartSearchType.php)

Petit formulaire de recherche, utilisé par [`PartController::index()`](../Controller/PartController.php).

- **Pas de `data_class`** — ce formulaire n'est lié à aucune entité, ses données sont juste un tableau associatif simple (`['query' => '...']`), lu directement via `$searchForm->get('query')->getData()`.
- Un seul champ `query` de type `SearchType` (rendu HTML `<input type="search">`), sans label affiché (`'label' => false`), avec un `placeholder` et des classes CSS Tailwind pour le style.
- `'required' => false` — la recherche est facultative (formulaire soumis même avec un champ vide).
- **`configureOptions()`** : deux réglages notables —
  - `'method' => Request::METHOD_GET'` — le formulaire est soumis en `GET` (pas `POST`), ce qui permet à la recherche d'apparaître dans l'URL (`?query=...`), d'être partageable/rafraîchissable/mise en favori.
  - `'csrf_protection' => false'` — pas de jeton CSRF, cohérent avec une requête `GET` (le CSRF protège contre des actions qui modifient un état, pas une simple lecture/recherche).
- **`getBlockPrefix(): string { return ''; }`** — supprime le préfixe habituel des noms de champs générés (normalement basé sur le nom de la classe, ex. `part_search[query]`), pour avoir directement `?query=...` dans l'URL plutôt que `?part_search[query]=...`.

## Points communs à retenir

- **Validation déclarative** — les contraintes (`NotBlank`, `GreaterThan`...) sont posées directement sur le champ dans `buildForm()`, plutôt que dans l'entité elle-même (autre approche possible avec des attributs `#[Assert\...]` sur les propriétés).
- **`data_class`** distingue les formulaires "liés à une entité" (`StarshipType`, `StarshipPartType`) des formulaires "à données libres" (`PartSearchType`), ce qui change la façon dont `$form->getData()` retourne les résultats (objet entité vs tableau).
- Ces types sont ensuite rendus dans les templates Twig correspondants (`{{ form(form) }}` ou champ par champ), voir le dossier [`templates/`](../../templates/).
