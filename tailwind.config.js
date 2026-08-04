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
                // Paleta Oficial Vera AI
                vera: {
                    dark: '#0F172A',   // Azul Medianoche
                    green: '#10B981',  // Verde Menta
                    gray: '#64748B',   // Gris Pizarra
                    yellow: '#dbc358',   // Gris Pizarra
                }
            },
            // NUEVA SECCIÓN PARA ANIMACIONES:
            animation: {
                'text-shimmer': 'text-shimmer 2s ease-out infinite',
            },
            keyframes: {
                'text-shimmer': {
                    '0%': { backgroundPosition: '100% 50%' },
                    '100%': { backgroundPosition: '0% 20%' },
                }
            }
        },
    },

    plugins: [forms],
};