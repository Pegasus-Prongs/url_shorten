import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Steel Blue & Soft Mint SaaS palette
                primary: {
                    50: '#f0f8ff',
                    100: '#e0f1fe',
                    200: '#bae3fd',
                    300: '#7dd0fb',
                    400: '#38b9f7',
                    500: '#4682b4', // Steel Blue
                    600: '#3a6d96',
                    700: '#2f5a7a',
                    800: '#244760',
                    900: '#1a3447',
                },
                secondary: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0',
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#b2f2bb', // Soft Mint
                    600: '#8ee6a3',
                    700: '#6bd98b',
                    800: '#4ade80',
                    900: '#22c55e',
                },
                accent: {
                    50: '#fafafa', // Cool White
                    100: '#f5f5f5',
                    200: '#e5e5e5',
                    300: '#d4d4d4',
                    400: '#a3a3a3',
                    500: '#737373',
                    600: '#525252',
                    700: '#404040',
                    800: '#262626',
                    900: '#171717',
                },
            },
            backgroundImage: {
                // Steel Blue & Soft Mint gradient combinations
                'gradient-primary': 'linear-gradient(135deg, #4682b4 0%, #3a6d96 100%)',
                'gradient-secondary': 'linear-gradient(135deg, #b2f2bb 0%, #8ee6a3 100%)',
                'gradient-accent': 'linear-gradient(135deg, #4682b4 0%, #b2f2bb 100%)',
                'gradient-royal': 'linear-gradient(135deg, #4682b4 0%, #2f5a7a 100%)',
                'gradient-ocean': 'linear-gradient(135deg, #4682b4 0%, #b2f2bb 100%)',
                'gradient-sunset': 'linear-gradient(135deg, #b2f2bb 0%, #dcfce7 100%)',
                'gradient-fresh': 'linear-gradient(135deg, #b2f2bb 0%, #86efac 100%)',
                'gradient-deep': 'linear-gradient(135deg, #2f5a7a 0%, #1a3447 100%)',
                'gradient-hero': 'linear-gradient(135deg, #4682b4 0%, #b2f2bb 100%)',
                'gradient-card': 'linear-gradient(135deg, rgba(70, 130, 180, 0.1) 0%, rgba(178, 242, 187, 0.1) 100%)',
                'gradient-button': 'linear-gradient(135deg, #4682b4 0%, #3a6d96 100%)',
                'gradient-hover': 'linear-gradient(135deg, #3a6d96 0%, #2f5a7a 100%)',
            },
            boxShadow: {
                'modern': '0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                'modern-lg': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                'gradient': '0 10px 25px -3px rgba(70, 130, 180, 0.3), 0 4px 6px -2px rgba(178, 242, 187, 0.2)',
                'gradient-lg': '0 20px 25px -5px rgba(70, 130, 180, 0.3), 0 10px 10px -5px rgba(178, 242, 187, 0.2)',
            },
        },
    },

    plugins: [forms],
};
