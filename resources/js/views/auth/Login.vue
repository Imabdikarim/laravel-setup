<script setup>
import { ref } from 'vue';
import AdminIcon from '../../components/admin/AdminIcon.vue';
import BrandMark from '../../components/admin/BrandMark.vue';

const { loginUrl, csrfToken, loginError, oldEmail } = document.getElementById('app').dataset;
const email = ref(oldEmail || '');
const showPassword = ref(false);
</script>

<template>
    <main class="ma-auth">
        <div class="ma-auth-art" aria-hidden="true">
            <div class="ma-orbit ma-orbit--one"></div>
            <div class="ma-orbit ma-orbit--two"></div>
            <div class="ma-orbit ma-orbit--three"></div>
            <div class="ma-orbit-core"><span>m<span>.</span></span></div>
            <div class="ma-orbit-satellite ma-orbit-satellite--one"></div>
            <div class="ma-orbit-satellite ma-orbit-satellite--two"></div>
        </div>

        <div class="ma-auth-side">
            <div class="ma-auth-content">
                <BrandMark />
                <div class="ma-auth-heading">
                    <h1>Sign in</h1>
                    <p>Use your admin account to access Munaasab.</p>
                </div>

                <form class="ma-login-form" method="POST" :action="loginUrl">
                    <input type="hidden" name="_token" :value="csrfToken">

                    <label class="ma-field" for="admin-email">
                        <span>Email address</span>
                        <input id="admin-email" type="email" name="email" v-model="email"
                               placeholder="you@example.com" autocomplete="username" required>
                    </label>

                    <div class="ma-field">
                        <label for="admin-password">Password</label>
                        <div class="ma-password-field">
                            <input id="admin-password" :type="showPassword ? 'text' : 'password'"
                                   name="password" autocomplete="current-password"
                                   placeholder="Enter your password" required>
                            <button class="ma-icon-button ma-password-toggle" type="button"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    @click="showPassword = !showPassword">
                                <AdminIcon :name="showPassword ? 'eyeOff' : 'eye'" :size="19" />
                            </button>
                        </div>
                    </div>

                    <p v-if="loginError" class="ma-form-error" role="alert">{{ loginError }}</p>

                    <button class="ma-primary-button ma-login-submit" type="submit">
                        Sign in <AdminIcon name="arrow" :size="19" />
                    </button>
                </form>
            </div>
        </div>
    </main>
</template>
