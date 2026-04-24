<template>
    <v-app>
        <div class="auth-shell">
            <div class="auth-shell__canvas">
                <!-- Geometric rings -->
                <div class="ring ring--1" />
                <div class="ring ring--2" />
                <div class="ring ring--3" />
                <!-- Grid overlay -->
                <div class="auth-shell__grid" />
            </div>

            <div class="auth-shell__body">
                <div class="auth-header">
                    <RouterLink to="/" class="auth-logo">
                        <span class="auth-logo__badge">RW</span>
                        <span class="auth-logo__name">Rainwaves</span>
                    </RouterLink>
                </div>

                <div class="auth-stage">
                    <RouterView />
                </div>

                <footer class="auth-footer">
                    <span>Rainwaves Starter &mdash; Laravel 13</span>
                </footer>
            </div>
        </div>
    </v-app>
</template>

<style scoped>
.auth-shell {
    min-height: 100vh;
    display: flex;
    align-items: stretch;
    background: #011d12;
    position: relative;
    overflow: hidden;
}

/* ── Geometric canvas ──────────────────────────────── */
.auth-shell__canvas {
    position: fixed;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.ring {
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(0, 135, 95, 0.18);
}

.ring--1 {
    width: 680px;
    height: 680px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border-color: rgba(0, 135, 95, 0.14);
}

.ring--2 {
    width: 420px;
    height: 420px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border-color: rgba(0, 135, 95, 0.22);
}

.ring--3 {
    width: 220px;
    height: 220px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: radial-gradient(circle, rgba(0, 135, 95, 0.1), transparent 70%);
    border: none;
}

.auth-shell__grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0, 135, 95, 0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 135, 95, 0.06) 1px, transparent 1px);
    background-size: 48px 48px;
}

/* ── Body ──────────────────────────────────────────── */
.auth-shell__body {
    position: relative;
    z-index: 1;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem 1.25rem 2.5rem;
    min-height: 100vh;
}

/* ── Header ────────────────────────────────────────── */
.auth-header {
    width: 100%;
    max-width: 480px;
    margin-bottom: 2.25rem;
}

.auth-logo {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    text-decoration: none;
}

.auth-logo__badge {
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

.auth-logo__name {
    font-size: 1rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    letter-spacing: -0.01em;
}

/* ── Stage (form area) ─────────────────────────────── */
.auth-stage {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}

/* ── Footer ────────────────────────────────────────── */
.auth-footer {
    margin-top: 2rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.22);
    letter-spacing: 0.04em;
}
</style>
