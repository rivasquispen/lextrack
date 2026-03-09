import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Roboto', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: '#043965',
                'primary-light': '#0a4f8b',
                'primary-dark': '#02274d',
                accent: '#0fb7ff',
                muted: '#eef2f7',
            },
            boxShadow: {
                panel: '0 25px 50px -12px rgba(4, 57, 101, 0.15)',
            },
        },
    },

    plugins: [forms],
};
