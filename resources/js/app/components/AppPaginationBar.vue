<template>
    <div class="pagination-bar">
        <div class="pagination-bar__summary">
            <span class="pagination-bar__count">{{ rangeLabel }}</span>
            <span class="pagination-bar__muted">{{ totalLabel }}</span>
        </div>

        <v-pagination
            :model-value="currentPage"
            :length="lastPage"
            density="compact"
            rounded="xl"
            :total-visible="visiblePages"
            @update:model-value="$emit('update:page', $event)"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    meta: { type: Object, required: true },
    visiblePages: { type: Number, default: 7 },
});

defineEmits(['update:page']);

const currentPage = computed(() => Number(props.meta?.current_page || 1));
const perPage = computed(() => Number(props.meta?.per_page || 0));
const total = computed(() => Number(props.meta?.total || 0));
const lastPage = computed(() => Math.max(Number(props.meta?.last_page || 1), 1));

const rangeLabel = computed(() => {
    if (!total.value || !perPage.value) {
        return 'No records';
    }

    const start = ((currentPage.value - 1) * perPage.value) + 1;
    const end = Math.min(currentPage.value * perPage.value, total.value);

    return `${start}-${end}`;
});

const totalLabel = computed(() => {
    if (!total.value) {
        return 'of 0';
    }

    return `of ${total.value}`;
});
</script>

<style scoped>
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.pagination-bar__summary {
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
}

.pagination-bar__count {
    font-size: 0.95rem;
    font-weight: 700;
}

.pagination-bar__muted {
    color: var(--starter-muted, rgba(17, 34, 51, 0.7));
    font-size: 0.88rem;
}
</style>
