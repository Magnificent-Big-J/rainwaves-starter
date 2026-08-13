import { onBeforeUnmount, onMounted, ref } from 'vue';
import { onBeforeRouteLeave } from 'vue-router';

// Generic "are you sure you want to leave?" guard. The caller owns what "dirty"
// means (usually a computed comparing current form state against a baseline
// snapshot, reset after a successful save) — this only wires that boolean up
// to the two places a user can actually lose unsaved input: an in-app route
// change (blocked with our own ConfirmDialog) and a tab close/refresh/external
// navigation (blocked with the browser's native prompt, which is the only one
// browsers allow — the message text itself is never shown, they render their own).
export function useUnsavedChanges(isDirty) {
    const showLeaveConfirm = ref(false);
    let resumeNavigation = null;

    const handleBeforeUnload = (event) => {
        if (!isDirty.value) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    };

    onMounted(() => window.addEventListener('beforeunload', handleBeforeUnload));
    onBeforeUnmount(() => window.removeEventListener('beforeunload', handleBeforeUnload));

    onBeforeRouteLeave((to, from, next) => {
        if (!isDirty.value) {
            next();

            return;
        }

        resumeNavigation = next;
        showLeaveConfirm.value = true;
    });

    const confirmLeave = () => {
        showLeaveConfirm.value = false;
        resumeNavigation?.(true);
        resumeNavigation = null;
    };

    const cancelLeave = () => {
        showLeaveConfirm.value = false;
        resumeNavigation?.(false);
        resumeNavigation = null;
    };

    return { showLeaveConfirm, confirmLeave, cancelLeave };
}
