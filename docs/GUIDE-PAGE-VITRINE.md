# Guide : refaire une page vitrine comme TechSculptor

Ce guide explique **comment est construite** la page [index.html](../index.html) et **comment en refaire une** (pour toi ou pour un client) sans repartir de zéro. Chaque partie dit *quoi*, *pourquoi*, et pointe vers le vrai code.

Sommaire :

1. [Vue d'ensemble](#1-vue-densemble)
2. [Le CSS : Tailwind compilé + variables de couleur](#2-le-css--tailwind-compilé--variables-de-couleur)
3. [Thème clair / sombre](#3-thème-clair--sombre)
4. [La mise en page en deux colonnes](#4-la-mise-en-page-en-deux-colonnes)
5. [Les composants maison](#5-les-composants-maison)
6. [Le JavaScript (sans framework)](#6-le-javascript-sans-framework)
7. [SEO : être trouvé sur Google](#7-seo--être-trouvé-sur-google)
8. [Formulaire de contact sans serveur](#8-formulaire-de-contact-sans-serveur)
9. [Publier sur GitHub Pages](#9-publier-sur-github-pages)
10. [Les pièges rencontrés (et leurs solutions)](#10-les-pièges-rencontrés-et-leurs-solutions)
11. [Checklist pour un nouveau site](#11-checklist-pour-un-nouveau-site)
12. [Exercices pour progresser](#12-exercices-pour-progresser)

---

## 1. Vue d'ensemble

Le site est **statique** : pas de serveur, pas de base de données, pas de framework. Juste du HTML, du CSS et un peu de JavaScript, hébergés gratuitement sur GitHub Pages.

| Fichier | Rôle |
|---|---|
| [index.html](../index.html) | La page : `<head>` (SEO), le contenu, et le `<script>` en bas |
| [mentions-legales.html](../mentions-legales.html) | Page légale obligatoire pour un professionnel |
| [assets/css/input.css](../assets/css/input.css) | **Source** du CSS : couleurs + composants maison |
| [tailwind.config.js](../tailwind.config.js) | Configuration Tailwind (fichiers à scanner, police, couleurs) |
| [assets/css/site.css](../assets/css/site.css) | CSS **généré** (minifié) : ne jamais le modifier à la main |
| [sitemap.xml](../sitemap.xml) | Liste des pages pour Google |
| `media/` | Image de partage (`og-image.png`), icône iPhone, vidéo, captures de sites |
| [.vscode/tasks.json](../.vscode/tasks.json) | Raccourcis VS Code : serveur local + compilation CSS |

Le cycle de travail :

```text
modifier index.html / input.css
        │
        ▼
npx tailwindcss@3 -i assets/css/input.css -o assets/css/site.css --minify
        │
        ▼
vérifier sur http://localhost:8000/   (python -m http.server 8000)
        │
        ▼
git commit + git push  →  GitHub Pages publie en ~1 minute
```

---

## 2. Le CSS : Tailwind compilé + variables de couleur

### Pourquoi compiler Tailwind au lieu du CDN ?

Le CDN (`<script src="https://cdn.tailwindcss.com">`) est pratique pour tester, mais il génère le CSS **dans le navigateur de chaque visiteur** : plus lent, et Tailwind déconseille de l'utiliser en production. La compilation produit un petit fichier (`site.css`) qui ne contient **que les classes réellement utilisées**.

```bash
npx tailwindcss@3 -i assets/css/input.css -o assets/css/site.css --minify
```

- `npx` télécharge Tailwind à la volée : rien à installer, pas de `node_modules` dans le dépôt.
- Tailwind lit les fichiers listés dans `content` ([tailwind.config.js](../tailwind.config.js)) et ne garde que les classes qu'il y trouve.

> ⚠️ Si tu ajoutes une classe dans le HTML et qu'elle « ne marche pas », c'est presque toujours que tu as **oublié de recompiler**. Et si tu crées une nouvelle page HTML, ajoute-la dans `content`.

### Les couleurs en variables CSS (l'astuce centrale)

Toutes les couleurs de structure sont des **variables CSS** définies dans [input.css](../assets/css/input.css) :

```css
:root {            /* thème clair */
  --canvas: 255 255 255;   /* fond de page */
  --band: 219 233 247;     /* fond des bandes bleues */
  --head: 15 23 42;        /* titres */
  --gold: 14 165 233;      /* couleur d'accent (bleu en clair) */
  ...
}
.dark {            /* thème sombre : on ne change QUE les valeurs */
  --canvas: 28 28 31;
  --band: 30 38 51;
  --gold: 214 192 92;      /* l'accent devient doré */
  ...
}
```

Puis [tailwind.config.js](../tailwind.config.js) transforme chaque variable en couleur Tailwind :

```js
const v = (name) => `rgb(var(--${name}) / <alpha-value>)`;
colors: { canvas: v('canvas'), head: v('head'), gold: v('gold'), ... }
```

Résultat : tu écris `bg-canvas`, `text-head`, `border-gold/60`… et **la même classe s'adapte toute seule au thème**. Pas besoin d'écrire `dark:bg-…` partout.

Deux détails importants :

- Les valeurs sont écrites `255 255 255` (sans `rgb()` ni virgules) pour que Tailwind puisse ajouter la transparence : `bg-gold/10` devient `rgb(var(--gold) / 0.1)`.
- La variable s'appelle `--gold` même en thème clair où elle est bleue : c'est « la couleur d'accent ». Le nom est historique, ne te laisse pas piéger.

**Pour changer l'identité visuelle d'un futur site, tu modifies surtout ces deux blocs de variables.**

### Les composants maison

Plutôt que de répéter 15 classes Tailwind sur chaque bouton, [input.css](../assets/css/input.css) définit des classes réutilisables avec `@apply` :

```css
.pill { @apply inline-flex items-center rounded-full bg-gold/10 px-3 py-1 text-xs font-medium text-gold; }
```

Elles sont écrites **en dehors** de `@layer components` : ainsi Tailwind les garde toujours, même s'il ne les voit pas dans le HTML (par exemple une classe ajoutée en JavaScript comme `.dot.is-active`).

---

## 3. Thème clair / sombre

Trois morceaux travaillent ensemble.

**1. Tailwind en mode `class`** ([tailwind.config.js](../tailwind.config.js)) : `darkMode: 'class'` signifie « le thème sombre est actif quand `<html>` a la classe `dark` ».

**2. Un script dans le `<head>`, avant tout affichage** :

```html
<script>
  (function () {
    var stored = null;
    try { stored = localStorage.getItem('theme'); } catch (e) {}
    var dark = stored !== 'light';                 // sombre par défaut
    document.documentElement.classList.toggle('dark', dark);
    document.querySelector('meta[name="theme-color"]').setAttribute('content', dark ? '#1c1c1f' : '#ffffff');
  })();
</script>
```

Il est placé **dans le `<head>`** et non en bas de page pour éviter le « flash » : sinon la page s'afficherait une fraction de seconde dans le mauvais thème. Le `try/catch` protège les navigateurs où `localStorage` est bloqué (navigation privée).

**3. Le bouton** (dans le `<script>` en bas de page) inverse la classe, mémorise le choix et met à jour la couleur de la barre du navigateur (`theme-color`, visible sur mobile).

Pour afficher une icône différente selon le thème, pas de JavaScript : `hidden dark:inline` et `dark:hidden`.

```html
<i class="fa-solid fa-sun hidden dark:inline"></i>
<i class="fa-solid fa-moon dark:hidden"></i>
```

---

## 4. La mise en page en deux colonnes

Inspirée de [brittanychiang.com](https://brittanychiang.com) : sur grand écran, la colonne de gauche (accroche + menu) **reste fixe** pendant que la droite défile.

```html
<div class="lg:flex lg:justify-between lg:gap-8">
  <header class="lg:sticky lg:top-0 lg:flex lg:max-h-screen lg:w-[46%] lg:flex-col lg:justify-between">
    …accroche, menu, réseaux…
  </header>
  <main class="lg:w-[54%] lg:py-24">
    …les sections…
  </main>
</div>
```

À retenir :

- **Mobile d'abord** : sans préfixe, tout s'empile en une colonne. Le préfixe `lg:` (écran ≥ 1024 px) active les deux colonnes.
- `lg:sticky lg:top-0` + `lg:max-h-screen` : la colonne colle en haut et ne dépasse jamais la hauteur de l'écran.
- `flex-col justify-between` pousse les icônes de réseaux tout en bas de la colonne.

### Les bandes de couleur alternées

Chaque `<section>` reçoit soit `plain` (fond normal), soit `band` (fond bleu) :

```css
.plain { @apply py-12 lg:py-14; }
.band  { @apply -mx-6 px-6 py-12 md:-mx-12 md:px-12 lg:-mx-10 lg:rounded-2xl lg:px-10 lg:py-14;
         background-color: rgb(var(--band)); }
```

L'astuce `-mx-6 px-6` : la marge négative fait **dépasser** le fond jusqu'aux bords de l'écran (qui a `px-6` de marge intérieure), et le `px-6` remet le texte à sa place. Sur grand écran, on dépasse un peu moins (`lg:-mx-10`) et on arrondit les coins pour faire un panneau.

Les sections n'ont **pas de marge entre elles** : c'est leur `padding` qui crée l'espace, donc les bandes se touchent sans « petite bande blanche ».

Pour les listes (Réalisations, Expérience), `.zebra` colore une carte sur deux avec `:nth-child(odd)` :

```css
.zebra > li:nth-child(odd) { background-color: rgb(var(--band-soft)); }
```

---

## 5. Les composants maison

| Classe | Effet | Où |
|---|---|---|
| `.section-title` | Gros titre gras + trait dégradé dessous (`::after`) | Chaque `<h2>` de section |
| `.card` | Carte avec survol ; sur grand écran, **les autres cartes pâlissent** | Services, Réalisations, Expérience |
| `.btn-primary` | Bouton avec **contour en dégradé** | « Réserver un échange gratuit » |
| `.btn-line` | Bouton contour simple | « Voir mes réalisations » |
| `.link-gold` | Lien souligné couleur d'accent | Liens « Code », « Démo »… |
| `.pill` | Petite étiquette arrondie | Technologies (Node-RED, PHP…) |
| `.nav-link` | Lien du menu avec trait qui s'allonge | Menu de gauche |
| `.form-input`, `.form-label` | Champs du formulaire | Contact |

Deux techniques qui valent le coup d'être comprises :

**L'effet « les autres cartes pâlissent »** utilise les *groupes nommés* de Tailwind :

```html
<ul class="group/list"> <li class="card">…</li> … </ul>
```
```css
.card { @apply lg:group-hover/list:opacity-50 lg:hover:!opacity-100; }
```

Quand la souris survole la liste, toutes les cartes passent à 50 %, sauf celle survolée qui reste à 100 %.

**Le contour en dégradé** de `.btn-primary` : deux fonds superposés, l'un limité à l'intérieur (`padding-box`), l'autre qui déborde sous la bordure transparente (`border-box`).

```css
border: 1px solid transparent;
background:
  linear-gradient(rgb(var(--surface)), rgb(var(--surface))) padding-box,
  linear-gradient(120deg, rgb(var(--gold)), rgb(var(--steel))) border-box;
```

---

## 6. Le JavaScript (sans framework)

Tout est dans un seul `<script>` en bas de [index.html](../index.html), découpé en blocs commentés `// --- … ---`. Il est enveloppé dans `(function () { … })();` pour ne pas créer de variables globales.

Un principe partout : **respecter `prefers-reduced-motion`**. Si le visiteur a demandé moins d'animations dans son système, on désactive halo, curseur, défilement automatique et lecture auto des vidéos.

```js
var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
```

### Menu actif selon la section visible : `IntersectionObserver`

```js
var observer = new IntersectionObserver(function (entries) {
  entries.forEach(function (entry) {
    if (!entry.isIntersecting) return;
    links.forEach(function (l) { l.classList.toggle('is-current', l.getAttribute('href') === '#' + entry.target.id); });
  });
}, { rootMargin: '-30% 0px -60% 0px' });
```

Le `rootMargin` réduit la zone observée à une bande horizontale vers le haut de l'écran : la section « active » est celle qui traverse cette bande. Bien plus efficace que d'écouter `scroll`.

### Le diaporama : du CSS natif + un peu de JS

Le défilement est fait **par le CSS** (`scroll-snap`), le JS ne fait que piloter :

```html
<div id="slides" class="slides flex snap-x snap-mandatory overflow-x-auto scroll-smooth">
  <figure class="w-full shrink-0 snap-center">…</figure>
  …
</div>
```

- `flex` + `w-full shrink-0` : les diapositives sont côte à côte, chacune de la largeur du cadre.
- `snap-x snap-mandatory` + `snap-center` : le défilement s'aimante toujours sur une diapositive. **Au doigt sur mobile, ça marche même sans JavaScript.**
- Le JS crée les points, gère les flèches avec `track.scrollTo(...)`, synchronise le point actif à l'événement `scroll`, et avance toutes les 5 s (en pause au survol, au focus clavier, et quand l'onglet est caché).

### Curseur personnalisé et halo

- Le point suit la souris directement ; l'anneau la rattrape avec un léger retard grâce à une **interpolation** dans `requestAnimationFrame` : `rx += (mx - rx) * 0.18`.
- Activé seulement si `(pointer: fine)` : jamais sur écran tactile.
- Le halo est un `radial-gradient` repeint à la position de la souris.

---

## 7. SEO : être trouvé sur Google

Dans le `<head>` de [index.html](../index.html) :

| Élément | Rôle |
|---|---|
| `<title>` + `<meta name="description">` | Le titre et le texte affichés dans les résultats Google |
| `<link rel="canonical">` | L'adresse officielle de la page (évite les doublons) |
| `<meta name="google-site-verification">` + `google48fa….html` | Prouvent à Search Console que le site est à toi |
| Balises `og:*` + `media/og-image.png` (1200×630) | L'aperçu quand on partage le lien (Facebook, LinkedIn, WhatsApp) |
| `<script type="application/ld+json">` | **Données structurées** : décrit l'entreprise, ses services, ses réseaux (`sameAs`) dans un format que Google comprend |
| [sitemap.xml](../sitemap.xml) | La liste des pages à explorer, à soumettre dans Search Console |

Les « sous-titres » sous un résultat Google (*sitelinks*) ne se forcent pas : Google les affiche quand le site a une structure claire (sections avec `id`, titres explicites, plusieurs pages) et un peu de trafic.

Pour vérifier que le JSON-LD est valide : [validator.schema.org](https://validator.schema.org) ou le [test des résultats enrichis](https://search.google.com/test/rich-results).

---

## 8. Formulaire de contact sans serveur

Un site statique ne peut pas envoyer d'email. On passe par **FormSubmit**, qui reçoit les données et les transfère par email :

```js
fetch('https://formsubmit.co/ajax/' + CONTACT_EMAIL, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  body: JSON.stringify({ name: …, email: …, message: …, _subject: …, _template: 'table', _captcha: 'false' })
})
```

- **Activation** : au premier envoi depuis une nouvelle adresse (localhost, puis le site en ligne), FormSubmit envoie un mail « Activate form ». Tant que tu n'as pas cliqué, rien n'arrive.
- **Anti-spam** : un champ caché `_honey`. Un humain ne le voit pas ; un robot le remplit, et dans ce cas on n'envoie rien.
- **Repli** : si l'envoi échoue, on affiche un lien `mailto:` prérempli. Le visiteur n'est jamais bloqué.
- **Validation** : `field.checkValidity()` utilise les attributs HTML (`required`, `type="email"`) au lieu de réécrire des règles en JS.

---

## 9. Publier sur GitHub Pages

1. Dépôt GitHub → **Settings → Pages** → *Deploy from a branch* → `main` / racine.
2. Chaque `git push` sur `main` republie le site en environ une minute.
3. Suivre le déploiement : onglet **Actions** du dépôt, ou `gh api repos/<compte>/<dépôt>/pages/builds/latest`.

GitHub Pages passe les fichiers dans **Jekyll**. D'où le petit [_config.yml](../_config.yml), et une règle : **ne jamais écrire `{{` ou `{%` dans le HTML**, Jekyll essaierait de les interpréter.

Pour un client avec son propre nom de domaine : fichier `CNAME` à la racine + enregistrements DNS chez le registrar (voir la doc GitHub « Managing a custom domain »).

---

## 10. Les pièges rencontrés (et leurs solutions)

| Symptôme | Cause | Solution |
|---|---|---|
| Une classe Tailwind ne s'applique pas | CSS pas recompilé, ou page absente de `content` | Recompiler ; ajouter le fichier dans `tailwind.config.js` |
| Soleil **et** lune visibles en même temps | Le CSS de Font Awesome écrasait `hidden` | Charger `site.css` **après** Font Awesome |
| `@apply btn` provoque une erreur | `@apply` n'accepte que des classes Tailwind, pas tes propres classes | Recopier les utilitaires dans le composant |
| Dégradé SVG invisible | Le `<linearGradient>` était dans un SVG en `display:none` | Le mettre dans un SVG toujours présent de taille 0 (`absolute h-0 w-0`) |
| `var(--gold)` ignoré dans un SVG | Les attributs SVG (`stop-color="…"`) ne lisent pas les variables CSS | Utiliser `style="stop-color: rgb(var(--gold))"` |
| Page cassée sur GitHub Pages | `{{ }}` interprété par Jekyll | Éviter ces caractères dans le HTML |
| Formulaire qui marche en local mais pas en ligne | FormSubmit s'active **par adresse de page** | Cliquer le mail d'activation reçu après le 1er envoi en ligne |
| Sitemap « Impossible de récupérer » | Lenteur connue de Search Console sur les sites neufs | Attendre quelques jours ; sinon renvoyer `sitemap.xml?v=2` |
| Vidéo qui ne démarre pas toute seule | Les navigateurs n'autorisent la lecture auto que sans son | `autoplay muted loop playsinline` |
| Mauvais thème une fraction de seconde au chargement | Thème appliqué trop tard | Script du thème dans le `<head>` |

---

## 11. Checklist pour un nouveau site

**Contenu**
- [ ] Accroche orientée client (ce que tu lui apportes, pas qui tu es)
- [ ] Services, références avec captures, méthode, contact
- [ ] Mentions légales : nom, statut, adresse, SIRET, hébergeur, données personnelles

**Identité**
- [ ] Couleurs : les deux blocs `:root` et `.dark` dans `input.css`
- [ ] Police : lien Google Fonts dans le `<head>` + `fontFamily` dans `tailwind.config.js`
- [ ] Favicon SVG, `apple-touch-icon.png` (180×180), `og-image.png` (1200×630)

**Technique**
- [ ] `<title>`, description, canonical, balises `og:*`, JSON-LD (`sameAs` avec les réseaux)
- [ ] `sitemap.xml` à jour, soumis dans Search Console
- [ ] Recompiler le CSS après chaque changement de classes
- [ ] Tester : ordinateur, mobile (outils développeur, `Ctrl+Maj+M`), thème clair **et** sombre
- [ ] Accessibilité : `alt` sur les images, `aria-label` sur les boutons-icônes, lien « Aller au contenu », navigation au clavier (`Tab`)
- [ ] Formulaire : envoyer un test en ligne et cliquer le mail d'activation

**Après la mise en ligne**
- [ ] Search Console : sitemap + « Demander l'indexation »
- [ ] Fiche Google Business Profile, réseaux sociaux reliés au site

---

## 12. Exercices pour progresser

Du plus simple au plus avancé. Fais-les sur une copie de la page (ou dans une branche `git checkout -b exercice`).

1. **Changer l'identité** : passe l'accent en vert (`--gold: 16 185 129;` en clair). Recompile. Combien de fichiers as-tu dû toucher ? *(Réponse attendue : un seul, plus la compilation.)*
2. **Ajouter une section** « Tarifs » entre Services et Méthode, en respectant l'alternance `band` / `plain`. Ajoute-la au menu de gauche et au tableau d'`id` surveillés par l'`IntersectionObserver`.
3. **Nouveau composant** : crée une classe `.badge-new` (petite étiquette « Nouveau ») dans `input.css` avec `@apply`, et utilise-la sur une carte.
4. **Diaporama** : ajoute un 5ᵉ site. Vérifie que les points se créent tout seuls (le JS compte les `<figure>`).
5. **Accessibilité** : navigue sur toute la page avec `Tab` uniquement. Le diaporama se met-il bien en pause quand le focus y entre ? Lance l'audit **Lighthouse** de Chrome (F12 → Lighthouse) et corrige un point signalé.
6. **SEO** : ajoute dans le JSON-LD une propriété `aggregateRating` le jour où tu as des avis Google, puis valide-la avec le test des résultats enrichis.
7. **Défi** : refais la page **from scratch** dans un dossier vide, en ne regardant ce guide que pour les parties où tu bloques. C'est le meilleur test pour savoir si tu maîtrises.
