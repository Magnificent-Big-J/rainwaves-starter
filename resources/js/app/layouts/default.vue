<template>
    <v-app>
        <v-layout class="starter-shell">
            <AppHeader :items="navItems" />

            <v-main>
                <RouterView />
            </v-main>
        </v-layout>
    </v-app>
</template>

<script setup>
const navItems = [
    { label: 'Home', to: '/' },
    { label: 'Foundation', to: '/foundation' },
    { label: 'Auth', to: '/auth/login' },
    { label: 'Profile', to: '/auth/profile' },
];
</script>

<style scoped>
.starter-shell {
    min-height: 100vh;
}
</style>
