<template>
    <div class="chart-card chart-card--donut">
        <div class="chart-card__header">
            <div>
                <h3 class="chart-card__title">{{ title }}</h3>
                <p v-if="subtitle" class="chart-card__subtitle">{{ subtitle }}</p>
            </div>
            <slot name="actions" />
        </div>

        <div class="chart-card__body">
            <apexchart
                type="donut"
                height="240"
                :options="chartOptions"
                :series="series"
            />

            <div class="chart-card__legend">
                <div
                    v-for="(label, index) in labels"
                    :key="label"
                    class="chart-card__legend-item"
                >
                    <span class="chart-card__swatch" :style="{ background: colors[index] || '#006a4a' }" />
                    <span>{{ label }}</span>
                    <strong>{{ series[index] ?? 0 }}</strong>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    labels: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    colors: { type: Array, default: () => ['#006a4a', '#b45309', '#0369a1', '#9f1239'] },
});

const chartOptions = computed(() => ({
    chart: {
        id: `donut-${props.title}`,
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    labels: props.labels,
    colors: props.colors,
    stroke: {
        width: 0,
    },
    legend: {
        show: false,
    },
    dataLabels: {
        enabled: true,
        formatter: (value) => `${Math.round(value)}%`,
    },
    plotOptions: {
        pie: {
            donut: {
                size: '68%',
            },
        },
    },
    tooltip: {
        theme: 'light',
    },
}));
</script>

<style scoped>
.chart-card {
    display: grid;
    gap: 1rem;
    padding: 1.15rem 1.2rem;
    background: rgba(255, 253, 248, 0.96);
    border-radius: 1.1rem;
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.chart-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.chart-card__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
}

.chart-card__subtitle {
    margin: 0.35rem 0 0;
    color: var(--starter-muted, rgba(17, 34, 51, 0.7));
    font-size: 0.85rem;
}

.chart-card__body {
    display: grid;
    align-items: center;
    grid-template-columns: minmax(0, 1fr) minmax(180px, 220px);
    gap: 1rem;
}

.chart-card__legend {
    display: grid;
    gap: 0.75rem;
}

.chart-card__legend-item {
    display: grid;
    grid-template-columns: 0.75rem minmax(0, 1fr) auto;
    gap: 0.65rem;
    align-items: center;
    font-size: 0.9rem;
}

.chart-card__swatch {
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 999px;
}

@media (max-width: 900px) {
    .chart-card__body {
        grid-template-columns: 1fr;
    }
}
</style>
