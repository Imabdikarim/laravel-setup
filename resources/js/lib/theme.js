import { ref } from 'vue';
import './admin-dark.css';

// Light / dark theme shared by the whole app.
// Sets data-theme="light|dark" and color-scheme on <html>, and remembers the choice.
// With no saved choice it follows the system setting.

const KEY = 'basiit-theme';
const isDark = ref(false);
let mode = 'system';
let ready = false;

function systemPrefersDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function apply() {
    isDark.value = mode === 'dark' || (mode === 'system' && systemPrefersDark());

    const root = document.documentElement;
    root.dataset.theme = isDark.value ? 'dark' : 'light';
    root.classList.toggle('dark', isDark.value);
    root.style.colorScheme = isDark.value ? 'dark' : 'light';
}

function init() {
    if (ready) return;
    ready = true;

    try {
        const saved = localStorage.getItem(KEY);
        if (saved === 'dark' || saved === 'light') mode = saved;
    } catch {
        // Storage can be blocked; the theme just won't be remembered.
    }

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (mode === 'system') apply();
    });

    apply();
}

export function useTheme() {
    init();

    function toggle() {
        mode = isDark.value ? 'light' : 'dark';

        try {
            localStorage.setItem(KEY, mode);
        } catch {
            // Ignore: see above.
        }

        apply();
    }

    return { isDark, toggle };
}