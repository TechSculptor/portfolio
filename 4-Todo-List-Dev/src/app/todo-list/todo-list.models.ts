export type Priority = 'low' | 'medium' | 'high';
export type Tag = 'bug' | 'feature' | 'refactor' | 'review' | 'meeting' | 'docs';
export type SortBy = 'priority' | 'dueDate' | 'status';

export interface Subtask {
  text: string;
  done: boolean;
}

export interface Todo {
  text: string;
  done: boolean;
  priority: Priority;
  tags: Tag[];
  link: string | null;
  subtasks: Subtask[];
  expanded: boolean;
  dueDate: string | null;
  createdAt: string;
  lastReminderAt: string;
}

export interface DocSuggestion {
  label: string;
  url: string;
}

export const PRIORITY_CYCLE: Priority[] = ['low', 'medium', 'high'];

export const PRIORITY_RANKS: Record<Priority, number> = {
  low: 1,
  medium: 2,
  high: 3,
};

export const TAG_META: Record<Tag, { label: string; color: string }> = {
  bug: { label: '🐛 Bug', color: '#eb5757' },
  feature: { label: '✨ Feature', color: '#2f80ed' },
  refactor: { label: '♻️ Refactor', color: '#9b51e0' },
  review: { label: '👀 Review', color: '#219653' },
  meeting: { label: '🗓️ Meeting', color: '#828282' },
  docs: { label: '📝 Docs', color: '#f2c94c' },
};
