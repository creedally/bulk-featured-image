/** @type {import('tailwindcss').Config} */
module.exports = {
    prefix: 'bfi-', 
    content: [
        './**/*.php',
        './assets/js/**/*.js',
        './assets/css/**/*.css',
    ],
    theme: {
        extend: {},
    },
    corePlugins: {
        preflight: false, 
    },
    plugins: [],
};