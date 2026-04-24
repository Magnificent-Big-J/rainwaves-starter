<template>
    <v-text-field
        v-bind="$attrs"
        :model-value="modelValue"
        :label="label"
        :type="type"
        :placeholder="placeholder"
        :hint="hint"
        :prepend-inner-icon="prependInnerIcon"
        :append-inner-icon="appendInnerIcon"
        :hide-details="hideDetails"
        class="app-text-field"
        @update:model-value="$emit('update:modelValue', $event)"
    />
</template>

<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    modelValue: { type: [String, Number, null], default: null },
    label: { type: String, default: null },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: null },
    hint: { type: String, default: null },
    prependInnerIcon: { type: String, default: null },
    appendInnerIcon: { type: String, default: null },
    hideDetails: { type: [Boolean, String], default: 'auto' },
});

defineEmits(['update:modelValue']);
</script>
