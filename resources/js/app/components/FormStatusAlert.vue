<template>
    <v-alert
        v-if="message"
        :type="type"
        variant="tonal"
        rounded="xl"
    >
        {{ message }}
    </v-alert>
</template>

<script setup>
defineProps({
    message: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'error',
    },
});
</script>
