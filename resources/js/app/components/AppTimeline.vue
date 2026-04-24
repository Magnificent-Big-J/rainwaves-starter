<template>
    <div class="timeline">
        <div
            v-for="item in items"
            :key="item.id ?? `${item.title}-${item.time}`"
            class="timeline__item"
        >
            <span class="timeline__dot" :class="`timeline__dot--${item.type || 'info'}`" />
            <div class="timeline__content">
                <div class="timeline__meta">
                    <strong>{{ item.title }}</strong>
                    <span>{{ item.time }}</span>
                </div>
                <p class="timeline__text">{{ item.text }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    items: { type: Array, default: () => [] },
});
</script>

<style scoped>
.timeline {
    display: grid;
    gap: 1rem;
}

.timeline__item {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.85rem;
    align-items: flex-start;
}

.timeline__dot {
    width: 0.9rem;
    height: 0.9rem;
    border-radius: 999px;
    margin-top: 0.35rem;
}

.timeline__dot--success { background: #15803d; }
.timeline__dot--warning { background: #b45309; }
.timeline__dot--error { background: #b91c1c; }
.timeline__dot--info { background: #0369a1; }

.timeline__content {
    padding-left: 0.9rem;
    border-left: 1px solid var(--rw-border);
}

.timeline__meta {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    font-size: 0.86rem;
    color: var(--rw-ink-2);
}

.timeline__meta span {
    color: var(--rw-muted);
}

.timeline__text {
    margin: 0.35rem 0 0;
    line-height: 1.6;
    color: var(--rw-muted);
}
</style>
