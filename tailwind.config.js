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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                gold: {
                    50: '#fbf7e9',
                    100: '#f5ecc7',
                    200: '#ead68f',
                    300: '#dcba57',
                    400: '#cc9f34',
                    500: '#b3841f',
                    600: '#966b17',
                    700: '#7a5717',
                    800: '#654819',
                    900: '#553d1a',
                },
            },
        },
    },

    plugins: [forms],
};
