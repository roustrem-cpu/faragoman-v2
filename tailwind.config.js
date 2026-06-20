/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/views/**/*.php',
    './public/assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        ink: {
          900: '#0b1020',
          800: '#11182e',
          700: '#1a2240',
        },
        brand: {
          400: '#7c9cff',
          500: '#5b7cfa',
          600: '#4861e6',
        },
        accent: '#22d3ee',
      },
      fontFamily: {
        sans: ['Vazirmatn', 'system-ui', 'Tahoma', 'sans-serif'],
      },
      borderRadius: {
        xl2: '1.25rem',
      },
      boxShadow: {
        // Pseudo-3D layered shadows.
        soft: '0 1px 2px rgba(8,12,28,.20), 0 8px 24px rgba(8,12,28,.18)',
        lift: '0 12px 40px rgba(8,12,28,.35), inset 0 1px 0 rgba(255,255,255,.06)',
        glow: '0 0 0 1px rgba(124,156,255,.25), 0 10px 40px rgba(91,124,250,.35)',
      },
      keyframes: {
        floaty: {
          '0%,100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-6px)' },
        },
      },
      animation: {
        floaty: 'floaty 6s ease-in-out infinite',
      },
    },
  },
  plugins: [],
};
