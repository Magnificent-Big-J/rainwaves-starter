<template>
    <div class="data-table">
        <div v-if="hasSelection" class="data-table__bulk-bar">
            <span class="data-table__bulk-count">{{ selected.length }} selected</span>
            <v-spacer />
            <slot name="bulk-actions" :selected="selected" :clear="clearSelection" />
            <v-btn variant="text" size="small" @click="clearSelection">Clear</v-btn>
        </div>

        <div
            v-else-if="$slots.toolbar || searchable || title || hideableColumns.length || props.exportHref"
            class="data-table__toolbar"
        >
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
            <v-btn
                v-if="props.exportHref"
                :href="props.exportHref"
                variant="text"
                size="small"
                prepend-icon="mdi-file-export-outline"
                download
            >
                Export
            </v-btn>
            <v-menu v-if="hideableColumns.length" :close-on-content-click="false">
                <template #activator="{ props: menuProps }">
                    <v-btn
                        v-bind="menuProps"
                        icon="mdi-view-column-outline"
                        variant="text"
                        size="small"
                        title="Show/hide columns"
                        aria-label="Show or hide columns"
                    />
                </template>
                <v-list density="compact">
                    <v-list-item v-for="col in hideableColumns" :key="col.key">
                        <v-checkbox-btn
                            :model-value="!hiddenColumns.has(col.key)"
                            :label="col.label"
                            density="compact"
                            @update:model-value="toggleColumn(col.key)"
                        />
                    </v-list-item>
                </v-list>
            </v-menu>
        </div>

        <div class="data-table__scroll">
            <v-table density="comfortable" class="data-table__table">
                <colgroup>
                    <col v-if="selectable" />
                    <col v-for="col in columns" :key="col.key" :style="columnStyle(col)" />
                </colgroup>
                <thead>
                    <tr>
                        <th v-if="selectable" class="data-table__select-col">
                            <v-checkbox-btn
                                :model-value="allOnPageSelected"
                                :indeterminate="hasSelection && !allOnPageSelected"
                                density="compact"
                                aria-label="Select all rows on this page"
                                @update:model-value="toggleSelectAll"
                            />
                        </th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :class="[col.class, col.sortable && 'data-table__th--sortable']"
                            :tabindex="col.sortable ? 0 : undefined"
                            :role="col.sortable ? 'button' : undefined"
                            :aria-sort="ariaSort(col)"
                            @click="col.sortable && toggleSort(col.sortKey || col.key)"
                            @keydown.enter.space.prevent="col.sortable && toggleSort(col.sortKey || col.key)"
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
                        :class="[
                            'data-table__row',
                            isSelected(row) && 'data-table__row--selected',
                            clickableRows && 'data-table__row--clickable',
                        ]"
                        :tabindex="clickableRows ? 0 : undefined"
                        :role="clickableRows ? 'button' : undefined"
                        @click="$emit('row-click', row)"
                        @keydown.enter.space.prevent="clickableRows && $emit('row-click', row)"
                    >
                        <td v-if="selectable" class="data-table__select-col" @click.stop>
                            <v-checkbox-btn
                                :model-value="isSelected(row)"
                                density="compact"
                                :aria-label="`Select row ${rowIdentity(row)}`"
                                @update:model-value="toggleRow(row)"
                            />
                        </td>
                        <slot name="row" :row="row" />
                    </tr>
                </tbody>
            </v-table>
        </div>

        <div v-if="meta && meta.last_page > 1" class="data-table__pagination">
            <AppPaginationBar :meta="meta" @update:page="$emit('page-change', $event)" />
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

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
    // Set when the parent listens to @row-click, so keyboard users get the same
    // affordance (tabindex + role=button + Enter/Space) mouse users get for free.
    // Not inferred automatically — Vue has no clean way to detect "is this emit
    // listened to" from inside the child, and guessing wrong would make every
    // AppDataTable row a confusing no-op keyboard stop for tables that don't use it.
    clickableRows: { type: Boolean, default: false },
    // Persists column visibility to localStorage under `datatable:{tableId}:hidden-columns`.
    // Omit it and the feature still works, just doesn't survive a reload.
    tableId: { type: String, default: null },
    // Renders a toolbar "Export" button as a plain <a href> (browser handles the
    // download via the response's Content-Disposition, cookies included automatically
    // — no need to fetch + blob it ourselves). The parent builds this URL from its own
    // current filter/sort state, since AppDataTable itself only ever sees one page of
    // rows and export needs the full filtered set from the backend.
    exportHref: { type: String, default: null },
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

