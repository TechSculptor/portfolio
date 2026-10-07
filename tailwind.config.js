/** Build : npx tailwindcss@3 -i assets/css/input.css -o assets/css/site.css */
const v = (name) => `rgb(var(--${name}) / <alpha-value>)`;

module.exports = {
  // Fichiers à scanner pour trouver les classes Tailwind utilisées
  content: ['./index.html', './mentions-legales.html'],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Montserrat', 'system-ui', 'sans-serif']
      },
      // Couleurs de structure pilotées par des variables CSS (voir assets/css/input.css) :
      // le mode clair / sombre ne fait que changer les variables. Les couleurs vives
      // (sky, violet, amber, emerald) viennent de la palette Tailwind.
      colors: {
        // canvas défini couleur de fond de page
        // accent : couleur d'accentuation
        // band : couleur de la bande
        // surface, line, fg, head, muted, gold, steel : couleurs de structure
        canvas: v('canvas'),
        surface: v('surface'),
        line: v('line'),
        fg: v('fg'),
        head: v('head'),
        muted: v('muted'),
        accent: v('accent'),
        band: v('band'),
        gold: v('gold'),
        steel: v('steel')
      }
    }
  }
};
