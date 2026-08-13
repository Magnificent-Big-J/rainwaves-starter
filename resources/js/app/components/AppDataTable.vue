<template>
    <div class="data-table">
        <div v-if="hasSelection" class="data-table__bulk-bar">
            <span class="data-table__bulk-count">{{ selected.length }} selected</span>
            <v-spacer />
            <slot name="bulk-actions" :selected="selected" :clear="clearSelection" />
            <v-btn variant="text" size="small" @click="clearSelection">Clear</v-btn>
        </div>

        <div v-else-if="$slots.toolbar || searchable || title" class="data-table__toolbar">
            <div v-if="title" class="data-table__title">
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
                    <th v-if="selectable" class="data-table__select-col">
                        <v-checkbox-btn
                            :model-value="allOnPageSelected"
                            :indeterminate="hasSelection && !allOnPageSelected"
                            density="compact"
                            @update:model-value="toggleSelectAll"
                        />
                    </th>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        :class="[col.class, col.sortable && 'data-table__th--sortable']"
                        @click="col.sortable && toggleSort(col.sortKey || col.key)"
                    >
                        <span class="data-table__th-inner">
                            {{ col.label }}
                            <v-icon
                                v-if="col.sortable"
                                size="15"
                                :icon="sortIcon(col.sortKey || col.key)"
                                :class="[
                                    'data-table__sort-icon',
                                    sortBy === (col.sortKey || col.key) && 'data-table__sort-icon--active',
                                ]"
                            />
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="loading">
                    <td :colspan="columnCount" class="data-table__loader">
                        <v-progress-linear indeterminate color="primary" />
                    </td>
                </tr>
                <tr v-else-if="!rows.length">
                    <td :colspan="columnCount" class="data-table__empty">
                        <AppEmptyState :title="emptyTitle" :text="emptyText" icon="mdi-database-search-outline" />
                    </td>
                </tr>
                <tr
                    v-for="row in rows"
                    :key="row.id ?? row"
                    :class="['data-table__row', isSelected(row) && 'data-table__row--selected']"
                    @click="$emit('row-click', row)"
                >
                    <td v-if="selectable" class="data-table__select-col" @click.stop>
                        <v-checkbox-btn
                            :model-value="isSelected(row)"
                            density="compact"
                            @update:model-value="toggleRow(row)"
                        />
                    </td>
                    <slot name="row" :row="row" />
                </tr>
            </tbody>
        </v-table>

        <div v-if="meta && meta.last_page > 1" class="data-table__pagination">
            <AppPaginationBar :meta="meta" @update:page="$emit('page-change', $event)" />
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    title: { type: String, default: null },
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    searchable: { type: Boolean, default: false },
    emptyTitle: { type: String, default: 'No records found' },
    emptyText: { type: String, default: 'No records found.' },
    // Sort is parent-controlled (like search/page-change) — pass the current state
    // back in via these props so the header arrow reflects what the backend applied.
    sortBy: { type: String, default: null },
    sortDirection: { type: String, default: 'asc' },
    // Row selection: v-model:selected — an array of row ids. Bulk actions render via
    // the #bulk-actions slot ({ selected, clear }) and only take over the toolbar
    // area while something is selected, matching Sessions/Billing's existing
    // confirm-then-act pattern rather than introducing a new one.
    selectable: { type: Boolean, default: false },
    selected: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
});

const emit = defineEmits(['search', 'page-change', 'row-click', 'sort', 'update:selected']);

const searchQuery = ref('');

let searchTimer = null;

const onSearch = (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => emit('search', val ?? ''), 300);
};

const columnCount = computed(() => props.columns.length + (props.selectable ? 1 : 0));

const sortIcon = (key) => {
    if (props.sortBy !== key) {
        return 'mdi-unfold-more-horizontal';
    }

    return props.sortDirection === 'desc' ? 'mdi-arrow-down' : 'mdi-arrow-up';
};

const toggleSort = (key) => {
    const direction = props.sortBy === key && props.sortDirection === 'asc' ? 'desc' : 'asc';

    emit('sort', { sortBy: key, sortDirection: direction });
};

const hasSelection = computed(() => props.selectable && props.selected.length > 0);

const rowIdentity = (row) => row?.[props.rowKey] ?? row;

const isSelected = (row) => props.selected.includes(rowIdentity(row));

const allOnPageSelected = computed(() => props.rows.length > 0 && props.rows.every((row) => isSelected(row)));

const toggleRow = (row) => {
    const id = rowIdentity(row);
    const next = isSelected(row) ? props.selected.filter((existing) => existing !== id) : [...props.selected, id];

    emit('update:selected', next);
};

const toggleSelectAll = () => {
    if (allOnPageSelected.value) {
        const pageIds = props.rows.map(rowIdentity);
        emit(
            'update:selected',
            props.selected.filter((id) => !pageIds.includes(id))
        );

        return;
    }

    const merged = new Set([...props.selected, ...props.rows.map(rowIdentity)]);
    emit('update:selected', [...merged]);
};

const clearSelection = () => emit('update:selected', []);

defineExpose({ clearSelection });
</script>

<style scoped>
.data-table {
    border-radius: 1.25rem;
    overflow: hidden;
    border: 1px solid rgba(17, 34, 51, 0.08);
    background: rgba(255, 253, 248, 0.9);
}

.data-table__toolbar,
.data-table__bulk-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem 0.75rem;
    flex-wrap: wrap;
}

.data-table__bulk-bar {
    background: rgba(0, 106, 82, 0.06);
    padding-block: 0.75rem;
}

.data-table__bulk-count {
    font-weight: 700;
    font-size: 0.875rem;
    color: var(--rw-700, #006a4a);
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

.data-table__select-col {
    width: 2.5rem;
}

.data-table__th--sortable {
    cursor: pointer;
    user-select: none;
}

.data-table__th-inner {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.data-table__sort-icon {
    opacity: 0.35;
}

.data-table__sort-icon--active {
    opacity: 1;
}

.data-table__loader {
    padding: 0 !important;
}

.data-table__empty {
    padding: 0 !important;
}

.data-table__row {
    cursor: pointer;
    transition: background 0.15s;
}

.data-table__row:hover td {
    background: rgba(0, 106, 82, 0.04);
}

.data-table__row--selected td {
    background: rgba(0, 106, 82, 0.07);
}

.data-table__pagination {
    padding: 0.9rem 1.25rem;
    border-top: 1px solid rgba(17, 34, 51, 0.06);
}
</style>
