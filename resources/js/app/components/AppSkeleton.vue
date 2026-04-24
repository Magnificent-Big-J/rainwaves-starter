<template>
    <div class="app-skeleton" :style="{ width, height }" />
</template>

<script setup>
defineProps({
    width: { type: String, default: '100%' },
    height: { type: String, default: '1rem' },
});
</script>

<style scoped>
.app-skeleton {
    border-radius: 0.75rem;
    background: linear-gradient(
        90deg,
        rgba(17, 34, 51, 0.06),
        rgba(17, 34, 51, 0.11),
        rgba(17, 34, 51, 0.06)
    );
    background-size: 200% 100%;
    animation: shimmer 1.4s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
