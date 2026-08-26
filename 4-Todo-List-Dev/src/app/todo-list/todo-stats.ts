import { Signal, computed } from '@angular/core';
import type { Tag, Todo } from './todo-list.models';
import { todayIso } from './todo-list.date-utils';

/**
 * Regroupe toutes les statistiques dérivées de la liste de tâches, en vue du futur
 * dashboard karma étendu (tâche 9). Rien n'est encore branché au template.
 *
 * `isOverdue`/`isDueToday` sont injectées plutôt que réimplémentées ici, pour ne
 * jamais diverger de la logique utilisée ailleurs dans le composant.
 */
export function createTodoStats(
  todos: Signal<Todo[]>,
  isOverdue: (todo: Todo) => boolean,
  isDueToday: (todo: Todo) => boolean,
  remindersDueCount: () => number
) {
  return {
    totalTodos: computed(() => todos().length),
    completedTodos: computed(() => todos().filter(t => t.done).length),
    pendingTodos: computed(() => todos().filter(t => !t.done).length),
    overdueTodos: computed(() => todos().filter(t => isOverdue(t)).length),
    dueTodayTodos: computed(() => todos().filter(t => isDueToday(t)).length),

    totalSubtasks: computed(() => todos().reduce((sum, t) => sum + t.subtasks.length, 0)),
    completedSubtasks: computed(() => todos().reduce((sum, t) => sum + t.subtasks.filter(s => s.done).length, 0)),
    pendingSubtasks: computed(() => todos().reduce((sum, t) => sum + t.subtasks.filter(s => !s.done).length, 0)),
    overdueSubtasks: computed(() =>
      todos().reduce((sum, t) => sum + t.subtasks.filter(s => !s.done && isOverdue(t)).length, 0)
    ),
    dueTodaySubtasks: computed(() =>
      todos().reduce((sum, t) => sum + t.subtasks.filter(s => !s.done && isDueToday(t)).length, 0)
    ),

    totalTags: computed(() => {
      const tagSet = new Set<Tag>();
      todos().forEach(todo => todo.tags.forEach(tag => tagSet.add(tag)));
      return tagSet.size;
    }),

    totalLinks: computed(() => todos().filter(t => t.link).length),
    totalRemindersDue: computed(() => remindersDueCount()),

    totalSubtasksCompleted: computed(() => todos().reduce((sum, t) => sum + t.subtasks.filter(s => s.done).length, 0)),
    totalSubtasksPending: computed(() => todos().reduce((sum, t) => sum + t.subtasks.filter(s => !s.done).length, 0)),
    totalSubtasksOverdue: computed(() =>
      todos().reduce((sum, t) => sum + t.subtasks.filter(s => !s.done && isOverdue(t)).length, 0)
    ),
    totalSubtasksDueToday: computed(() =>
      todos().reduce((sum, t) => sum + t.subtasks.filter(s => !s.done && isDueToday(t)).length, 0)
    ),

    totalTasksWithSubtasks: computed(() => todos().filter(t => t.subtasks.length > 0).length),
    totalTasksWithoutSubtasks: computed(() => todos().filter(t => t.subtasks.length === 0).length),

    totalTasksWithLinks: computed(() => todos().filter(t => t.link !== null).length),
    totalTasksWithoutLinks: computed(() => todos().filter(t => t.link === null).length),

    totalTasksWithTags: computed(() => todos().filter(t => t.tags.length > 0).length),
    totalTasksWithoutTags: computed(() => todos().filter(t => t.tags.length === 0).length),

    totalTasksOverdue: computed(() => todos().filter(t => !t.done && t.dueDate !== null && t.dueDate < todayIso()).length),
    totalTasksDueToday: computed(() => todos().filter(t => !t.done && t.dueDate === todayIso()).length),
    totalTasksDueLater: computed(() => todos().filter(t => !t.done && t.dueDate !== null && t.dueDate > todayIso()).length),
    totalTasksWithoutDueDate: computed(() => todos().filter(t => !t.done && t.dueDate === null).length),

    totalTasksCompleted: computed(() => todos().filter(t => t.done).length),
    totalTasksPending: computed(() => todos().filter(t => !t.done).length),

    totalTasksHighPriority: computed(() => todos().filter(t => t.priority === 'high').length),
    totalTasksMediumPriority: computed(() => todos().filter(t => t.priority === 'medium').length),
    totalTasksLowPriority: computed(() => todos().filter(t => t.priority === 'low').length),

    totalTasksWithSubtasksCompleted: computed(() =>
      todos().filter(t => t.subtasks.length > 0 && t.subtasks.every(s => s.done)).length
    ),
    totalTasksWithSubtasksPending: computed(() =>
      todos().filter(t => t.subtasks.length > 0 && t.subtasks.some(s => !s.done)).length
    ),

    totalTasksWithLinksCompleted: computed(() => todos().filter(t => t.link !== null && t.done).length),
    totalTasksWithLinksPending: computed(() => todos().filter(t => t.link !== null && !t.done).length),

    totalTasksWithTagsCompleted: computed(() => todos().filter(t => t.tags.length > 0 && t.done).length),
    totalTasksWithTagsPending: computed(() => todos().filter(t => t.tags.length > 0 && !t.done).length),

    totalTasksOverdueCompleted: computed(() =>
      todos().filter(t => !t.done && t.dueDate !== null && t.dueDate < todayIso()).length
    ),
    totalTasksDueTodayCompleted: computed(() => todos().filter(t => !t.done && t.dueDate === todayIso()).length),
    totalTasksDueLaterCompleted: computed(() =>
      todos().filter(t => !t.done && t.dueDate !== null && t.dueDate > todayIso()).length
    ),
    totalTasksWithoutDueDateCompleted: computed(() => todos().filter(t => !t.done && t.dueDate === null).length),

    totalTasksCompletedWithSubtasks: computed(() => todos().filter(t => t.done && t.subtasks.length > 0).length),
    totalTasksPendingWithSubtasks: computed(() => todos().filter(t => !t.done && t.subtasks.length > 0).length),

    totalTasksCompletedWithLinks: computed(() => todos().filter(t => t.done && t.link !== null).length),
    totalTasksPendingWithLinks: computed(() => todos().filter(t => !t.done && t.link !== null).length),

    totalTasksCompletedWithTags: computed(() => todos().filter(t => t.done && t.tags.length > 0).length),
    totalTasksPendingWithTags: computed(() => todos().filter(t => !t.done && t.tags.length > 0).length),

    totalTasksOverdueCompletedWithSubtasks: computed(() =>
      todos().filter(t => !t.done && t.dueDate !== null && t.dueDate < todayIso() && t.subtasks.length > 0).length
    ),
    totalTasksDueTodayCompletedWithSubtasks: computed(() =>
      todos().filter(t => !t.done && t.dueDate === todayIso() && t.subtasks.length > 0).length
    ),
    totalTasksDueLaterCompletedWithSubtasks: computed(() =>
      todos().filter(t => !t.done && t.dueDate !== null && t.dueDate > todayIso() && t.subtasks.length > 0).length
    ),
    totalTasksWithoutDueDateCompletedWithSubtasks: computed(() =>
      todos().filter(t => !t.done && t.dueDate === null && t.subtasks.length > 0).length
    ),

    totalTasksCompletedWithLinksAndSubtasks: computed(() =>
      todos().filter(t => t.done && t.link !== null && t.subtasks.length > 0).length
    ),
    totalTasksPendingWithLinksAndSubtasks: computed(() =>
      todos().filter(t => !t.done && t.link !== null && t.subtasks.length > 0).length
    ),

    totalTasksCompletedWithTagsAndSubtasks: computed(() =>
      todos().filter(t => t.done && t.tags.length > 0 && t.subtasks.length > 0).length
    ),
    totalTasksPendingWithTagsAndSubtasks: computed(() =>
      todos().filter(t => !t.done && t.tags.length > 0 && t.subtasks.length > 0).length
    ),

    totalTasksOverdueCompletedWithLinks: computed(() =>
      todos().filter(t => !t.done && t.dueDate !== null && t.dueDate < todayIso() && t.link !== null).length
    ),
    totalTasksDueTodayCompletedWithLinks: computed(() =>
      todos().filter(t => !t.done && t.dueDate === todayIso() && t.link !== null).length
    ),
    totalTasksDueLaterCompletedWithLinks: computed(() =>
      todos().filter(t => !t.done && t.dueDate !== null && t.dueDate > todayIso() && t.link !== null).length
    ),
  };
}
