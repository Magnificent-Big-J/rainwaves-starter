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

const layouts = import.meta.glob('./layouts/*.vue', {
    eager: true,
});

const route = useRoute();

const activeLayout = computed(() => {
    const requested = route.meta.layout ?? 'default';
    const fallback = layouts['./layouts/default.vue']?.default;

    return layouts[`./layouts/${requested}.vue`]?.default ?? fallback;
});
</script>
