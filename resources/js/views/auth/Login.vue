<script setup>
import { onMounted, ref } from 'vue';
import AdminIcon from '../../components/admin/AdminIcon.vue';
import ThemeToggle from '../../components/admin/ThemeToggle.vue';
import { useTheme } from '../../lib/theme';

useTheme();

const { loginUrl, csrfToken, loginError, oldEmail } = document.getElementById('app').dataset;

const email = ref(oldEmail || '');
const showPassword = ref(false);
const submitting = ref(false);
const year = new Date().getFullYear();

// Drop your own photos in public/images/ with these names and they appear
// automatically. Until then, each frame shows the drawn artwork behind it.
const frames = ref([
    { id: 'a', src: '/images/login-1.jpg', failed: false },
    { id: 'b', src: '/images/login-2.jpg', failed: false },
    { id: 'c', src: '/images/login-3.jpg', failed: false },
]);

// Coming back with the browser's Back button should let the person submit again.
onMounted(() => {
    window.addEventListener('pageshow', () => {
        submitting.value = false;
    });
});
</script>

<template>
    <main class="lg">
        <section class="lg-side">
            <header class="lg-top">
                <p class="lg-brand">basiit<span>.</span></p>
                <ThemeToggle class="lg-theme" />
            </header>

            <div class="lg-body">
                <div class="lg-formwrap">
                    <h1>Welcome back</h1>
                    <p class="lg-sub">Sign in with your admin account.</p>

                    <form class="lg-form" method="POST" :action="loginUrl" @submit="submitting = true">
                        <input type="hidden" name="_token" :value="csrfToken">

                        <div class="lg-field">
                            <label for="admin-email">Email address</label>
                            <input id="admin-email" type="email" name="email" v-model="email"
                                   placeholder="you@example.com" autocomplete="username" required
                                   :aria-invalid="loginError ? 'true' : null"
                                   :aria-describedby="loginError ? 'login-error' : null">
                        </div>

                        <div class="lg-field">
                            <label for="admin-password">Password</label>
                            <div class="lg-password">
                                <input id="admin-password" :type="showPassword ? 'text' : 'password'"
                                       name="password" autocomplete="current-password"
                                       placeholder="Enter your password" required
                                       :aria-invalid="loginError ? 'true' : null"
                                       :aria-describedby="loginError ? 'login-error' : null">
                                <button class="lg-toggle" type="button"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                        :aria-pressed="showPassword"
                                        @click="showPassword = !showPassword">
                                    <AdminIcon :name="showPassword ? 'eyeOff' : 'eye'" :size="19" />
                                </button>
                            </div>
                        </div>

                        <label class="lg-remember">
                            <input type="checkbox" name="remember" value="1">
                            <span>Keep me signed in on this device</span>
                        </label>

                        <p v-if="loginError" id="login-error" class="lg-error" role="alert">{{ loginError }}</p>

                        <button class="lg-submit" type="submit" :disabled="submitting">
                            <span>{{ submitting ? 'Signing in…' : 'Sign in' }}</span>
                            <AdminIcon v-if="!submitting" class="lg-arrow" name="arrow" :size="19" />
                        </button>
                    </form>
                </div>
            </div>

            <footer class="lg-foot">© {{ year }} basiit</footer>
        </section>

        <aside class="lg-visual" aria-hidden="true">
            <div class="lg-grain"></div>

            <div class="lg-copy">
                <p class="lg-visual-brand">basiit<span>.</span></p>
                <p class="lg-visual-line">Simple tools for running your site.</p>
            </div>

            <div class="lg-stage">
                <div v-for="frame in frames" :key="frame.id" class="lg-frame" :class="`lg-frame--${frame.id}`">
                    <img v-show="!frame.failed" :src="frame.src" alt="" @error="frame.failed = true">
                </div>
            </div>
        </aside>
    </main>
</template>

<style scoped>
.lg {
    --bg: #f5f7fb;
    --ink: #0e1522;
    --muted: #5a6678;
    --line: #d9dfe8;
    --field: #ffffff;
    --accent: #0b6bcb;
    --btn: #0e1522;
    --btn-ink: #ffffff;
    --danger: #b42318;
    --danger-bg: #fdecea;

    min-height: 100vh;
    min-height: 100dvh;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
    background: var(--bg);
    color: var(--ink);
    font-family: "Segoe UI Variable Text", "Segoe UI", system-ui, -apple-system, Roboto, sans-serif;
}

:root[data-theme="dark"] .lg {
    --bg: #090c12;
    --ink: #eef2f8;
    --muted: #8b97a9;
    --line: #232d3d;
    --field: #0e131c;
    --accent: #5db2ff;
    --btn: #eef2f8;
    --btn-ink: #0a0d14;
    --danger: #ffb4ab;
    --danger-bg: #3a1a17;
}

/* Form side */
.lg-side {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    min-height: 100dvh;
    padding: 24px 32px;
    box-sizing: border-box;
}

.lg-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lg-brand {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.04em;
}

.lg-brand span {
    color: var(--accent);
}

.lg-theme {
    color: var(--ink);
}

.lg-body {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 0;
}

.lg-formwrap {
    width: 100%;
    max-width: 400px;
}

