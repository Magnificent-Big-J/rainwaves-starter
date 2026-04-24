<template>
    <v-textarea
        v-bind="$attrs"
        :model-value="modelValue"
        :label="label"
        :placeholder="placeholder"
        :hint="hint"
        :rows="rows"
        :hide-details="hideDetails"
        class="app-textarea"
        @update:model-value="$emit('update:modelValue', $event)"
    />
</template>

<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    modelValue: { type: [String, null], default: null },
    label: { type: String, default: null },
    placeholder: { type: String, default: null },
    hint: { type: String, default: null },
    rows: { type: [Number, String], default: 4 },
    hideDetails: { type: [Boolean, String], default: 'auto' },
});

defineEmits(['update:modelValue']);
</script>
