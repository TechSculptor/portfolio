# Présentation du projet — Todo List Angular pour développeurs

Points à utiliser si on te demande de montrer un projet de ton portfolio.

## En une phrase

Une todo-list Angular 21 (signals, standalone components) pensée spécifiquement pour l'organisation quotidienne d'un développeur — priorités, tags dev (bug/feature/refactor...), liens vers PR/branches, sous-tâches, et une suggestion de documentation façon agent IA.

## Pourquoi ce projet

Base générée par Angular CLI, transformée en une vraie feature applicative pour explorer les nouveautés d'Angular récent (signals, `@if`/`@for`, composants standalone) sur un cas d'usage concret plutôt qu'un tutoriel classique.

## Stack technique

- **Angular 21** — composants standalone (pas de NgModules), nouvelle syntaxe de contrôle de flux (`@if`, `@for`, `@empty`)
- **Signals** (`signal`, `computed`, `effect`) pour tout l'état réactif — zéro RxJS nécessaire pour cette partie de l'app
- **FormsModule** (`ngModel`) pour les formulaires, y compris le binding natif d'un `<select multiple>` sur un tableau
- **HttpClient** (`provideHttpClient(withFetch())`) pour la partie `/users` (appel à une API REST publique)
- Persistance **`localStorage`**, sans backend à ce stade

## Fonctionnalités à montrer (par ordre d'impact)

1. **Priorités + tags dev** — sélection à la création, points colorés cliquables pour changer la priorité, filtrage par tag.
2. **Sous-tâches imbriquées** — panneau dépliable par tâche, progression affichée (`2/3`).
3. **Suggestion de documentation façon agent IA** — tape une tâche ("Apprendre les Signals"), clique sur suggérer : l'app propose des liens de doc pertinents en se basant sur des mots-clés, avec un état de chargement simulé. *Bon point à développer à l'oral : actuellement mocké côté client par design — un vrai appel à un LLM ou une recherche web nécessiterait une clé API, donc un petit backend pour ne jamais l'exposer côté navigateur. Montre que tu penses sécurité, pas juste "faire fonctionner".*
4. **Rappels intelligents** — fréquence de relance qui s'adapte à la proximité de l'échéance (pas d'échéance = rappel régulier, échéance lointaine = rappels plus fréquents pour ne pas l'oublier).
5. **Persistance localStorage avec migration de schéma** — `normalizeTodo()` comble les champs manquants sur d'anciennes données sauvegardées quand le modèle évolue. *Détail "vécu" à mentionner : le genre de piège qu'on rencontre en vrai quand un schéma de données change en prod.*

## Choix d'architecture à mentionner

- **Découpage en plusieurs fichiers par responsabilité** plutôt qu'un seul composant fourre-tout : `todo-list.models.ts` (types), `todo-list.date-utils.ts` (fonctions pures), `todo-list.seed-data.ts` (données + persistance), `todo-stats.ts` (statistiques dérivées). Le composant lui-même ne garde que l'état et les interactions utilisateur.
- **Fonctions pures testables** séparées du composant (ex: `daysBetween`, `reminderIntervalDays`) — faciles à tester unitairement sans monter tout Angular.
- **Immutabilité systématique** sur les mises à jour de signal (`.map()`/`.filter()`, jamais de mutation en place) — évite les bugs discrets liés à la mutation d'un tableau partagé.

## Pistes d'amélioration (si on te demande "et après ?")

Roadmap déjà planifiée et partiellement en cours :
- Tri et filtres avancés (priorité / échéance / statut)
- Raccourcis clavier
- Dashboard avec statistiques (streak, graphique hebdomadaire — les calculs sont déjà prêts dans `todo-stats.ts`, pas encore branchés à l'écran)
- Estimation de temps / minuteur pomodoro
- Un vrai backend pour la suggestion de documentation (actuellement mockée)

## Questions probables et pistes de réponse

- **"Pourquoi Signals plutôt que RxJS/Observables ?"** → Pour un état local de composant simple à moyen, les signals donnent une API plus directe (pas d'abonnement/désabonnement à gérer), une détection de changement plus fine, et c'est la direction que prend Angular. RxJS reste pertinent pour des flux asynchrones complexes (ex: `HttpClient` renvoie toujours un Observable).
- **"Comment tu testerais cette app ?"** → En priorité les fonctions pures (`daysBetween`, `reminderIntervalDays`, `normalizeTodo`) qui ne nécessitent pas de monter le composant, puis des tests de composant sur les interactions clés (ajout, suppression, filtrage).
- **"Comment tu passerais ça en vrai produit ?"** → Remplacer `localStorage` par un backend (API REST ou GraphQL) avec authentification, déplacer la logique de suggestion de doc côté serveur pour protéger la clé API, ajouter une vraie synchronisation multi-appareils.
