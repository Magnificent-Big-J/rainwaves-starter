<template>
    <v-autocomplete
        v-bind="$attrs"
        :model-value="modelValue"
        :items="items"
        :label="label"
        :placeholder="placeholder"
        :hint="hint"
        :chips="chips"
        :closable-chips="closableChips"
        :multiple="multiple"
        :clearable="clearable"
        :item-title="itemTitle"
        :item-value="itemValue"
        :prepend-inner-icon="prependInnerIcon"
        :hide-details="hideDetails"
        class="app-autocomplete"
        @update:model-value="$emit('update:modelValue', $event)"
    />
</template>

<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    modelValue: { type: [Array, String, Number, Object, null], default: null },
    items: { type: Array, default: () => [] },
    label: { type: String, default: null },
    placeholder: { type: String, default: null },
    hint: { type: String, default: null },
    chips: { type: Boolean, default: false },
    closableChips: { type: Boolean, default: false },
    multiple: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true },
    itemTitle: { type: String, default: 'title' },
    itemValue: { type: String, default: 'value' },
    prependInnerIcon: { type: String, default: 'mdi-magnify' },
    hideDetails: { type: [Boolean, String], default: 'auto' },
});

defineEmits(['update:modelValue']);
</script>
