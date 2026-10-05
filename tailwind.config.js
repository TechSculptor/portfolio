/** Build : npx tailwindcss@3 -i assets/css/input.css -o assets/css/site.css --minify */
const v = (name) => `rgb(var(--${name}) / <alpha-value>)`;

module.exports = {
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
        canvas: v('canvas'),
        surface: v('surface'),
        line: v('line'),
        fg: v('fg'),
        head: v('head'),
        muted: v('muted'),
        accent: v('accent'),
        band: v('band')
      }
    }
  }
};
