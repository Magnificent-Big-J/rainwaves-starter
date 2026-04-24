<template>
    <AppSectionCard title="Recovery codes" subtitle="Store these somewhere safe — each can only be used once.">
        <div class="recovery-panel">
            <div class="recovery-panel__head">
                <p class="recovery-panel__copy">
                    These codes are your fallback if the authenticator device is unavailable.
                </p>
                <v-btn
                    variant="tonal"
                    size="small"
                    prepend-icon="mdi-refresh"
                    :loading="loading"
                    @click="$emit('regenerate')"
                >
                    Regenerate
                </v-btn>
            </div>

            <div class="recovery-panel__grid">
                <code
                    v-for="code in codes"
                    :key="code"
                    class="recovery-panel__code"
                >
                    {{ code }}
                </code>
            </div>
        </div>
    </AppSectionCard>
</template>

<script setup>
defineProps({
    codes: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

defineEmits(['regenerate']);
</script>

<style scoped>
.recovery-panel {
    display: grid;
    gap: 1rem;
}

.recovery-panel__head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.recovery-panel__copy {
    margin: 0;
    color: var(--starter-muted);
    line-height: 1.6;
    max-width: 44ch;
}

.recovery-panel__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
}

.recovery-panel__code {
    display: inline-flex;
    align-items: center;
    min-height: 2.7rem;
    padding: 0.75rem 0.85rem;
    border-radius: 0.9rem;
    background: rgba(17, 34, 51, 0.06);
    border: 1px solid rgba(17, 34, 51, 0.08);
    font-size: 0.88rem;
}
</style>
