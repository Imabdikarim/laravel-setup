<script setup>
import { RouterLink } from 'vue-router';
import AdminIcon from '../../components/admin/AdminIcon.vue';

const sections = [
    {
        route: 'admin.projects',
        title: 'Projects',
        description: 'Drafts, reviews, and published work.',
        icon: 'projects',
    },
    {
        route: 'admin.settings',
        title: 'Settings',
        description: 'Add and manage the people who can sign in.',
        icon: 'account',
    },
    {
        route: 'admin.account',
        title: 'My account',
        description: 'View the account you’re signed in with.',
        icon: 'account',
    },
];

const { adminName } = document.getElementById('app').dataset;
</script>

<template>
    <section aria-labelledby="overview-title">
        <div class="ma-welcome">
            <div>
                <h1 id="overview-title">Welcome back, {{ adminName }}.</h1>
            </div>
            <RouterLink class="ma-primary-button ma-welcome-link" :to="{ name: 'admin.projects' }">
                View Projects <AdminIcon name="arrow" :size="18" />
            </RouterLink>
        </div>

        <h2 class="ma-section-title">Workspace</h2>
        <div class="ma-section-grid">
            <RouterLink v-for="section in sections" :key="section.route" class="ma-section-card"
                        :to="{ name: section.route }">
                <span class="ma-card-icon"><AdminIcon :name="section.icon" :size="22" /></span>
                <span class="ma-card-content">
                    <span class="ma-card-title">{{ section.title }}</span>
                    <span class="ma-card-description">{{ section.description }}</span>
                </span>
                <AdminIcon class="ma-card-arrow" name="arrow" :size="18" />
            </RouterLink>
        </div>
    </section>
</template>
