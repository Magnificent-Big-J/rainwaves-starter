import { watch } from 'vue';

// "Saved views" scoped down to the useful 80%: remember the last filter/sort state a
// user left a table in, per table, so returning to it doesn't reset to defaults. Not
// named/multiple presets — that would need a backing model and its own management UI,
// real scope for a future module if one ever needs it. This is pure localStorage, same
// pattern as AppDataTable's own column-visibility persistence.
//
// `filters` must be a `reactive()` object. Call this *before* the page's initial fetch
// so the restored values are what gets loaded. Keys in `exclude` (e.g. "page" — a view
// should always land on page 1, not wherever you last paginated to) are left alone.
export function usePersistedFilters(storageKey, filters, { exclude = [] } = {}) {
    const key = `filters:${storageKey}`;

    try {
        const raw = window.localStorage.getItem(key);

        if (raw) {
            const stored = JSON.parse(raw);

            for (const field of Object.keys(stored)) {
                if (!exclude.includes(field) && field in filters) {
                    filters[field] = stored[field];
                }
            }
        }
    } catch (_error) {
        // Corrupt or inaccessible storage — fall back to whatever defaults the page
        // already initialised `filters` with.
    }

    watch(
        filters,
        () => {
            const toStore = { ...filters };

            for (const field of exclude) {
                delete toStore[field];
            }

            try {
                window.localStorage.setItem(key, JSON.stringify(toStore));
            } catch (_error) {
                // Storage full/unavailable (private browsing, quota) — persistence is a
                // convenience, not a requirement, so fail silently.
            }
        },
        { deep: true }
    );
}
