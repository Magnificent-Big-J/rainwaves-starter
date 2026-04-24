<template>
    <button class="notification-item" :class="{ 'notification-item--read': Boolean(item.readAt) }" @click="$emit('select', item)">
        <span class="notification-item__icon" :class="`notification-item__icon--${item.type}`">
            <v-icon :icon="item.icon" size="17" />
        </span>

        <span class="notification-item__copy">
            <span class="notification-item__meta">
                <strong>{{ item.title }}</strong>
                <span>{{ item.timeLabel }}</span>
            </span>
            <span class="notification-item__message">{{ item.message }}</span>
            <span class="notification-item__footer">
                <span class="notification-item__category">{{ item.category }}</span>
                <span v-if="item.actionLabel">{{ item.actionLabel }}</span>
            </span>
        </span>
    </button>
</template>

<script setup>
defineProps({
    item: { type: Object, required: true },
});

defineEmits(['select']);
</script>

<style scoped>
.notification-item {
    width: 100%;
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.85rem;
    padding: 0.9rem 1rem;
    text-align: left;
    background: transparent;
    border: none;
    border-bottom: 1px solid rgba(17, 34, 51, 0.06);
    cursor: pointer;
}

.notification-item:hover {
    background: rgba(17, 34, 51, 0.03);
}

.notification-item--read {
    opacity: 0.7;
}

.notification-item__icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.notification-item__icon--success { background: rgba(21, 128, 61, 0.12); color: #15803d; }
.notification-item__icon--error { background: rgba(185, 28, 28, 0.12); color: #b91c1c; }
.notification-item__icon--warning { background: rgba(180, 83, 9, 0.12); color: #b45309; }
.notification-item__icon--info { background: rgba(3, 105, 161, 0.12); color: #0369a1; }

.notification-item__copy {
    display: grid;
    gap: 0.3rem;
}

.notification-item__meta,
.notification-item__footer {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.76rem;
}

.notification-item__meta span,
.notification-item__footer {
    color: var(--starter-muted);
}

.notification-item__message {
    font-size: 0.87rem;
    line-height: 1.55;
}

.notification-item__category {
    text-transform: uppercase;
    letter-spacing: 0.12em;
}
</style>
