import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import { vuetify } from '../plugins/vuetify';
import AppDataTable from './AppDataTable.vue';

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email' },
];

const rows = [
    { id: 1, name: 'Alice', email: 'alice@example.test' },
    { id: 2, name: 'Bob', email: 'bob@example.test' },
];

const mountTable = (props = {}) =>
    mount(AppDataTable, {
        global: { plugins: [vuetify] },
        props: { columns, rows, ...props },
        slots: {
            row: `<template #row="{ row }"><td>{{ row.name }}</td><td>{{ row.email }}</td></template>`,
        },
        attachTo: document.body,
    });

beforeEach(() => {
    window.localStorage.clear();
});

afterEach(() => {
    // Every test mounts with attachTo: document.body, and Vuetify's menu/overlay
    // content is teleported to document.body too — without this, a checkbox from a
    // previous test's (unmounted-but-not-removed) menu can still match a
    // document.querySelector() in a later test.
    document.body.innerHTML = '';
});

// VMenu teleports its content to document.body, outside the mounted wrapper's own
// DOM subtree, so it has to be queried from the document rather than via
// wrapper.find() — and needs a flushPromises() beyond $nextTick() for Vuetify's
// overlay-open transition to actually attach the content.
const openColumnMenu = async (wrapper) => {
    await wrapper.find('button[title="Show/hide columns"]').trigger('click');
    await flushPromises();

    return document.querySelector('.v-list-item input[type="checkbox"]');
};

describe('AppDataTable sorting', () => {
    it('emits sort with toggled direction when a sortable header is clicked', async () => {
        const wrapper = mountTable({ sortBy: null, sortDirection: 'asc' });

        await wrapper.find('th.data-table__th--sortable').trigger('click');

        expect(wrapper.emitted('sort')).toEqual([[{ sortBy: 'name', sortDirection: 'asc' }]]);
    });

    it('toggles to desc when the same column is already sorted asc', async () => {
        const wrapper = mountTable({ sortBy: 'name', sortDirection: 'asc' });

        await wrapper.find('th.data-table__th--sortable').trigger('click');

        expect(wrapper.emitted('sort')).toEqual([[{ sortBy: 'name', sortDirection: 'desc' }]]);
    });

    it('is keyboard-operable: Enter on a focused sortable header fires the same sort event as a click', async () => {
        const wrapper = mountTable({ sortBy: null, sortDirection: 'asc' });
        const header = wrapper.find('th.data-table__th--sortable');

        await header.trigger('keydown.enter');

        expect(wrapper.emitted('sort')).toEqual([[{ sortBy: 'name', sortDirection: 'asc' }]]);
    });

    it('space also activates a focused sortable header', async () => {
        const wrapper = mountTable({ sortBy: null, sortDirection: 'asc' });

        await wrapper.find('th.data-table__th--sortable').trigger('keydown.space');

        expect(wrapper.emitted('sort')).toHaveLength(1);
    });

    it('exposes correct aria-sort and tabindex only on sortable headers', () => {
        const wrapper = mountTable({ sortBy: 'name', sortDirection: 'desc' });
        const headers = wrapper.findAll('th');

        const nameHeader = headers.find((h) => h.text().includes('Name'));
        const emailHeader = headers.find((h) => h.text().includes('Email'));

        expect(nameHeader.attributes('aria-sort')).toBe('descending');
        // No role="button" here: aria-sort is only a valid ARIA attribute on an element
        // whose role is columnheader (a <th>'s native implicit role) — role="button"
        // would override that and make aria-sort invalid (axe: aria-allowed-attr).
        // tabindex + Enter/Space handling alone still makes it keyboard-operable.
        expect(nameHeader.attributes('role')).toBeUndefined();
        expect(nameHeader.attributes('tabindex')).toBe('0');

        expect(emailHeader.attributes('aria-sort')).toBeUndefined();
        expect(emailHeader.attributes('tabindex')).toBeUndefined();
    });
});

