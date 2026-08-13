import { flushPromises, mount } from '@vue/test-utils';
import { defineComponent, ref } from 'vue';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it } from 'vitest';

import { useUnsavedChanges } from './useUnsavedChanges';

// Route-leave guards can only be exercised with a real "from"/"to" pair of routed
// components, so this file intentionally defines two tiny fixtures rather than one.
/* eslint-disable vue/one-component-per-file */
const FormPage = defineComponent({
    setup() {
        const dirty = ref(false);
        const { showLeaveConfirm, confirmLeave, cancelLeave } = useUnsavedChanges(dirty);

        return { dirty, showLeaveConfirm, confirmLeave, cancelLeave };
    },
    template: '<div>form page</div>',
});

const OtherPage = defineComponent({ template: '<div>other page</div>' });
/* eslint-enable vue/one-component-per-file */

const buildRouter = () =>
    createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/form', component: FormPage },
            { path: '/other', component: OtherPage },
        ],
    });

let wrapper;

afterEach(() => {
    wrapper?.unmount();
    window.removeEventListener('beforeunload', () => {});
});

describe('useUnsavedChanges route guard', () => {
    it('navigates away immediately when there are no unsaved changes', async () => {
        const router = buildRouter();
        router.push('/form');
        await router.isReady();

        wrapper = mount({ template: '<router-view />' }, { global: { plugins: [router] } });
        await flushPromises();

        await router.push('/other');
        await flushPromises();

        expect(router.currentRoute.value.path).toBe('/other');
    });

    it('blocks in-app navigation and shows the confirm dialog when dirty', async () => {
        const router = buildRouter();
        router.push('/form');
        await router.isReady();

        wrapper = mount({ template: '<router-view />' }, { global: { plugins: [router] } });
        await flushPromises();

        wrapper.findComponent(FormPage).vm.dirty = true;
        await flushPromises();

        router.push('/other');
        await flushPromises();

        expect(router.currentRoute.value.path).toBe('/form');
        expect(wrapper.findComponent(FormPage).vm.showLeaveConfirm).toBe(true);
    });

    it('resumes navigation once the user confirms leaving', async () => {
        const router = buildRouter();
        router.push('/form');
        await router.isReady();

        wrapper = mount({ template: '<router-view />' }, { global: { plugins: [router] } });
        await flushPromises();

        const formVm = wrapper.findComponent(FormPage).vm;
        formVm.dirty = true;
        await flushPromises();

        router.push('/other');
        await flushPromises();

        formVm.confirmLeave();
        await flushPromises();

        expect(router.currentRoute.value.path).toBe('/other');
        expect(formVm.showLeaveConfirm).toBe(false);
    });

    it('stays on the page once the user cancels leaving', async () => {
        const router = buildRouter();
        router.push('/form');
        await router.isReady();

        wrapper = mount({ template: '<router-view />' }, { global: { plugins: [router] } });
        await flushPromises();

        const formVm = wrapper.findComponent(FormPage).vm;
        formVm.dirty = true;
        await flushPromises();

        router.push('/other');
        await flushPromises();

        formVm.cancelLeave();
        await flushPromises();

        expect(router.currentRoute.value.path).toBe('/form');
        expect(formVm.showLeaveConfirm).toBe(false);
    });
});

describe('useUnsavedChanges beforeunload guard', () => {
    it('prevents the default unload behaviour while dirty', async () => {
        const router = buildRouter();
        router.push('/form');
        await router.isReady();

        wrapper = mount({ template: '<router-view />' }, { global: { plugins: [router] } });
        await flushPromises();

        wrapper.findComponent(FormPage).vm.dirty = true;
        await flushPromises();

        const event = new Event('beforeunload', { cancelable: true });
        window.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(true);
    });

    it('does not prevent unload when there are no unsaved changes', async () => {
        const router = buildRouter();
        router.push('/form');
        await router.isReady();

        wrapper = mount({ template: '<router-view />' }, { global: { plugins: [router] } });
        await flushPromises();

        const event = new Event('beforeunload', { cancelable: true });
        window.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(false);
    });
});
