export const DOC_KNOWLEDGE_BASE: { keywords: string[]; label: string; url: string }[] = [
  { keywords: ['signal'], label: 'Angular — Signals', url: 'https://angular.dev/guide/signals' },
  { keywords: ['route', 'router'], label: 'Angular — Routing', url: 'https://angular.dev/guide/routing' },
  { keywords: ['form', 'ngmodel'], label: 'Angular — Forms', url: 'https://angular.dev/guide/forms' },
  { keywords: ['http', 'api', 'fetch'], label: 'Angular — HttpClient', url: 'https://angular.dev/guide/http' },
  { keywords: ['test', 'spec', 'jasmine'], label: 'Angular — Testing', url: 'https://angular.dev/guide/testing' },
  { keywords: ['css', 'flex', 'grid', 'style'], label: 'MDN — CSS', url: 'https://developer.mozilla.org/fr/docs/Web/CSS' },
  { keywords: ['git', 'branch', 'commit', 'merge'], label: 'Git — Documentation', url: 'https://git-scm.com/doc' },
  { keywords: ['typescript', 'type', 'interface'], label: 'TypeScript — Handbook', url: 'https://www.typescriptlang.org/docs/handbook/intro.html' },
];

export function fallbackSearchUrl(query: string): string {
  return `https://www.google.com/search?q=${encodeURIComponent(query + ' documentation')}`;
}
