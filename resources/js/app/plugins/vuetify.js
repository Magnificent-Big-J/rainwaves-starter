import 'vuetify/styles';

import { createVuetify } from 'vuetify';

export const vuetify = createVuetify({
    theme: {
        defaultTheme: 'rainwavesStarter',
        themes: {
            rainwavesStarter: {
                dark: false,
                colors: {
                    background: '#f7f3ea',
                    surface: '#fffdf8',
                    primary: '#006a52',
                    secondary: '#d38b00',
                    accent: '#0e8b6c',
                    error: '#b42318',
                    info: '#0b7285',
                    success: '#157347',
                    warning: '#b7791f',
                },
            },
        },
    },
    defaults: {
        VBtn: {
            rounded: 'xl',
        },
        VCard: {
            rounded: 'xl',
            elevation: 0,
        },
    },
});
