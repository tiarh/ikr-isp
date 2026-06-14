/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.tsx',
        './resources/**/*.ts',
        './app/Filament/**/*.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe',
                    300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6',
                    600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af',
                },
                status: {
                    draft:        '#9ca3af',
                    submitted:    '#3b82f6',
                    coverage_ok:  '#06b6d4',
                    assigned:     '#eab308',
                    provisioning: '#f97316',
                    photos:       '#a855f7',
                    done:         '#10b981',
                    rejected:     '#ef4444',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
