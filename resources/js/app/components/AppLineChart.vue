<template>
    <div class="chart-card">
        <div class="chart-card__header">
            <div>
                <h3 class="chart-card__title">{{ title }}</h3>
                <p v-if="subtitle" class="chart-card__subtitle">{{ subtitle }}</p>
            </div>
            <slot name="actions" />
        </div>

        <apexchart
            type="line"
            height="100%"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    colors: { type: Array, default: () => ['#006a4a', '#b45309'] },
});

const chartOptions = computed(() => ({
    chart: {
        id: `line-${props.title}`,
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit',
    },
    colors: props.colors,
    stroke: {
        curve: 'smooth',
        width: 3,
    },
    grid: {
        borderColor: 'rgba(17, 34, 51, 0.08)',
        strokeDashArray: 4,
    },
    xaxis: {
        categories: props.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: 'rgba(17, 34, 51, 0.6)' },
        },
    },
    yaxis: {
        labels: {
            style: { colors: 'rgba(17, 34, 51, 0.6)' },
        },
    },
    tooltip: {
        theme: 'light',
    },
    legend: {
        show: true,
        position: 'top',
        horizontalAlign: 'left',
    },
    markers: {
        size: 4,
        strokeWidth: 0,
    },
}));
</script>

<style scoped>
.chart-card {
    display: grid;
    gap: 1rem;
    min-height: 320px;
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
    color: var(--rw-muted);
    font-size: 0.85rem;
}
</style>
