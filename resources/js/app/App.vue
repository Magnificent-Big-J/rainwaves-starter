<template>
    <v-app>
        <v-layout class="starter-shell">
            <v-app-bar flat class="starter-bar">
                <div class="starter-brand">
                    <span class="starter-badge">RW</span>
                    <div>
                        <div class="starter-brand__title">Rainwaves Starter</div>
                        <div class="starter-brand__meta">Laravel 13 + Vue + Vuetify</div>
                    </div>
                </div>
                <v-spacer />
                <v-btn
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    variant="text"
                    rounded="xl"
                >
                    {{ item.label }}
                </v-btn>
            </v-app-bar>

            <v-main>
                <router-view />
            </v-main>
        </v-layout>
    </v-app>
</template>

<script setup>
const navItems = [
    { label: 'Home', to: '/' },
    { label: 'Foundation', to: '/foundation' },
];
</script>

<style scoped>
.starter-shell {
    min-height: 100vh;
}

.starter-bar {
    padding-inline: 1rem;
    background: rgba(247, 243, 234, 0.86);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(17, 34, 51, 0.08);
}

.starter-brand {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.starter-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #006a52, #0e8b6c);
    color: white;
    font-weight: 700;
    letter-spacing: 0.08em;
}

.starter-brand__title {
    font-weight: 700;
}

.starter-brand__meta {
    color: var(--starter-muted);
    font-size: 0.875rem;
}
</style>
