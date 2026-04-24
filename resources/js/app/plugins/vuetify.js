import 'vuetify/styles';

import { createVuetify } from 'vuetify';

export const vuetify = createVuetify({
    theme: {
        defaultTheme: 'rw',
        themes: {
            rw: {
                dark: false,
                colors: {
                    background: '#f2efe8',
                    surface:    '#ffffff',
                    primary:    '#006a4a',
                    secondary:  '#b45309',
                    accent:     '#00875f',
                    error:      '#b91c1c',
                    info:       '#0369a1',
                    success:    '#15803d',
                    warning:    '#b45309',
                    'on-primary': '#ffffff',
                },
            },
        },
    },
    defaults: {
        VBtn: {
            rounded: 'lg',
            fontWeight: '600',
        },
        VCard: {
            rounded: 'xl',
            elevation: 0,
        },
        VTextField: {
            variant: 'outlined',
            density: 'comfortable',
            color: 'primary',
        },
        VSelect: {
            variant: 'outlined',
            density: 'comfortable',
            color: 'primary',
        },
        VCombobox: {
            variant: 'outlined',
            density: 'comfortable',
            color: 'primary',
        },
        VChip: {
            rounded: 'md',
        },
    },
});
