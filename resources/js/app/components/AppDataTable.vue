<template>
    <div class="data-table">
        <div v-if="$slots.toolbar || searchable || title" class="data-table__toolbar">
            <div class="data-table__title" v-if="title">
                <span>{{ title }}</span>
                <v-chip v-if="meta" size="small" variant="tonal" color="primary">
                    {{ meta.total ?? rows.length }}
                </v-chip>
            </div>
            <v-spacer />
            <v-text-field
                v-if="searchable"
                v-model="searchQuery"
                density="compact"
                variant="outlined"
                placeholder="Search…"
                prepend-inner-icon="mdi-magnify"
                clearable
                hide-details
                class="data-table__search"
                @update:model-value="onSearch"
            />
            <slot name="toolbar" />
        </div>

        <v-table density="comfortable" class="data-table__table">
            <thead>
                <tr>
                    <th v-for="col in columns" :key="col.key" :class="col.class">
                        {{ col.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="loading">
                    <td :colspan="columns.length" class="data-table__loader">
                        <v-progress-linear indeterminate color="primary" />
                    </td>
                </tr>
                <tr v-else-if="!rows.length">
                    <td :colspan="columns.length" class="data-table__empty">
                        {{ emptyText }}
                    </td>
                </tr>
                <tr
                    v-for="row in rows"
                    :key="row.id ?? row"
                    class="data-table__row"
                    @click="$emit('row-click', row)"
                >
                    <slot name="row" :row="row" />
                </tr>
            </tbody>
        </v-table>

        <div v-if="meta && meta.last_page > 1" class="data-table__pagination">
            <v-pagination
                :model-value="meta.current_page"
                :length="meta.last_page"
                density="compact"
                rounded="xl"
                @update:model-value="$emit('page-change', $event)"
            />
            <span class="data-table__count">
                {{ meta.total }} total
            </span>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    title: { type: String, default: null },
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    searchable: { type: Boolean, default: false },
    emptyText: { type: String, default: 'No records found.' },
});

const emit = defineEmits(['search', 'page-change', 'row-click']);

const searchQuery = ref('');

let searchTimer = null;

const onSearch = (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => emit('search', val ?? ''), 300);
};
</script>

<style scoped>
.data-table {
    border-radius: 1.25rem;
    overflow: hidden;
    border: 1px solid rgba(17, 34, 51, 0.08);
    background: rgba(255, 253, 248, 0.9);
}

.data-table__toolbar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem 0.75rem;
    flex-wrap: wrap;
}

.data-table__title {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 700;
    font-size: 1rem;
}

.data-table__search {
    max-width: 280px;
    flex: 1 1 auto;
}

.data-table__table {
    background: transparent;
}

.data-table__loader {
    padding: 0 !important;
}

.data-table__empty {
    padding: 2.5rem !important;
    text-align: center;
    color: var(--starter-muted);
    font-size: 0.9rem;
}

.data-table__row {
    cursor: pointer;
    transition: background 0.15s;
}

.data-table__row:hover td {
    background: rgba(0, 106, 82, 0.04);
}

.data-table__pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    border-top: 1px solid rgba(17, 34, 51, 0.06);
}

.data-table__count {
    font-size: 0.875rem;
    color: var(--starter-muted);
}
</style>
