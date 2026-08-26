import type { Priority, Subtask, Tag, Todo } from './todo-list.models';
import { daysAgoIso, todayIso } from './todo-list.date-utils';

export const STORAGE_KEY = 'kozmodev-todos';

function seedTodo(
  text: string,
  done: boolean,
  priority: Priority,
  tags: Tag[],
  subtasks: Subtask[],
  createdDaysAgo: number,
  dueInDays: number | null = null
): Todo {
  const createdAt = daysAgoIso(createdDaysAgo);
  return {
    text,
    done,
    priority,
    tags,
    link: null,
    subtasks,
    expanded: false,
    dueDate: dueInDays !== null ? daysAgoIso(-dueInDays) : null,
    createdAt,
    lastReminderAt: createdAt,
  };
}

// Démo : la roadmap de cette todo-list elle-même, tâche par tâche.
export const DEFAULT_TODOS: Todo[] = [
  seedTodo(
    'Ajouter des priorités aux tâches (basse / moyenne / haute)',
    true,
    'medium',
    ['feature'],
    [
      { text: 'Sélecteur de priorité à la création', done: true },
      { text: 'Point coloré cliquable pour changer la priorité', done: true },
      { text: 'Légende des couleurs', done: true },
    ],
    9
  ),
  seedTodo(
    'Ajouter des tags/catégories dev (bug, feature, refactor...)',
    true,
    'medium',
    ['feature'],
    [
      { text: '6 tags prédéfinis avec couleur', done: true },
      { text: 'Sélection multiple à la création', done: true },
      { text: 'Filtrage par tag', done: true },
    ],
    8
  ),
  seedTodo(
    'Lier une tâche à un repo / une branche / une PR',
    true,
    'low',
    ['feature'],
    [
      { text: 'Champ lien optionnel dans le formulaire', done: true },
      { text: 'Icône cliquable ouvrant le lien', done: true },
    ],
    7
  ),
  seedTodo(
    'Découper une tâche en sous-tâches (checklist imbriquée)',
    true,
    'high',
    ['feature'],
    [
      { text: 'Panneau dépliable par tâche', done: true },
      { text: 'Checkbox indépendante par sous-tâche', done: true },
      { text: 'Badge de progression', done: true },
    ],
    6
  ),
  seedTodo(
    'Sauvegarder les tâches dans le localStorage',
    true,
    'high',
    ['feature', 'refactor'],
    [
      { text: 'Chargement au démarrage avec valeur par défaut', done: true },
      { text: "effect() qui sauvegarde à chaque changement", done: true },
      { text: 'Migration des anciennes données sauvegardées', done: true },
    ],
    5
  ),
  seedTodo(
    'Ajouter une vue "Aujourd\'hui" / focus mode',
    true,
    'medium',
    ['feature'],
    [
      { text: 'Champ échéance optionnel', done: true },
      { text: 'Bouton de bascule vue du jour', done: true },
      { text: 'Badges retard / aujourd\'hui', done: true },
    ],
    4
  ),
  seedTodo(
    'Ajouter le tri et des filtres avancés',
    false,
    'medium',
    ['feature'],
    [
      { text: 'Tri par priorité', done: false },
      { text: "Tri par date d'échéance", done: false },
      { text: 'Tri par statut', done: false },
    ],
    3,
    3
  ),
  seedTodo(
    'Ajouter des raccourcis clavier',
    false,
    'low',
    ['feature'],
    [
      { text: 'Raccourci pour ouvrir le formulaire', done: false },
      { text: 'Navigation au clavier dans la liste', done: false },
      { text: 'Suppression rapide', done: false },
    ],
    2,
    10
  ),
  seedTodo(
    'Étendre le dashboard karma (streak, graphique)',
    false,
    'medium',
    ['feature', 'docs'],
    [
      { text: 'Suivi de streak de jours actifs', done: false },
      { text: 'Graphique hebdomadaire des tâches complétées', done: false },
    ],
    1,
    20
  ),
  seedTodo(
    'Ajouter une estimation de temps / Pomodoro',
    false,
    'low',
    ['feature'],
    [
      { text: "Champ d'estimation de durée", done: false },
      { text: 'Minuteur pomodoro pour la tâche en cours', done: false },
    ],
    5,
    35
  ),
];

/** Comble les champs manquants d'anciennes données sauvegardées avant l'ajout de createdAt/lastReminderAt. */
function normalizeTodo(todo: Partial<Todo>): Todo {
  const createdAt = todo.createdAt ?? todayIso();
  return {
    text: todo.text ?? '',
    done: todo.done ?? false,
    priority: todo.priority ?? 'medium',
    tags: todo.tags ?? [],
    link: todo.link ?? null,
    subtasks: todo.subtasks ?? [],
    expanded: todo.expanded ?? false,
    dueDate: todo.dueDate ?? null,
    createdAt,
    lastReminderAt: todo.lastReminderAt ?? createdAt,
  };
}

export function loadTodos(): Todo[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return DEFAULT_TODOS;
    const parsed = JSON.parse(raw) as Partial<Todo>[];
    return parsed.map(normalizeTodo);
  } catch {
    return DEFAULT_TODOS;
  }
}