describe('AppDataTable row selection', () => {
    it('emits update:selected with the row id when its checkbox is toggled', async () => {
        const wrapper = mountTable({ selectable: true, selected: [] });

        await wrapper.findAll('tbody input[type="checkbox"]')[0].setValue(true);

        expect(wrapper.emitted('update:selected')[0]).toEqual([[1]]);
    });

    it('removes the id from the selection when an already-selected row is unchecked', async () => {
        const wrapper = mountTable({ selectable: true, selected: [1, 2] });

        await wrapper.findAll('tbody input[type="checkbox"]')[0].setValue(false);

        expect(wrapper.emitted('update:selected')[0]).toEqual([[2]]);
    });

    it('select-all checkbox selects every row currently on the page', async () => {
        const wrapper = mountTable({ selectable: true, selected: [] });

        await wrapper.find('thead input[type="checkbox"]').setValue(true);

        expect(wrapper.emitted('update:selected')[0][0]).toEqual(expect.arrayContaining([1, 2]));
    });

    it('shows the bulk-action bar only once something is selected', async () => {
        const wrapper = mountTable({ selectable: true, selected: [] });
        expect(wrapper.find('.data-table__bulk-bar').exists()).toBe(false);

        await wrapper.setProps({ selected: [1] });
        expect(wrapper.find('.data-table__bulk-bar').exists()).toBe(true);
        expect(wrapper.find('.data-table__bulk-bar').text()).toContain('1 selected');
    });
});

describe('AppDataTable clickable rows', () => {
    it('does not make rows keyboard-focusable unless clickable-rows is set', () => {
        const wrapper = mountTable();

        expect(wrapper.find('tbody tr.data-table__row').attributes('tabindex')).toBeUndefined();
    });

    it('makes rows keyboard-focusable and activatable when clickable-rows is set', async () => {
        const wrapper = mountTable({ clickableRows: true });
        const row = wrapper.find('tbody tr.data-table__row');

        expect(row.attributes('tabindex')).toBe('0');
        // Deliberately no role="button" here: a row typically also contains its own
        // real interactive elements (a select checkbox, edit/archive buttons), and an
        // ARIA "button" role containing other focusable descendants is an accessibility
        // violation (axe: no-focusable-content) — found via the Playwright a11y suite.
        // tabindex + Enter/Space handling alone still makes the row keyboard-operable.
        expect(row.attributes('role')).toBeUndefined();

        await row.trigger('keydown.enter');

        expect(wrapper.emitted('row-click')[0]).toEqual([rows[0]]);
    });
});

describe('AppDataTable accessible column headers', () => {
    it('gives a visually-empty header its srLabel as an aria-label', () => {
        const wrapper = mountTable({
            columns: [...columns, { key: 'actions', label: '', srLabel: 'Actions' }],
        });
        const headers = wrapper.findAll('th');

        expect(headers.at(-1).attributes('aria-label')).toBe('Actions');
    });

    it('does not add an aria-label when the header already has visible text', () => {
        const wrapper = mountTable();
        const headers = wrapper.findAll('th');

        expect(headers[0].attributes('aria-label')).toBeUndefined();
    });
});

describe('AppDataTable export', () => {
    it('does not render an export button when exportHref is not set', () => {
        const wrapper = mountTable();

        expect(wrapper.find('a[download]').exists()).toBe(false);
    });

    it('renders an export link pointing at the given URL when exportHref is set', () => {
        const wrapper = mountTable({ exportHref: '/api/v1/users/export?status=archived' });
        const link = wrapper.find('a[download]');

        expect(link.exists()).toBe(true);
        expect(link.attributes('href')).toBe('/api/v1/users/export?status=archived');
    });
});

describe('AppDataTable column visibility', () => {
    const hideableColumns = [
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email', hideable: true },
    ];

    it('only lists hideable columns in the visibility menu', async () => {
        const wrapper = mountTable({ columns: hideableColumns });

        await wrapper.find('button[title="Show/hide columns"]').trigger('click');
        await flushPromises();

        const labels = [...document.querySelectorAll('.v-list-item .v-label')].map((el) => el.textContent.trim());

        expect(labels).toEqual(['Email']);
    });

    it('does not render the visibility toggle when no column opts in', () => {
        const wrapper = mountTable();

        expect(wrapper.find('button[title="Show/hide columns"]').exists()).toBe(false);
    });

    it('persists hidden columns to localStorage under the given tableId', async () => {
        const wrapper = mountTable({ columns: hideableColumns, tableId: 'test-table' });

        const checkbox = await openColumnMenu(wrapper);
        checkbox.click();
        await flushPromises();

        const stored = JSON.parse(window.localStorage.getItem('datatable:test-table:hidden-columns'));
        expect(stored).toEqual(['email']);
    });

    it('hides the column via a collapsed <col>, without needing the parent to change its row markup', async () => {
        const wrapper = mountTable({ columns: hideableColumns, tableId: 'test-table-2' });

        const checkbox = await openColumnMenu(wrapper);
        checkbox.click();
        await flushPromises();

        const cols = wrapper.findAll('col');
        // Second <col> corresponds to the "email" column (no selectable checkbox col here).
        expect(cols[1].attributes('style')).toContain('visibility: collapse');
    });
});
