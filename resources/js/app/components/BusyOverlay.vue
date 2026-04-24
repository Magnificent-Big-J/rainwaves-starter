<template>
    <div v-if="active" class="busy-overlay">
        <v-progress-circular
            indeterminate
            color="primary"
            size="42"
            width="4"
        />
    </div>
</template>

<script setup>
defineProps({
    active: {
        type: Boolean,
        default: false,
    },
});
</script>

<style scoped>
.busy-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 253, 248, 0.72);
    backdrop-filter: blur(4px);
    border-radius: inherit;
    z-index: 3;
}
</style>
