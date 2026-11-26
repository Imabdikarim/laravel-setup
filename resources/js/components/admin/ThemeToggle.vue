<script setup>
import { useTheme } from '../../lib/theme';

const { isDark, toggle } = useTheme();
</script>

<template>
    <button class="tt" type="button" :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggle">
        <svg v-if="isDark" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
        </svg>
        <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
        </svg>
    </button>
</template>

<style scoped>
.tt {
    width: 42px;
    height: 42px;
    display: inline-grid;
    place-items: center;
    border: 1px solid color-mix(in srgb, currentColor 18%, transparent);
    border-radius: 12px;
    background: color-mix(in srgb, currentColor 5%, transparent);
    color: inherit;
    cursor: pointer;
    transition: background 0.15s;
}

.tt:hover {
    background: color-mix(in srgb, currentColor 11%, transparent);
}

.tt:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}
</style>