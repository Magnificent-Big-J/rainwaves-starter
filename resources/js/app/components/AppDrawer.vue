<template>
    <v-navigation-drawer
        :model-value="modelValue"
        :location="location"
        :width="width"
        temporary
        class="app-drawer"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <div class="app-drawer__header">
            <div>
                <h3 v-if="title" class="app-drawer__title">{{ title }}</h3>
                <p v-if="subtitle" class="app-drawer__subtitle">{{ subtitle }}</p>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="$emit('update:modelValue', false)" />
        </div>
        <div class="app-drawer__body">
            <slot />
        </div>
    </v-navigation-drawer>
</template>

<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: null },
    subtitle: { type: String, default: null },
    width: { type: [Number, String], default: 380 },
    location: { type: String, default: 'right' },
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
.app-drawer {
    border-left: 1px solid rgba(17, 34, 51, 0.08);
}

.app-drawer__header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid rgba(17, 34, 51, 0.06);
}

.app-drawer__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
}

.app-drawer__subtitle {
    margin: 0.3rem 0 0;
    font-size: 0.85rem;
    color: var(--starter-muted);
}

.app-drawer__body {
    padding: 1rem;
    display: grid;
    gap: 1rem;
}
</style>
