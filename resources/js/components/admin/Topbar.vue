<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import AdminIcon from './AdminIcon.vue';
import ThemeToggle from './ThemeToggle.vue';

const props = defineProps({
    adminName: { type: String, required: true },
    logoutUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
});

defineEmits(['open-navigation']);

const route = useRoute();
const initials = computed(() => props.adminName.trim().split(/\s+/)
    .slice(0, 2).map((part) => part[0]).join('').toUpperCase());

// Profile dropdown
const open = ref(false);
const root = ref(null);
const trigger = ref(null);

function close(returnFocus = false) {
    open.value = false;
    if (returnFocus) trigger.value?.focus();
}

function onPointerDown(event) {
    if (open.value && root.value && !root.value.contains(event.target)) {
        open.value = false;
    }
}

function onKeydown(event) {
    if (event.key === 'Escape' && open.value) close(true);
}

onMounted(() => {
    document.addEventListener('pointerdown', onPointerDown);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onPointerDown);
    document.removeEventListener('keydown', onKeydown);
});

watch(() => route.fullPath, () => close());
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
            <ThemeToggle />

            <div ref="root" class="tb-profile">
                <button ref="trigger" class="tb-trigger" type="button" aria-haspopup="true"
                        aria-controls="profile-menu" :aria-expanded="open" @click="open = !open">
                    <span class="ma-avatar" aria-hidden="true">{{ initials }}</span>
                    <span class="ma-topbar-name">{{ adminName }}</span>
                    <svg class="tb-chevron" :class="{ 'tb-chevron--open': open }" width="16" height="16"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>

                <div v-if="open" id="profile-menu" class="tb-menu">
                    <div class="tb-who">
                        <strong>{{ adminName }}</strong>
                        <span>Administrator</span>
                    </div>

                    <RouterLink class="tb-item" :to="{ name: 'admin.account' }" @click="close()">My account</RouterLink>
                    <RouterLink class="tb-item" :to="{ name: 'admin.settings' }" @click="close()">Settings</RouterLink>

                    <hr class="tb-divider">

                    <form method="POST" :action="logoutUrl">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button class="tb-item tb-item--out" type="submit">
                            <AdminIcon name="logout" :size="18" />
                            <span>Sign out</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.ma-topbar-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.tb-profile {
    position: relative;
}

.tb-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px 10px 4px 4px;
    border: 1px solid transparent;
    border-radius: 999px;
    background: none;
    color: inherit;
    font: inherit;
    cursor: pointer;
}

.tb-trigger:hover,
.tb-trigger[aria-expanded="true"] {
    background: color-mix(in srgb, currentColor 8%, transparent);
}

.tb-trigger:focus-visible,
.tb-item:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.tb-chevron {
    transition: transform 0.15s;
}

.tb-chevron--open {
    transform: rotate(180deg);
}

.tb-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 50;
    min-width: 220px;
    padding: 6px;
    border: 1px solid color-mix(in srgb, currentColor 16%, transparent);
    border-radius: 14px;
    background: Canvas;
    color: CanvasText;
    box-shadow: 0 12px 32px -8px rgb(0 0 0 / 0.28);
}

.tb-who {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 10px 12px 12px;
}

.tb-who span {
    font-size: 0.85rem;
    color: color-mix(in srgb, currentColor 62%, transparent);
}

.tb-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    box-sizing: border-box;
    padding: 10px 12px;
    border: 0;
    border-radius: 10px;
    background: none;
    color: inherit;
    font: inherit;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
}

.tb-item:hover {
    background: color-mix(in srgb, currentColor 8%, transparent);
}

.tb-item--out {
    color: #b42318;
}

:root[data-theme="dark"] .tb-item--out {
    color: #ffb4ab;
}

.tb-divider {
    margin: 6px 0;
    border: 0;
    border-top: 1px solid color-mix(in srgb, currentColor 14%, transparent);
}
</style>