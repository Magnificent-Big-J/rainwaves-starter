<template>
    <div class="settings-page">
        <AppPageHeader
            eyebrow="Administration"
            title="Settings"
            subtitle="Current brand, navigation, and feature-flag configuration. Change values in config/app-brand.php, config/features.php, and config/navigation.php (or their .env overrides) — this page reads that config, it doesn't edit it."
        />

        <AppSectionCard title="Brand">
            <dl class="settings-grid">
                <div><dt>Name</dt><dd>{{ appConfig.brand.name }}</dd></div>
                <div><dt>Short name</dt><dd>{{ appConfig.brand.short_name }}</dd></div>
                <div><dt>Tagline</dt><dd>{{ appConfig.brand.tagline || '—' }}</dd></div>
                <div><dt>Support email</dt><dd>{{ appConfig.brand.support_email || '—' }}</dd></div>
                <div><dt>Footer</dt><dd>{{ appConfig.brand.footer || '—' }}</dd></div>
            </dl>
        </AppSectionCard>

        <AppSectionCard title="Feature flags">
            <dl class="settings-grid">
                <div>
                    <dt>Showcase pages</dt>
                    <dd>
                        <AppStatusBadge
                            :status="appConfig.features.show_showcase_pages ? 'active' : 'inactive'"
                            :label="appConfig.features.show_showcase_pages ? 'Enabled' : 'Disabled'"
                        />
                    </dd>
                </div>
                <div><dt>Environment</dt><dd>{{ appConfig.environment }}</dd></div>
            </dl>
        </AppSectionCard>

        <AppSectionCard title="Navigation" subtitle="Roles that put a session on the admin surface, and each surface's home route.">
            <dl class="settings-grid">
                <div><dt>Admin roles</dt><dd>{{ appConfig.navigation.admin_roles.join(', ') }}</dd></div>
                <div><dt>Admin home</dt><dd>{{ appConfig.navigation.home_routes.admin }}</dd></div>
                <div><dt>Customer home</dt><dd>{{ appConfig.navigation.home_routes.customer }}</dd></div>
            </dl>
        </AppSectionCard>

        <AppSectionCard
            title="Deployment readiness"
            subtitle="Run `php artisan starter:doctor --production` for a full environment/security check before going live."
        />
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Settings",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import { useAppConfigStore } from '../../stores/app-config';

const appConfig = useAppConfigStore();
</script>

<style scoped>
.settings-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
}

.settings-grid dt {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rw-dim);
    margin-bottom: 0.25rem;
}

.settings-grid dd {
    margin: 0;
    font-weight: 600;
    color: var(--rw-ink);
}
</style>
