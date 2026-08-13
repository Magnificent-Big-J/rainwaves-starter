import { flushPromises, mount } from '@vue/test-utils';
import { defineComponent, reactive } from 'vue';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import { usePersistedFilters } from './usePersistedFilters';

const mountWithFilters = (defaults, options) => {
    let filters;

    const wrapper = mount(
        defineComponent({
            setup() {
                filters = reactive(defaults);
                usePersistedFilters('test-table', filters, options);

                return {};
            },
            template: '<div />',
        })
    );

    return { wrapper, filters };
};

beforeEach(() => {
    window.localStorage.clear();
});

afterEach(() => {
    window.localStorage.clear();
});

describe('usePersistedFilters', () => {
    it('does not touch the defaults when nothing has been persisted yet', () => {
        const { filters } = mountWithFilters({ search: '', status: '' });

        expect(filters).toEqual({ search: '', status: '' });
    });

    it('persists filter changes to localStorage under the given key', async () => {
        const { filters } = mountWithFilters({ search: '', status: '' });

        filters.search = 'ops';
        await flushPromises();

        const stored = JSON.parse(window.localStorage.getItem('filters:test-table'));
        expect(stored).toEqual({ search: 'ops', status: '' });
    });

    it('restores persisted values into the filters object on mount', async () => {
        window.localStorage.setItem('filters:test-table', JSON.stringify({ search: 'ops', status: 'archived' }));

        const { filters } = mountWithFilters({ search: '', status: '' });

        expect(filters).toEqual({ search: 'ops', status: 'archived' });
    });

    it('leaves excluded fields (e.g. page) untouched by restore and storage', async () => {
        window.localStorage.setItem('filters:test-table', JSON.stringify({ search: 'ops', page: 5 }));

        const { filters } = mountWithFilters({ search: '', page: 1 }, { exclude: ['page'] });

        expect(filters.search).toBe('ops');
        expect(filters.page).toBe(1);

        filters.page = 3;
        filters.search = 'ops2';
        await flushPromises();

        const stored = JSON.parse(window.localStorage.getItem('filters:test-table'));
        expect(stored).toEqual({ search: 'ops2' });
    });

    it('ignores corrupt stored JSON instead of throwing', () => {
        window.localStorage.setItem('filters:test-table', '{not json');

        expect(() => mountWithFilters({ search: '' })).not.toThrow();
    });
});
