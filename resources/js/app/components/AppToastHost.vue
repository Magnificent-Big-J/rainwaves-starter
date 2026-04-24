<template>
    <v-snackbar
        :model-value="Boolean(active)"
        :color="active?.color || 'error'"
        :timeout="active?.timeout || 5000"
        location="top right"
        @update:model-value="onToggle"
    >
        {{ active?.message }}
    </v-snackbar>
</template>

<script setup>
import { storeToRefs } from 'pinia';

import { useAppErrorsStore } from '../stores/app-errors';

const errors = useAppErrorsStore();
const { active } = storeToRefs(errors);

const onToggle = (value) => {
    if (!value) {
        errors.clear();
    }
};
</script>
