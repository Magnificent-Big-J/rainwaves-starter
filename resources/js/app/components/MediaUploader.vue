<template>
    <div class="media-uploader">
        <div class="media-uploader__preview">
            <v-avatar :size="avatarSize" color="primary" variant="tonal" class="media-uploader__avatar">
                <v-img
                    v-if="previewUrl"
                    :src="previewUrl"
                    :alt="alt"
                    cover
                />
                <span v-else class="media-uploader__initials">{{ initials }}</span>
            </v-avatar>

            <div class="media-uploader__actions">
                <v-btn
                    size="small"
                    variant="tonal"
                    color="primary"
                    prepend-icon="mdi-upload"
                    @click="triggerPicker"
                >
                    Upload
                </v-btn>
                <v-btn
                    v-if="previewUrl"
                    size="small"
                    variant="text"
                    color="error"
                    icon="mdi-delete-outline"
                    @click="remove"
                />
            </div>
        </div>

        <input
            ref="input"
            type="file"
            accept="image/*"
            class="media-uploader__input"
            @change="onPick"
        />

        <p class="media-uploader__hint">
            {{ hint }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [File, null], default: null },
    currentUrl: { type: String, default: null },
    name: { type: String, default: '' },
    alt: { type: String, default: 'Avatar' },
    avatarSize: { type: [Number, String], default: 88 },
    hint: { type: String, default: 'JPG or PNG, max 2 MB.' },
});

const emit = defineEmits(['update:modelValue', 'remove']);

const input = ref(null);
const localPreview = ref(null);

watch(
    () => props.modelValue,
    (file) => {
        if (!file) {
            localPreview.value = null;
            return;
        }

        const reader = new FileReader();

        reader.onload = (e) => (localPreview.value = e.target.result);
        reader.readAsDataURL(file);
    }
);

const previewUrl = computed(() => localPreview.value || props.currentUrl || null);

const initials = computed(() => {
    return (props.name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() || '')
        .join('');
});

const triggerPicker = () => input.value?.click();

const onPick = (event) => {
    const file = event.target.files?.[0];

    if (file) {
        emit('update:modelValue', file);
    }

    event.target.value = '';
};

const remove = () => {
    localPreview.value = null;
    emit('update:modelValue', null);
    emit('remove');
};
</script>

<style scoped>
.media-uploader {
    display: grid;
    gap: 0.5rem;
}

.media-uploader__preview {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.media-uploader__avatar {
    flex-shrink: 0;
}

.media-uploader__initials {
    font-size: 1.5rem;
    font-weight: 700;
}

.media-uploader__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.media-uploader__input {
    display: none;
}

.media-uploader__hint {
    margin: 0;
    font-size: 0.8rem;
    color: var(--rw-muted);
}
</style>
