import type { Todo } from './todo-list.models';

export function todayIso(): string {
  const now = new Date();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${now.getFullYear()}-${month}-${day}`;
}

export function daysAgoIso(days: number): string {
  const date = new Date();
  date.setDate(date.getDate() - days);
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${date.getFullYear()}-${month}-${day}`;
}

export function daysBetween(fromIso: string, toIso: string): number {
  const [fy, fm, fd] = fromIso.split('-').map(Number);
  const [ty, tm, td] = toIso.split('-').map(Number);
  const fromUtc = Date.UTC(fy, fm - 1, fd);
  const toUtc = Date.UTC(ty, tm - 1, td);
  return Math.round((toUtc - fromUtc) / (1000 * 60 * 60 * 24));
}

export function formatDueDate(iso: string): string {
  const [year, month, day] = iso.split('-').map(Number);
  return new Date(year, month - 1, day).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
}

export function isOverdue(todo: Todo, today: string): boolean {
  return !!todo.dueDate && !todo.done && todo.dueDate < today;
}

export function isDueToday(todo: Todo, today: string): boolean {
  return todo.dueDate === today;
}

/**
 * Intervalle de rappel (en jours) selon la distance à l'échéance :
 * - pas d'échéance : rappel régulier pour inciter à en fixer une
 * - échéance lointaine (> 30 jours) : rappels plus fréquents pour ne pas l'oublier
 * - échéance proche (≤ 7 jours) : pas de rappel générique, le badge "en retard/aujourd'hui" suffit
 */
export function reminderIntervalDays(todo: Todo, today: string): number | null {
  if (!todo.dueDate) return 7;
  const daysUntilDue = daysBetween(today, todo.dueDate);
  if (daysUntilDue > 30) return 3;
  if (daysUntilDue > 7) return 5;
  return null;
}
