/** Build : npx tailwindcss@3 -i assets/css/input.css -o assets/css/site.css --minify */
module.exports = {
  content: ['./index.html'],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['"Space Grotesk"', 'Inter', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace']
      },
      colors: {
        ink: { 950: '#070a14', 900: '#0b1020', 800: '#121a30', 700: '#1c2643' }
      }
    }
  }
};
