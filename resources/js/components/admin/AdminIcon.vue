<script setup>
defineProps({
    name: { type: String, required: true },
    size: { type: Number, default: 20 },
});

const paths = {
    overview: 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
    projects: 'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
    account: 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2',
    logout: 'M10 17l5-5-5-5 M7 12h8 M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5',
    arrow: 'M4 12h15 M13 6l6 6-6 6',
    menu: 'M4 6h16 M4 12h16 M4 18h16',
    close: 'M6 6l12 12 M18 6L6 18',
    eye: 'M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z',
    eyeOff: 'M3 3l18 18 M10.6 6.1A11 11 0 0 1 12 6c6.4 0 10 6 10 6a17 17 0 0 1-2.6 3.1 M6.3 7.8A16 16 0 0 0 2 12s3.6 6 10 6c1.5 0 2.8-.3 4-.8',
    lock: 'M6 11h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2z M8 11V7a4 4 0 1 1 8 0v4',
};
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path :d="paths[name]" />
    </svg>
</template>
