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
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                hpk: {
                    blue: '#0a2342',
                    teal: '#4a9e9e',
                    red: '#e11d48',
                    orange: '#f59e0b',
                    light: '#f0f4f8',
                    slate: '#f1f5f9',
                }
            }
        },
    },

    plugins: [forms],
};
