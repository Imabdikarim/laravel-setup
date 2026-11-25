<script setup>
import AdminIcon from '../../components/admin/AdminIcon.vue';
</script>

<template>
    <section aria-labelledby="projects-title">
        <header class="ma-page-heading">
            <h1 id="projects-title">Projects</h1>
        </header>
        <div class="ma-empty-state">
            <span class="ma-card-icon"><AdminIcon name="projects" :size="24" /></span>
            <h2>Projects aren’t connected yet</h2>
            <p>Project drafts and published items will appear here once the Projects module is connected.</p>
        </div>
    </section>
</template>
