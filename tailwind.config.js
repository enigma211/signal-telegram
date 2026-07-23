/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                ink: {
                    950: '#061018',
                    900: '#0A1620',
                    800: '#132433',
                    700: '#1C3144',
                    100: '#D7E2EA',
                },
                tide: {
                    200: '#B7E8DF',
                    300: '#7DD3C7',
                    400: '#4DB8A8',
                    500: '#2A9B8A',
                    600: '#1F7A6C',
                },
                mist: {
                    50: '#F3F7F8',
                    100: '#E6EEF1',
                    200: '#CDDCE2',
                },
                ember: {
                    400: '#F0A06A',
                    500: '#E07A3D',
                },
                sea: {
                    50: '#F2F8F7',
                    900: '#0E2A2A',
                    950: '#071A1A',
                },
            },
            fontFamily: {
                display: ['Vazirmatn', 'Vazir', 'Tahoma', 'sans-serif'],
                sans: ['Vazirmatn', 'Vazir', 'Tahoma', 'sans-serif'],
                enDisplay: ['Fraunces', 'Georgia', 'serif'],
                enSans: ['Outfit', 'system-ui', 'sans-serif'],
            },
            backgroundImage: {
                'hero-en':
                    'radial-gradient(ellipse 90% 70% at 80% 20%, rgba(42,155,138,0.28), transparent 55%), radial-gradient(ellipse 60% 50% at 10% 90%, rgba(224,122,61,0.12), transparent 50%), linear-gradient(165deg, #F3F7F8 0%, #E6EEF1 40%, #CDDCE2 100%)',
                'hero-fa':
                    'radial-gradient(ellipse 70% 55% at 20% 30%, rgba(125,211,199,0.25), transparent 50%), radial-gradient(ellipse 50% 40% at 90% 80%, rgba(42,155,138,0.18), transparent 45%), linear-gradient(200deg, #061018 0%, #0A1620 45%, #132433 100%)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(22px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'draw-line': {
                    '0%': { strokeDashoffset: '1400' },
                    '100%': { strokeDashoffset: '0' },
                },
                'drift': {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
                'glow-line': {
                    '0%, 100%': { opacity: '0.35' },
                    '50%': { opacity: '0.9' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.9s cubic-bezier(0.22,1,0.36,1) both',
                'fade-up-delay': 'fade-up 0.9s cubic-bezier(0.22,1,0.36,1) 0.12s both',
                'fade-up-delay-2': 'fade-up 0.9s cubic-bezier(0.22,1,0.36,1) 0.24s both',
                'draw-line': 'draw-line 2.8s ease-out forwards',
                'drift': 'drift 7s ease-in-out infinite',
                'glow-line': 'glow-line 5s ease-in-out infinite',
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
};
