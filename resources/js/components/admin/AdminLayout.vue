<script setup>
import { ref } from 'vue';
import { RouterView } from 'vue-router';
import Sidebar from './Sidebar.vue';
import Topbar from './Topbar.vue';

const navigation = ref(null);
const { adminName, csrfToken, logoutUrl } = document.getElementById('app').dataset;

function closeNavigation() {
    navigation.value?.close();
}
</script>

<template>
    <div class="ma-shell">
        <a class="ma-skip-link" href="#admin-main">Skip to content</a>
        <aside class="ma-desktop-sidebar">
            <Sidebar />
        </aside>

        <dialog ref="navigation" class="ma-mobile-nav" aria-label="Admin navigation"
                @click.self="closeNavigation">
            <Sidebar compact @close="closeNavigation" @navigate="closeNavigation" />
        </dialog>

        <div class="ma-workspace">
            <Topbar :admin-name="adminName" :csrf-token="csrfToken" :logout-url="logoutUrl"
                    @open-navigation="navigation.showModal()" />
            <main id="admin-main" class="ma-main" tabindex="-1">
                <RouterView />
            </main>
        </div>
    </div>
</template>
