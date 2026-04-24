<template>
    <component :is="activeLayout">
        <RouterView />
    </component>

    <AppToastHost />
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AppToastHost from './components/AppToastHost.vue';
import { useSessionStore } from './stores/session';

const layouts = import.meta.glob('./layouts/*.vue', {
    eager: true,
});

const route = useRoute();
const session = useSessionStore();

const activeLayout = computed(() => {
    const requested = route.meta.layout ?? 'default';
    const fallback = layouts['./layouts/default.vue']?.default;

    if (requested === 'contextual') {
        return session.activeSurface === 'admin'
            ? layouts['./layouts/default.vue']?.default ?? fallback
            : layouts['./layouts/customer.vue']?.default ?? fallback;
    }

    return layouts[`./layouts/${requested}.vue`]?.default ?? fallback;
});
</script>
