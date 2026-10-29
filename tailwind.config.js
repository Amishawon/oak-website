/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,jsx}",
  ],
  theme: {
    extend: {
      colors: {
        forest: '#084f3c',
        mint: '#a9e4dc',
        ice: '#eaf4f6',
        muted: '#49665d',
      },
    },
  },
  plugins: [],
}
