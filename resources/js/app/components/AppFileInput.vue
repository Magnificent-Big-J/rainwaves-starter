<template>
    <v-file-input
        v-bind="$attrs"
        :model-value="modelValue"
        :label="label"
        :hint="hint"
        :accept="accept"
        :show-size="showSize"
        :prepend-icon="prependIcon"
        :hide-details="hideDetails"
        :clearable="clearable"
        class="app-file-input"
        @update:model-value="$emit('update:modelValue', $event)"
    />
</template>

<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    modelValue: { type: [Array, Object, null], default: null },
    label: { type: String, default: null },
    hint: { type: String, default: null },
    accept: { type: String, default: null },
    showSize: { type: Boolean, default: true },
    prependIcon: { type: String, default: 'mdi-paperclip' },
    hideDetails: { type: [Boolean, String], default: 'auto' },
    clearable: { type: Boolean, default: true },
});

defineEmits(['update:modelValue']);
</script>
