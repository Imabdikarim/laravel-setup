<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import AdminIcon from './AdminIcon.vue';

const props = defineProps({
    adminName: { type: String, required: true },
    logoutUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
});

defineEmits(['open-navigation']);

const route = useRoute();
const initials = computed(() => props.adminName.trim().split(/\s+/)
    .slice(0, 2).map((part) => part[0]).join('').toUpperCase());
</script>

<template>
    <header class="ma-topbar">
        <div class="ma-topbar-title">
            <button class="ma-icon-button ma-menu-button" type="button" aria-label="Open navigation"
                    aria-haspopup="dialog" @click="$emit('open-navigation')">
                <AdminIcon name="menu" />
            </button>
            <span>{{ route.meta.title }}</span>
        </div>
        <div class="ma-topbar-actions">
            <RouterLink :to="{ name: 'admin.account' }" class="ma-account-link" aria-label="My account">
                <span class="ma-avatar" aria-hidden="true">{{ initials }}</span>
                <span class="ma-topbar-name">{{ adminName }}</span>
            </RouterLink>
            <form method="POST" :action="logoutUrl">
                <input type="hidden" name="_token" :value="csrfToken">
                <button class="ma-logout-button" type="submit" aria-label="Log out">
                    <AdminIcon name="logout" :size="18" />
                    <span>Log out</span>
                </button>
            </form>
        </div>
    </header>
</template>
