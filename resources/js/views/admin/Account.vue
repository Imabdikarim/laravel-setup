<script setup>
const { adminName } = document.getElementById('app').dataset;
</script>

<template>
    <section aria-labelledby="account-title">
        <header class="ma-page-heading">
            <h1 id="account-title">My account</h1>
        </header>
        <dl class="ma-account-details">
            <div><dt>Name</dt><dd>{{ adminName }}</dd></div>
            <div><dt>Access</dt><dd>Administrator</dd></div>
        </dl>
    </section>
</template>
