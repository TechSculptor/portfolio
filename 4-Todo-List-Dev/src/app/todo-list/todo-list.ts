import { Component, computed, effect, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import type { DocSuggestion, Priority, SortBy, Subtask, Tag, Todo } from './todo-list.models';
import { PRIORITY_CYCLE, PRIORITY_RANKS, TAG_META } from './todo-list.models';
import { daysBetween, formatDueDate, reminderIntervalDays, todayIso } from './todo-list.date-utils';
import { isDueToday as checkDueToday, isOverdue as checkOverdue } from './todo-list.date-utils';
import { DOC_KNOWLEDGE_BASE, fallbackSearchUrl } from './todo-list.doc-search';
import { STORAGE_KEY, loadTodos } from './todo-list.seed-data';
import { createTodoStats } from './todo-stats';

@Component({
  selector: 'app-todo-list',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './todo-list.html',
  styleUrls: ['./todo-list.css', './todo-list-popup.css', './todo-list-subtasks.css']
})
export class TodoListComponent {
  readonly tagMeta = TAG_META;
  readonly allTags = Object.keys(TAG_META) as Tag[];
  readonly docKnowledgeBase = DOC_KNOWLEDGE_BASE;
  readonly taskExamples = [
    'Apprendre les Signals Angular',
    'Configurer le routing de l\'application',
    'Écrire des tests unitaires pour le service',
    'Nettoyer la mise en page CSS en flexbox',
    'Créer une branche Git pour la feature',
    'Revoir les types TypeScript du modèle',
  ];

  todos = signal<Todo[]>(loadTodos());
  newTodo = signal('');
  newPriority = signal<Priority>('medium');
  newTags = signal<Tag[]>([]);
  newLink = signal('');
  newDueDate = signal('');
  activeTagFilter = signal<Tag | null>(null);
  showTodayOnly = signal(false);
  showAddForm = signal(false);
  searchingDocs = signal(false);
  docSuggestions = signal<DocSuggestion[]>([]);
  showHelp = signal(false);
  private now = signal(todayIso());
  sortBy = signal<SortBy>('priority');

  readonly sortOptions: { value: SortBy; label: string }[] = [
    { value: 'priority', label: 'Priorité' },
    { value: 'dueDate', label: 'Échéance' },
    { value: 'status', label: 'Statut' },
  ];

  readonly stats = createTodoStats(
    this.todos,
    t => this.isOverdue(t),
    t => this.isDueToday(t),
    () => this.remindersDue().length
  );

  karma = computed(() => this.todos().filter(t => t.done).length * 10);

  remindersDue = computed(() => {
    const today = this.now();
    return this.todos().filter(todo => {
      if (todo.done) return false;
      const interval = reminderIntervalDays(todo, today);
      if (interval === null) return false;
      return daysBetween(todo.lastReminderAt, today) >= interval;
    });
  });

  filteredTodos = computed(() => {
    let list = this.todos();

    const tagFilter = this.activeTagFilter();
    if (tagFilter) {
      list = list.filter(t => t.tags.includes(tagFilter));
    }

    if (this.showTodayOnly()) {
      const today = todayIso();
      list = list.filter(t => t.dueDate !== null && t.dueDate <= today);
    }

    switch (this.sortBy()) {
      case 'priority':
        list = [...list].sort((a, b) => PRIORITY_RANKS[a.priority] - PRIORITY_RANKS[b.priority]);
        break;
      case 'dueDate':
        list = [...list].sort((a, b) => {
          if (a.dueDate && b.dueDate) {
            return a.dueDate.localeCompare(b.dueDate);
          } else if (a.dueDate) {
            return -1;
          } else if (b.dueDate) {
            return 1;
          }
          return 0;
        });
        break;
      case 'status':
        list = [...list].sort((a, b) => Number(a.done) - Number(b.done));
        break;
    }
    return list;
  });

  readonly formatDate = formatDueDate;

  constructor() {
    effect(() => {
      const todos = this.todos();
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(todos));
      } catch {
        // Stockage plein ou indisponible (ex: navigation privée) : on ignore silencieusement.
      }
    });

    // Recalcule les rappels dus toutes les heures, pour un onglet resté ouvert sans être rafraîchi.
    setInterval(() => this.now.set(todayIso()), 60 * 60 * 1000);
  }

  dismissReminder(todo: Todo) {
    this.todos.update(list => list.map(t => (t === todo ? { ...t, lastReminderAt: this.now() } : t)));
  }

  toggleTodayView() {
    this.showTodayOnly.update(v => !v);
  }

  isOverdue(todo: Todo): boolean {
    return checkOverdue(todo, todayIso());
  }

  isDueToday(todo: Todo): boolean {
    return checkDueToday(todo, todayIso());
  }

  openAddForm() {
    this.showAddForm.set(true);
  }

  closeAddForm() {
    this.showAddForm.set(false);
    this.newTodo.set('');
    this.newPriority.set('medium');
    this.newTags.set([]);
    this.newLink.set('');
    this.newDueDate.set('');
    this.docSuggestions.set([]);
    this.searchingDocs.set(false);
    this.showHelp.set(false);
  }

  toggleHelp() {
    this.showHelp.update(v => !v);
  }

  useExample(text: string) {
    this.newTodo.set(text);
    this.showHelp.set(false);
  }

  suggestDocs() {
    const query = this.newTodo().trim();
    if (!query) return;

    this.searchingDocs.set(true);
    this.docSuggestions.set([]);

    // Simule l'appel à un agent IA de recherche documentaire (à brancher plus tard sur une vraie API côté serveur).
    setTimeout(() => {
      const queryLower = query.toLowerCase();
      const matches = DOC_KNOWLEDGE_BASE.filter(entry =>
        entry.keywords.some(keyword => queryLower.includes(keyword))
      );
      const suggestions: DocSuggestion[] =
        matches.length > 0
          ? matches.map(({ label, url }) => ({ label, url }))
          : [{ label: `Rechercher "${query}" sur le web`, url: fallbackSearchUrl(query) }];

      this.docSuggestions.set(suggestions);
      this.searchingDocs.set(false);
    }, 700);
  }

  useSuggestion(url: string) {
    this.newLink.set(url);
  }

  addTodo() {
    if (this.newTodo().trim()) {
      const now = todayIso();
      this.todos.update(list => [
        ...list,
        {
          text: this.newTodo().trim(),
          done: false,
          priority: this.newPriority(),
          tags: this.newTags(),
          link: this.newLink().trim() || null,
          subtasks: [],
          expanded: false,
          dueDate: this.newDueDate() || null,
          createdAt: now,
          lastReminderAt: now,
        },
      ]);
      this.closeAddForm();
    }
  }

  toggleTagFilter(tag: Tag) {
    this.activeTagFilter.update(current => (current === tag ? null : tag));
  }

  toggleTodo(todo: Todo) {
    this.todos.update(list => list.map(t => (t === todo ? { ...t, done: !t.done } : t)));
  }

  cyclePriority(todo: Todo) {
    this.todos.update(list =>
      list.map(t => {
        if (t !== todo) return t;
        const next = PRIORITY_CYCLE[(PRIORITY_CYCLE.indexOf(t.priority) + 1) % PRIORITY_CYCLE.length];
        return { ...t, priority: next };
      })
    );
  }

  removeTodo(todo: Todo) {
    this.todos.update(list => list.filter(t => t !== todo));
  }

  toggleExpand(todo: Todo) {
    this.todos.update(list => list.map(t => (t === todo ? { ...t, expanded: !t.expanded } : t)));
  }

  addSubtask(todo: Todo, text: string) {
    const trimmed = text.trim();
    if (!trimmed) return;
    this.todos.update(list =>
      list.map(t => (t === todo ? { ...t, subtasks: [...t.subtasks, { text: trimmed, done: false }] } : t))
    );
  }

  toggleSubtask(todo: Todo, subtask: Subtask) {
    this.todos.update(list =>
      list.map(t =>
        t === todo
          ? { ...t, subtasks: t.subtasks.map(s => (s === subtask ? { ...s, done: !s.done } : s)) }
          : t
      )
    );
  }

  removeSubtask(todo: Todo, subtask: Subtask) {
    this.todos.update(list =>
      list.map(t => (t === todo ? { ...t, subtasks: t.subtasks.filter(s => s !== subtask) } : t))
    );
  }

  subtaskProgress(todo: Todo): string {
    const done = todo.subtasks.filter(s => s.done).length;
    return `${done}/${todo.subtasks.length}`;
  }
}
