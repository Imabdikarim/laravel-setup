<script setup>
import { RouterLink } from 'vue-router';
import AdminIcon from './AdminIcon.vue';
import BrandMark from './BrandMark.vue';

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
            <BrandMark />
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
        </nav>
    </div>
</template>
