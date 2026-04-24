<template>
    <div class="foundation-page">
        <div class="foundation-page__wrap">
            <div>
                <p class="foundation-page__eyebrow">Current foundation</p>
                <h1 class="foundation-page__title">Starter scaffold status</h1>
            </div>

            <v-row class="mt-2" dense>
                <v-col
                    v-for="item in items"
                    :key="item.title"
                    cols="12"
                    md="6"
                >
                    <v-card class="foundation-card">
                        <v-card-title>{{ item.title }}</v-card-title>
                        <v-card-text>{{ item.body }}</v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </div>
    </div>
</template>

<script setup>
const items = [
    {
        title: 'Backend',
        body: 'Laravel 13 scaffolded with Sail added for the Docker-first local stack.',
    },
    {
        title: 'Frontend',
        body: 'Vue 3, Vue Router, Pinia, and Vuetify are wired into a clean SPA entrypoint.',
    },
    {
        title: 'Next',
        body: 'Move into environment defaults, auth package integration, and starter route structure.',
    },
    {
        title: 'Constraint',
        body: 'Local Node is still on 18.x, while the current Vite toolchain expects Node 20.19+.',
    },
];
</script>

<style scoped>
.foundation-page {
    padding: 2rem 1.25rem 4rem;
}

.foundation-page__wrap {
    max-width: 1180px;
    margin: 0 auto;
}

.foundation-page__eyebrow {
    margin: 0;
    color: var(--starter-accent);
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.8rem;
    font-weight: 700;
}

.foundation-page__title {
    margin: 0.75rem 0 0;
    font-size: clamp(2.2rem, 4vw, 3.6rem);
}

.foundation-card {
    height: 100%;
    background: rgba(255, 253, 248, 0.9);
    border: 1px solid rgba(17, 34, 51, 0.08);
}
</style>