const ariaSort = (col) => {
    if (!col.sortable) {
        return undefined;
    }

    const key = col.sortKey || col.key;

    if (props.sortBy !== key) {
        return 'none';
    }

    return props.sortDirection === 'desc' ? 'descending' : 'ascending';
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

// ── Column visibility ──────────────────────────────────────────────
const storageKey = computed(() => (props.tableId ? `datatable:${props.tableId}:hidden-columns` : null));

const loadHiddenColumns = () => {
    if (!storageKey.value) {
        return new Set();
    }

    try {
        const raw = window.localStorage.getItem(storageKey.value);

        return raw ? new Set(JSON.parse(raw)) : new Set();
    } catch (_error) {
        return new Set();
    }
};

const hiddenColumns = ref(loadHiddenColumns());

watch(
    () => props.tableId,
    () => {
        hiddenColumns.value = loadHiddenColumns();
    }
);

const hideableColumns = computed(() => props.columns.filter((col) => col.hideable));

const toggleColumn = (key) => {
    const next = new Set(hiddenColumns.value);

    if (next.has(key)) {
        next.delete(key);
    } else {
        next.add(key);
    }

    hiddenColumns.value = next;

    if (storageKey.value) {
        window.localStorage.setItem(storageKey.value, JSON.stringify([...next]));
    }
};

// A <col> with visibility:collapse hides that entire column (header *and* every
// row's cell at that position) without AppDataTable needing to know or touch what
// markup the #row slot renders for it — the alternative (a per-column cell slot
// API) would mean rewriting every existing page's row template just for this.
const columnStyle = (col) => (hiddenColumns.value.has(col.key) ? 'visibility: collapse' : '');

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

.data-table__scroll {
    overflow-x: auto;
}

.data-table__table {
    background: transparent;
}

/* A visibility:collapse'd <col> correctly zeroes its <th>/<td> width, but doesn't
   clip their content by itself — without this, a collapsed sortable column's icon
   visually overflows into its neighbour's space instead of disappearing. */
.data-table__table :deep(th),
.data-table__table :deep(td) {
    overflow: hidden;
}

.data-table__select-col {
    width: 2.5rem;
}

.data-table__th--sortable {
    cursor: pointer;
    user-select: none;
}

.data-table__th--sortable:focus-visible,
.data-table__row--clickable:focus-visible {
    outline: 2px solid var(--rw-600, #059669);
    outline-offset: -2px;
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

.data-table__row--clickable {
    cursor: pointer;
}

.data-table__row {
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

/* ── Mobile: stack each row into a labelled card ─────────────────────
   Relies on the #row slot's <td> elements carrying data-label="Column Name" —
   see CLAUDE.md "AppDataTable mobile layout" for the convention. Cells without a
   data-label (or with one that's an empty string, e.g. an actions column) render
   without the inline label. */
@media (max-width: 720px) {
    .data-table__scroll {
        overflow-x: visible;
    }

    .data-table__table :deep(thead) {
        display: none;
    }

    .data-table__table :deep(tbody),
    .data-table__table :deep(tr) {
        display: block;
        width: 100%;
    }

    .data-table__table :deep(tr.data-table__row) {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(17, 34, 51, 0.08);
    }

    .data-table__table :deep(td) {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.4rem 0 !important;
        border: none !important;
        text-align: right;
    }

    .data-table__table :deep(td[data-label]:not([data-label='']))::before {
        content: attr(data-label);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--rw-dim, #94a3b8);
        text-align: left;
        margin-right: auto;
    }

    .data-table__select-col {
        justify-content: flex-end !important;
    }
}
</style>
