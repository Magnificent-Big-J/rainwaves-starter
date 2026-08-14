<template>
    <AuthCard eyebrow="Before you continue" title="Updated legal documents" :subtitle="subtitle">
        <FormStatusAlert :message="message" type="error" />

        <ul class="legal-list">
            <li
                v-for="document in legal.documents.filter((d) => d.accepted_version !== d.version)"
                :key="document.document"
                class="legal-list__item"
            >
                <span class="legal-list__name">{{ documentLabel(document.document) }}</span>
                <a :href="documentUrl(document.document)" target="_blank" rel="noopener" class="legal-list__link">
                    Review
                    <v-icon size="14">mdi-open-in-new</v-icon>
                </a>
            </li>
        </ul>

        <v-btn color="primary" block :loading="accepting" @click="acceptAll">Accept and continue</v-btn>
    </AuthCard>
</template>

<route lang="json">
{
    "meta": {
        "layout": "auth",
        "title": "Legal Documents",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import AuthCard from '../../components/AuthCard.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import { useAppConfigStore } from '../../stores/app-config';
import { useLegalStore } from '../../stores/legal';
import { useSessionStore } from '../../stores/session';

const appConfig = useAppConfigStore();
const legal = useLegalStore();
const session = useSessionStore();
const router = useRouter();

const message = ref('');
const accepting = ref(false);

const subtitle = computed(
    () => "We've updated the documents below since you last accepted them — please review and accept to continue."
);

const documentLabels = { terms: 'Terms of Service', privacy: 'Privacy Policy' };
const documentLabel = (document) => documentLabels[document] ?? document;

const documentUrls = {
    terms: appConfig.brand.legal?.terms_url ?? '/legal/terms',
    privacy: appConfig.brand.legal?.privacy_url ?? '/legal/privacy',
};
const documentUrl = (document) => documentUrls[document] ?? '/';

const acceptAll = async () => {
    message.value = '';
    accepting.value = true;

    const outstanding = legal.documents.filter((d) => d.accepted_version !== d.version).map((d) => d.document);
    const result = await legal.accept(outstanding);

    accepting.value = false;

    if (result.ok) {
        router.push(session.homeRoute);

        return;
    }

    message.value = result.message;
};

onMounted(async () => {
    await legal.ensureLoaded();

    if (!legal.hasOutstandingDocuments) {
        router.push(session.homeRoute);
    }
});
</script>

<style scoped>
.legal-list {
    list-style: none;
    margin: 0 0 1.5rem;
    padding: 0;
    display: grid;
    gap: 0.5rem;
}

.legal-list__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 0.9rem;
    border-radius: 10px;
    background: rgba(17, 34, 51, 0.03);
    font-size: 0.88rem;
}

.legal-list__name {
    font-weight: 600;
}

.legal-list__link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--rw-700);
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
}

.legal-list__link:hover {
    text-decoration: underline;
}
</style>
