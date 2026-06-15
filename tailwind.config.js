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
            colors: {
                brand: {
                    navy: '#003F73',
                    ink: '#071F45',
                    gold: '#FDB33A',
                    amber: '#FFB030',
                    cream: '#FFF8E8',
                    mist: '#F6F8FB',
                    line: '#DCE5EF',
                },
                orange: {
                    50: '#FFF8E8',
                    100: '#FFF0CC',
                    200: '#FFE19A',
                    300: '#FFD16A',
                    400: '#FFBE44',
                    500: '#FDB33A',
                    600: '#E49A20',
                    700: '#B97716',
                    800: '#935D16',
                    900: '#784D16',
                    950: '#442708',
                },
            },
            fontFamily: {
                sans: ['DINNextLTArabic-Regular-2', 'Inter', 'Tajawal', 'ui-sans-serif', 'system-ui', '-apple-system', ...defaultTheme.fontFamily.sans],
            },
            transitionTimingFunction: {
                premium: 'cubic-bezier(0.2, 0.8, 0.2, 1)',
                productive: 'cubic-bezier(0.16, 1, 0.3, 1)',
            },
            boxShadow: {
                apple: '0 18px 45px rgba(7, 31, 69, 0.10)',
                'apple-sm': '0 8px 24px rgba(7, 31, 69, 0.08)',
                'inner-soft': 'inset 0 1px 0 rgba(255, 255, 255, 0.65)',
            },
            borderRadius: {
                apple: '1.25rem',
            },
        },
    },

    plugins: [forms],
};