.lg-formwrap h1 {
    margin: 0 0 6px;
    font-size: 2.125rem;
    line-height: 1.12;
    font-weight: 650;
    letter-spacing: -0.025em;
}

.lg-sub {
    margin: 0 0 28px;
    color: var(--muted);
    font-size: 1.025rem;
}

.lg-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.lg-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.lg-field label {
    font-size: 0.9rem;
    font-weight: 600;
}

.lg-field input,
.lg-password input {
    width: 100%;
    box-sizing: border-box;
    height: 50px;
    padding: 0 15px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--field);
    color: var(--ink);
    font: inherit;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.lg-field input::placeholder {
    color: var(--muted);
    opacity: 0.75;
}

.lg-field input:focus-visible {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 30%, transparent);
}

.lg-field input[aria-invalid="true"] {
    border-color: var(--danger);
}

.lg-password {
    position: relative;
}

.lg-password input {
    padding-right: 50px;
}

.lg-toggle {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 9px;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
}

.lg-toggle:hover {
    color: var(--ink);
}

.lg-toggle:focus-visible,
.lg-submit:focus-visible,
.lg-remember input:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
}

.lg-remember {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--muted);
    font-size: 0.925rem;
    cursor: pointer;
}

.lg-remember input {
    width: 18px;
    height: 18px;
    margin: 0;
    accent-color: var(--accent);
}

.lg-error {
    margin: 0;
    padding: 11px 14px;
    border-radius: 12px;
    background: var(--danger-bg);
    color: var(--danger);
    font-size: 0.925rem;
    font-weight: 500;
}

.lg-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    height: 52px;
    margin-top: 4px;
    border: 0;
    border-radius: 12px;
    background: var(--btn);
    color: var(--btn-ink);
    font: inherit;
    font-weight: 650;
    cursor: pointer;
    transition: opacity 0.15s;
}

.lg-submit:hover:not(:disabled) {
    opacity: 0.88;
}

.lg-submit:disabled {
    opacity: 0.65;
    cursor: progress;
}

.lg-arrow {
    transition: transform 0.15s;
}

.lg-submit:hover .lg-arrow {
    transform: translateX(3px);
}

.lg-foot {
    color: var(--muted);
    font-size: 0.85rem;
}

/* Visual side: drawn gradient background with a collage of photo frames. */
.lg-visual {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 32px;
    padding: 48px;
    background:
        radial-gradient(60% 50% at 18% 12%, #2f6bff 0%, transparent 62%),
        radial-gradient(50% 45% at 90% 28%, #13b8a6 0%, transparent 60%),
        radial-gradient(60% 55% at 62% 98%, #7c4dff 0%, transparent 62%),
        #0a1124;
    color: #f2f6ff;
}

.lg-grain {
    position: absolute;
    inset: 0;
    opacity: 0.22;
    mix-blend-mode: overlay;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    pointer-events: none;
}

.lg-copy {
    position: relative;
}

.lg-visual-brand {
    margin: 0 0 10px;
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: -0.04em;
}

.lg-visual-brand span {
    color: #7fe3d6;
}

.lg-visual-line {
    margin: 0;
    max-width: 18ch;
    font-size: clamp(1.75rem, 2.6vw, 2.5rem);
    line-height: 1.15;
    font-weight: 650;
    letter-spacing: -0.025em;
}

.lg-stage {
    position: relative;
    align-self: center;
    width: min(100%, 540px);
    aspect-ratio: 1 / 1.02;
    margin-bottom: 8px;
}

.lg-frame {
    position: absolute;
    overflow: hidden;
    border: 1px solid rgb(255 255 255 / 0.28);
    box-shadow: 0 24px 48px -16px rgb(0 0 0 / 0.55);
}

.lg-frame img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Drawn artwork shown until a photo is added */
.lg-frame--a {
    top: 0;
    right: 0;
    width: 62%;
    height: 60%;
    border-radius: 30px;
    background:
        radial-gradient(circle at 70% 28%, #ffe29a 0 11%, transparent 12%),
        linear-gradient(165deg, #ff9a6b 0%, #d94f8b 48%, #5b3fd1 100%);
}

.lg-frame--b {
    top: 32%;
    left: 0;
    width: 46%;
    height: 42%;
    border-radius: 26px;
    background:
        radial-gradient(120% 70% at 20% 110%, #0b3d5c 0 45%, transparent 46%),
        radial-gradient(120% 70% at 90% 120%, #0f6a86 0 42%, transparent 43%),
        linear-gradient(180deg, #8fd8f2 0%, #3aa5c9 100%);
}

.lg-frame--c {
    right: 12%;
    bottom: 0;
    width: 54%;
    height: 34%;
    border-radius: 26px;
    background:
        repeating-radial-gradient(circle at 15% 120%, rgb(255 255 255 / 0.22) 0 2px, transparent 2px 22px),
        linear-gradient(135deg, #14b8a6 0%, #1b6fd1 100%);
}

@media (max-width: 960px) {
    .lg {
        grid-template-columns: minmax(0, 1fr);
    }

    .lg-visual {
        display: none;
    }

    .lg-side {
        padding: 20px 16px;
    }
}
</style>