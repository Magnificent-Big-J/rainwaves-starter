<template>
    <div class="home-page">
        <section class="hero">
            <div class="hero__copy">
                <p class="hero__eyebrow">Internal Rainwaves starter</p>
                <h1 class="hero__title">
                    A clean baseline for secure apps, not another recycled template.
                </h1>
                <p class="hero__text">
                    This starter is being built to standardize auth, permissions, media,
                    payments, and deployment defaults without carrying forward UI baggage.
                </p>
                <div class="hero__actions">
                    <v-btn color="primary" size="large" to="/foundation">
                        View foundation
                    </v-btn>
                    <v-btn variant="outlined" size="large">
                        Laravel 13 ready
                    </v-btn>
                </div>
            </div>

            <v-card class="hero__panel">
                <v-card-text>
                    <div class="hero__grid">
                        <div>
                            <div class="hero__metric">Auth-first</div>
                            <p>Session auth, 2FA, registration disabled by default.</p>
                        </div>
                        <div>
                            <div class="hero__metric">Admin-ready</div>
                            <p>Permissions, roles, auditability, and profile/media flows.</p>
                        </div>
                        <div>
                            <div class="hero__metric">Payment baseline</div>
                            <p>PayFast integration stays part of the core starter contract.</p>
                        </div>
                    </div>
                </v-card-text>
            </v-card>
        </section>
    </div>
</template>

<style scoped>
.home-page {
    padding: 2rem 1.25rem 4rem;
}

.hero {
    display: grid;
    gap: 1.5rem;
    max-width: 1180px;
    margin: 0 auto;
}

.hero__copy {
    padding: 2rem;
    border-radius: 28px;
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.72), rgba(235, 225, 207, 0.9));
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.hero__eyebrow {
    margin: 0 0 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: var(--starter-accent);
    font-size: 0.8rem;
    font-weight: 700;
}

.hero__title {
    max-width: 12ch;
    margin: 0;
    font-size: clamp(2.6rem, 6vw, 5.4rem);
    line-height: 0.94;
}

.hero__text {
    max-width: 52rem;
    margin: 1.5rem 0 0;
    color: var(--starter-muted);
    font-size: 1.05rem;
}

.hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1.75rem;
}

.hero__panel {
    background: rgba(255, 253, 248, 0.9);
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.hero__grid {
    display: grid;
    gap: 1rem;
}

.hero__metric {
    margin-bottom: 0.35rem;
    font-size: 1.2rem;
    font-weight: 700;
}

@media (min-width: 960px) {
    .hero {
        grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.7fr);
        align-items: stretch;
    }
}
</style>
