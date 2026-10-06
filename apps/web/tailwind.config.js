/** @type {import('tailwindcss').Config} */
const v = (name) => `rgb(var(--${name}) / <alpha-value>)`;
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        bg: v('bg'),
        panel: v('panel'),
        panel2: v('panel-2'),
        line: v('line'),
        fg: v('fg'),
        muted: v('muted'),
        accent: v('accent'),
        'accent-fg': v('accent-fg'),
        profit: v('profit'),
        loss: v('loss'),
        warn: v('warn'),
        info: v('info'),
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
      fontSize: { '2xs': ['0.6875rem', '1rem'] },
      keyframes: {
        ticker: { from: { transform: 'translateX(0)' }, to: { transform: 'translateX(-50%)' } },
        breathe: { '0%,100%': { transform: 'scale(0.75)', opacity: '0.6' }, '50%': { transform: 'scale(1)', opacity: '1' } },
      },
      animation: { ticker: 'ticker 60s linear infinite', breathe: 'breathe 8s ease-in-out infinite' },
    },
  },
  plugins: [],
};
