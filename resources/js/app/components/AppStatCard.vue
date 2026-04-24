<template>
    <div class="stat-card">
        <div class="stat-card__top">
            <div class="stat-card__icon" :style="{ background: iconBackground }">
                <v-icon :icon="icon" :color="iconColor" size="18" />
            </div>
            <AppStatusBadge
                v-if="status"
                :status="status"
                :label="statusLabel"
                class="stat-card__status"
            />
        </div>

        <div class="stat-card__value">{{ value }}</div>
        <div class="stat-card__label">{{ label }}</div>
        <div v-if="helper" class="stat-card__helper">{{ helper }}</div>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    helper: { type: String, default: null },
    icon: { type: String, default: 'mdi-chart-arc' },
    iconColor: { type: String, default: 'primary' },
    iconBackground: { type: String, default: 'rgba(0, 106, 74, 0.08)' },
    status: { type: String, default: null },
    statusLabel: { type: String, default: null },
});
</script>

<style scoped>
.stat-card {
    display: grid;
    gap: 0.8rem;
    padding: 1.15rem 1.2rem;
    background: rgba(255, 253, 248, 0.96);
    border-radius: 1.1rem;
    border: 1px solid rgba(17, 34, 51, 0.08);
    box-shadow: 0 10px 30px rgba(17, 34, 51, 0.04);
}

.stat-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.stat-card__icon {
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 0.8rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.stat-card__value {
    font-size: 1.6rem;
    line-height: 1;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.stat-card__label {
    font-size: 0.86rem;
    font-weight: 700;
    color: rgba(17, 34, 51, 0.84);
}

.stat-card__helper {
    font-size: 0.8rem;
    color: var(--starter-muted, rgba(17, 34, 51, 0.7));
}
</style>
