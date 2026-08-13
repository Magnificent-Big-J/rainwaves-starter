<template>
    <div class="filter-bar">
        <div class="filter-bar__primary">
            <slot />
        </div>
        <div v-if="$slots.actions" class="filter-bar__actions">
            <slot name="actions" />
        </div>
    </div>
</template>

<style scoped>
.filter-bar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.9rem;
    flex-wrap: wrap;
    padding: 1rem 1.1rem;
    border: 1px solid rgba(17, 34, 51, 0.08);
    border-radius: 1rem;
    background: rgba(255, 253, 248, 0.9);
}

.filter-bar__primary,
.filter-bar__actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

/* Without this, .filter-bar__primary defaults to flex:0 1 auto — shrink-to-fit
   its own content — so its children only get their flex-basis worth of room
   and wrap onto separate lines immediately, even with plenty of width to
   spare in the row (justify-content:space-between on the parent has nothing
   to space when there's no #actions slot in use). */
.filter-bar__primary {
    flex: 1 1 auto;
    min-width: 0;
}

/* Consistent, proportional sizing for whatever a page drops into the default
   slot — a search field should read as the primary control and a select as a
   secondary refinement, on every page that uses this bar, without each page
   having to redeclare its own bespoke width classes (a real inconsistency
   this fixed: some pages had an oversized search box next to tiny unstyled
   selects, others had a search box so narrow its placeholder text truncated
   to a couple of characters). Targets our own component classes
   (app-text-field/app-select/app-autocomplete), not Vuetify's internal ones,
   since VSelect also carries a v-text-field class internally. */
.filter-bar__primary :deep(.app-text-field) {
    flex: 2 1 240px;
    max-width: 420px;
}

.filter-bar__primary :deep(.app-select),
.filter-bar__primary :deep(.app-autocomplete) {
    flex: 1 1 180px;
    max-width: 280px;
}
</style>
