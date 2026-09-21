# [Form/](../src/Form) folder

Contains the Symfony **form types** (`AbstractType`), used by the controllers through `$this->createForm(XxxType::class)`. Each class defines which fields to display, their validation constraints, and optionally which entity the data is bound to (`data_class`).

Two methods appear in every type:

- **`buildForm(FormBuilderInterface $builder, array $options)`** — declares the form fields (`->add('field', FieldType::class, [options])`).
- **`configureOptions(OptionsResolver $resolver)`** — configures form-wide options (e.g. `data_class` to bind it to an entity, the HTTP method, CSRF protection...).

## [StarshipType.php](../src/Form/StarshipType.php)

Form to create/edit a starship, used by [`StarshipAdminController`](../src/Controller/StarshipAdminController.php).

- Bound to the `Starship` entity through `data_class` — the form data is read from / written to a `Starship` object directly.
- Simple fields: `name`, `class`, `captain` — no type specified (implicit `null` second argument), so Symfony **automatically guesses** the HTML field type by inspecting the entity's Doctrine metadata (text, etc.).
- `status` uses `EnumType::class` — a special field type for native PHP enums, which generates a drop-down list from the cases of `StarshipStatusEnum` and automatically converts between the `<select>` value and the enum case on the PHP side.

## [StarshipPartType.php](../src/Form/StarshipPartType.php)

Form to create/edit a spare part, used by [`AdminController::newStarshipPart()`](../src/Controller/AdminController.php).

- Bound to the `StarshipPart` entity through `data_class`.
- **`name`** — `NotBlank` constraint (the Symfony validator rejects the form if the field is empty, with a custom error message).
- **`price`** — two combined constraints: `GreaterThan(0)` (forbids a zero or negative price) and `NotBlank`, plus a help text (`help`) displayed under the field.
- **`notes`** — free field, no particular constraint.
- **`starship`** — uses `EntityType::class` (a specialized Doctrine field) to offer a drop-down list of existing starships:
  - `choice_label`: a function that customizes the text displayed for each option (`"Name (by Captain)"`) instead of using the default `__toString()`.
  - `query_builder`: customizes the query used to load the choices (here, sorted by name) instead of the implicit `findAll()`.
  - `priority: 10`: influences the display order of the field in the form relative to the other fields.
- **`createAndAddNew`** — a second submit button (`SubmitType`), in addition to the implicit "submit" button. It lets the controller know *which* of the two buttons was clicked (`$form->get('createAndAddNew')->isClicked()`, see [AdminController.php](../src/Controller/AdminController.php)) to decide where to redirect after saving. `'validate' => false` disables the browser's native HTML5 validation on this particular button.

## [PartSearchType.php](../src/Form/PartSearchType.php)

Small search form, used by [`PartController::index()`](../src/Controller/PartController.php).

- **No `data_class`** — this form is not bound to any entity; its data is just a simple associative array (`['query' => '...']`), read directly through `$searchForm->get('query')->getData()`.
- A single `query` field of type `SearchType` (rendered as an HTML `<input type="search">`), without a visible label (`'label' => false`), with a `placeholder` and Tailwind CSS classes for styling.
- `'required' => false` — the search is optional (the form is submitted even with an empty field).
- **`configureOptions()`**: two notable settings —
  - `'method' => Request::METHOD_GET` — the form is submitted with `GET` (not `POST`), so the search appears in the URL (`?query=...`) and can be shared, refreshed and bookmarked.
  - `'csrf_protection' => false` — no CSRF token, consistent with a `GET` request (CSRF protects against actions that change state, not a simple read/search).
- **`getBlockPrefix(): string { return ''; }`** — removes the usual prefix of the generated field names (normally based on the class name, e.g. `part_search[query]`), to get `?query=...` directly in the URL instead of `?part_search[query]=...`.

## Common points to remember

- **Declarative validation** — the constraints (`NotBlank`, `GreaterThan`...) are set directly on the field in `buildForm()`, rather than on the entity itself (another possible approach uses `#[Assert\...]` attributes on the properties).
- **`data_class`** distinguishes "entity-bound" forms (`StarshipType`, `StarshipPartType`) from "free data" forms (`PartSearchType`), which changes how `$form->getData()` returns the results (entity object vs array).
- These types are then rendered in the matching Twig templates (`{{ form(form) }}` or field by field), see the [`templates/`](../templates) folder.
