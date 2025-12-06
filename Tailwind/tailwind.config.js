/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: "class",
  content: [
    "../*.{html,js,php}",
    "../include/**/*.{html,php}",
    "../pages/**/*.{html,php}",
    "../JavaScript/**/*.{html,js}",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ["SF Pro", "sans-serif"],
      },
    },
  },
  plugins: [],
};
