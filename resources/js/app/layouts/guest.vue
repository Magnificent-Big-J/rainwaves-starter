<template>
    <v-app>
        <div class="guest-shell">
            <div class="guest-shell__canvas">
                <div class="ring ring--1" />
                <div class="ring ring--2" />
                <div class="ring ring--3" />
                <div class="guest-shell__grid" />
            </div>

            <div class="guest-shell__body">
                <header class="guest-header">
                    <RouterLink to="/" class="guest-logo">
                        <span class="guest-logo__badge">RW</span>
                        <span class="guest-logo__name">Rainwaves</span>
                    </RouterLink>
                    <nav class="guest-links">
                        <RouterLink to="/about" class="guest-links__item">About</RouterLink>
                        <RouterLink to="/auth/register" class="guest-links__item">Register</RouterLink>
                        <RouterLink to="/auth/login" class="guest-links__cta">Sign in</RouterLink>
                    </nav>
                </header>

                <main class="guest-main">
                    <RouterView />
                </main>

                <footer class="guest-footer">
                    <span>Rainwaves Starter &mdash; Laravel 13</span>
                </footer>
            </div>
        </div>
    </v-app>
</template>

<style scoped>
.guest-shell {
    min-height: 100vh;
    background: #011d12;
    position: relative;
    overflow-x: hidden;
}

/* ── Canvas ────────────────────────────────────────── */
.guest-shell__canvas {
    position: fixed;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.ring {
    position: absolute;
    border-radius: 50%;
}

.ring--1 {
    width: 900px;
    height: 900px;
    top: -200px;
    right: -300px;
    border: 1px solid rgba(0, 135, 95, 0.1);
}

.ring--2 {
    width: 560px;
    height: 560px;
    top: -80px;
    right: -100px;
    border: 1px solid rgba(0, 135, 95, 0.18);
}

.ring--3 {
    width: 280px;
    height: 280px;
    bottom: 10%;
    left: -80px;
    border: 1px solid rgba(0, 135, 95, 0.12);
    background: radial-gradient(circle, rgba(0, 135, 95, 0.06), transparent 70%);
}

.guest-shell__grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0, 135, 95, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 135, 95, 0.05) 1px, transparent 1px);
    background-size: 48px 48px;
}

/* ── Body ──────────────────────────────────────────── */
.guest-shell__body {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* ── Header ────────────────────────────────────────── */
.guest-header {
    display: flex;
    align-items: center;
    padding: 1.25rem 2rem;
    border-bottom: 1px solid rgba(0, 135, 95, 0.12);
}

.guest-logo {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    text-decoration: none;
}

.guest-logo__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 8px;
    background: linear-gradient(145deg, #005237, #00875f);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    box-shadow: 0 0 0 1px rgba(255,255,255,0.08), 0 2px 8px rgba(0,0,0,0.4);
}

.guest-logo__name {
    font-size: 1rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.88);
    letter-spacing: -0.01em;
}

.guest-links {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-left: auto;
}

.guest-links__item {
    padding: 0.4rem 0.875rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.5);
    border-radius: 0.5rem;
    text-decoration: none;
    transition: background 0.12s, color 0.12s;
}

.guest-links__item:hover {
    color: rgba(255, 255, 255, 0.88);
    background: rgba(255, 255, 255, 0.06);
}

.guest-links__cta {
    padding: 0.4rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    background: rgba(0, 135, 95, 0.2);
    border: 1px solid rgba(0, 135, 95, 0.4);
    border-radius: 0.5rem;
    text-decoration: none;
    transition: background 0.12s, border-color 0.12s;
}

.guest-links__cta:hover {
    background: rgba(0, 135, 95, 0.3);
    border-color: rgba(0, 135, 95, 0.6);
}

/* ── Main ──────────────────────────────────────────── */
.guest-main {
    flex: 1;
}

/* ── Footer ────────────────────────────────────────── */
.guest-footer {
    padding: 1.5rem 2rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.2);
    letter-spacing: 0.04em;
    border-top: 1px solid rgba(0, 135, 95, 0.08);
}
</style>
