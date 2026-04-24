<template>
    <v-select
        v-bind="$attrs"
        :model-value="modelValue"
        :items="items"
        :label="label"
        :placeholder="placeholder"
        :hint="hint"
        :clearable="clearable"
        :item-title="itemTitle"
        :item-value="itemValue"
        :hide-details="hideDetails"
        class="app-select"
        @update:model-value="$emit('update:modelValue', $event)"
    />
</template>

<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    modelValue: { type: [String, Number, Object, null], default: null },
    items: { type: Array, default: () => [] },
    label: { type: String, default: null },
    placeholder: { type: String, default: null },
    hint: { type: String, default: null },
    clearable: { type: Boolean, default: true },
    itemTitle: { type: String, default: 'title' },
    itemValue: { type: String, default: 'value' },
    hideDetails: { type: [Boolean, String], default: 'auto' },
});

defineEmits(['update:modelValue']);
</script>
