<script setup>
import { RouterLink } from 'vue-router';
import AdminIcon from './AdminIcon.vue';

defineEmits(['navigate', 'close']);
defineProps({ compact: Boolean });

const workspaceLinks = [
    { name: 'admin.dashboard', label: 'Overview', icon: 'overview' },
    { name: 'admin.projects', label: 'Projects', icon: 'projects' },
];
</script>

<template>
    <div class="ma-sidebar">
        <div class="ma-sidebar-heading">
            <RouterLink class="sb-brand" :to="{ name: 'admin.dashboard' }" aria-label="basiit, go to overview"
                        @click="$emit('navigate')">
                basiit<span>.</span>
            </RouterLink>
            <button v-if="compact" class="ma-icon-button ma-sidebar-close" type="button"
                    aria-label="Close navigation" @click="$emit('close')">
                <AdminIcon name="close" />
            </button>
        </div>

        <nav class="ma-sidebar-nav" aria-label="Admin navigation">
            <p class="ma-nav-label">Workspace</p>
            <RouterLink v-for="link in workspaceLinks" :key="link.name" :to="{ name: link.name }"
                        class="ma-nav-link" @click="$emit('navigate')">
                <AdminIcon :name="link.icon" />
                {{ link.label }}
            </RouterLink>

            <p class="ma-nav-label ma-nav-section">Account</p>
            <RouterLink :to="{ name: 'admin.account' }" class="ma-nav-link" @click="$emit('navigate')">
                <AdminIcon name="account" />
                My account
            </RouterLink>
            <RouterLink :to="{ name: 'admin.settings' }" class="ma-nav-link" @click="$emit('navigate')">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="3" />
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                </svg>
                Settings
            </RouterLink>
        </nav>
    </div>
</template>

<style scoped>
.sb-brand {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: inherit;
    text-decoration: none;
}

.sb-brand span {
    color: #0b6bcb;
}

:root[data-theme="dark"] .sb-brand span {
    color: #5db2ff;
}

.sb-brand:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 3px;
    border-radius: 6px;
}
</style>