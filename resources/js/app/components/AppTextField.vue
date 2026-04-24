<template>
    <v-text-field
        v-bind="$attrs"
        :model-value="modelValue"
        :label="label"
        :type="inputType"
        :placeholder="placeholder"
        :hint="hint"
        :prepend-inner-icon="prependInnerIcon"
        :append-inner-icon="resolvedAppendInnerIcon"
        :hide-details="hideDetails"
        class="app-text-field"
        @update:model-value="$emit('update:modelValue', $event)"
        @click:append-inner="handleAppendInnerClick"
    />
</template>

<script setup>
import { computed, ref } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    label: { type: String, default: null },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: null },
    hint: { type: String, default: null },
    prependInnerIcon: { type: String, default: null },
    appendInnerIcon: { type: String, default: null },
    hideDetails: { type: [Boolean, String], default: 'auto' },
    passwordToggle: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'click:append-inner']);

const passwordVisible = ref(false);

const inputType = computed(() => {
    if (!props.passwordToggle) {
        return props.type;
    }

    return passwordVisible.value ? 'text' : 'password';
});

const resolvedAppendInnerIcon = computed(() => {
    if (!props.passwordToggle) {
        return props.appendInnerIcon;
    }

    return passwordVisible.value ? 'mdi-eye-off-outline' : 'mdi-eye-outline';
});

const handleAppendInnerClick = (event) => {
    if (props.passwordToggle) {
        passwordVisible.value = !passwordVisible.value;
    }

    emit('click:append-inner', event);
};
</script>
