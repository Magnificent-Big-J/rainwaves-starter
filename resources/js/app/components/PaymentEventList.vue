<template>
    <AppSectionCard :title="title" :subtitle="subtitle">
        <AppTimeline :items="timelineItems" />
    </AppSectionCard>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: 'Payment events' },
    subtitle: { type: String, default: 'Starter event timeline primitive' },
    events: { type: Array, default: () => [] },
});

const timelineItems = computed(() =>
    props.events.map((event) => ({
        id: event.id,
        title: event.title || event.eventType || 'Event',
        time: event.time || event.receivedAt || 'Unknown',
        text: event.text || event.description || 'No details provided.',
        type: event.type || event.status || 'info',
    }))
);
</script>
