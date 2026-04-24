<template>
    <v-app>
        <v-layout class="starter-shell">
            <AppHeader :items="navItems" />

            <v-main>
                <router-view />
            </v-main>
        </v-layout>
    </v-app>
</template>

<script setup>
import AppHeader from './components/AppHeader.vue';

const navItems = [
    { label: 'Home', to: '/' },
    { label: 'Foundation', to: '/foundation' },
];
</script>

<style scoped>
.starter-shell {
    min-height: 100vh;
}
</style>
