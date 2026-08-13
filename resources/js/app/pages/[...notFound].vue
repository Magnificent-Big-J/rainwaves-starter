<template>
    <div class="not-found">
        <p class="not-found__code">404</p>
        <h1 class="not-found__heading">That page doesn't exist</h1>
        <p class="not-found__message">
            The link might be old, the address may have been mistyped, or this feature isn't enabled in this
            environment.
        </p>
        <RouterLink to="/" class="not-found__cta">Back to safety</RouterLink>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "guest",
        "title": "Not found"
    }
}
</route>

<script setup>
import { RouterLink } from 'vue-router';
</script>

<style scoped>
.not-found {
    max-width: 26rem;
    margin: 4rem auto;
    padding: 0 1.5rem;
    text-align: center;
}

.not-found__code {
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(0, 191, 137, 0.85);
    margin: 0 0 0.5rem;
}

.not-found__heading {
    font-size: 1.6rem;
    color: rgba(255, 255, 255, 0.92);
    margin: 0 0 0.75rem;
}

.not-found__message {
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.5;
    margin: 0 0 1.75rem;
}

.not-found__cta {
    display: inline-block;
    padding: 0.65rem 1.5rem;
    border-radius: 0.6rem;
    background: rgba(0, 135, 95, 0.2);
    border: 1px solid rgba(0, 135, 95, 0.45);
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}
</style>
