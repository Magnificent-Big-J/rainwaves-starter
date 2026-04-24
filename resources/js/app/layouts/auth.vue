<template>
    <v-app>
        <div class="auth-layout">
            <section class="auth-layout__hero">
                <RouterLink to="/" class="auth-layout__brand">
                    <span class="auth-layout__badge">RW</span>
                    <div>
                        <div class="auth-layout__brand-title">Rainwaves Starter</div>
                        <div class="auth-layout__brand-meta">Secure app baseline</div>
                    </div>
                </RouterLink>

                <div class="auth-layout__copy">
                    <p class="auth-layout__eyebrow">Auth foundation</p>
                    <h1 class="auth-layout__title">
                        Secure access, recovery, and verification from one consistent flow.
                    </h1>
                    <p class="auth-layout__text">
                        This starter keeps session auth, CSRF, 2FA, and recovery behavior
                        as first-class platform concerns instead of leaving them to each app.
                    </p>
                    <div class="auth-layout__points">
                        <p>Session-first auth with Laravel Sanctum</p>
                        <p>Email OTP and authenticator app verification</p>
                        <p>Registration disabled by default</p>
                    </div>
                </div>
            </section>

            <section class="auth-layout__panel">
                <RouterView />
            </section>
        </div>
    </v-app>
</template>

<style scoped>
.auth-layout {
    min-height: 100vh;
    display: grid;
    background:
        radial-gradient(circle at top left, rgba(0, 106, 82, 0.12), transparent 30%),
        linear-gradient(180deg, #fbfaf6 0%, #f2eee4 100%);
}

.auth-layout__hero {
    display: none;
    padding: 2.5rem 3rem;
    border-inline-end: 1px solid rgba(17, 34, 51, 0.08);
}

.auth-layout__brand {
    display: inline-flex;
    align-items: center;
    gap: 0.85rem;
}

.auth-layout__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.7rem;
    height: 2.7rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #006a52, #0e8b6c);
    color: white;
    font-weight: 700;
}

.auth-layout__brand-title {
    font-weight: 700;
}

.auth-layout__brand-meta {
    font-size: 0.875rem;
    color: var(--starter-muted);
}

.auth-layout__copy {
    max-width: 36rem;
    margin: auto 0;
}

.auth-layout__eyebrow {
    margin: 0 0 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: var(--starter-accent);
    font-size: 0.8rem;
    font-weight: 700;
}

.auth-layout__title {
    margin: 0;
    font-size: clamp(2.4rem, 3.8vw, 4.5rem);
    line-height: 0.96;
}

.auth-layout__text {
    margin: 1.25rem 0 0;
    color: var(--starter-muted);
    font-size: 1.03rem;
}

.auth-layout__points {
    display: grid;
    gap: 0.5rem;
    margin-top: 1.5rem;
    font-weight: 600;
    color: #2e3e4f;
}

.auth-layout__points p {
    margin: 0;
}

.auth-layout__points p::before {
    content: '•';
    color: var(--starter-highlight);
    margin-right: 0.55rem;
}

.auth-layout__panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
}

@media (min-width: 960px) {
    .auth-layout {
        grid-template-columns: minmax(0, 1.25fr) minmax(360px, 0.75fr);
    }

    .auth-layout__hero {
        display: flex;
        flex-direction: column;
    }

    .auth-layout__panel {
        padding: 2rem 1.5rem;
    }
}
</style>
