<template>
    <div class="form-actions">
        <v-btn
            block
            type="submit"
            size="large"
            color="primary"
            :loading="loading"
        >
            {{ submitLabel }}
        </v-btn>

        <div
            v-if="leftLinkLabel || rightLinkLabel"
            class="form-actions__links"
        >
            <RouterLink
                v-if="leftLinkLabel"
                :to="leftLinkTo"
            >
                {{ leftLinkLabel }}
            </RouterLink>
            <span v-else />

            <RouterLink
                v-if="rightLinkLabel"
                :to="rightLinkTo"
            >
                {{ rightLinkLabel }}
            </RouterLink>
            <span v-else />
        </div>
    </div>
</template>

<script setup>
defineProps({
    submitLabel: {
        type: String,
        default: 'Submit',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    leftLinkLabel: {
        type: String,
        default: '',
    },
    leftLinkTo: {
        type: String,
        default: '',
    },
    rightLinkLabel: {
        type: String,
        default: '',
    },
    rightLinkTo: {
        type: String,
        default: '',
    },
});
</script>

<style scoped>
.form-actions {
    display: grid;
    gap: 0.9rem;
}

.form-actions__links {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    font-size: 0.9rem;
    font-weight: 600;
}
</style>
