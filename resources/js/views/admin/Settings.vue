<script setup>
import { nextTick, onMounted, reactive, ref } from 'vue';
import AdminIcon from '../../components/admin/AdminIcon.vue';
import { api } from '../../lib/api';

const users = ref([]);
const loading = ref(true);
const loadError = ref('');
const notice = ref('');
let noticeTimer = null;

// Add / edit dialog
const formDialog = ref(null);
const nameInput = ref(null);
const mode = ref('create');
const editing = ref(null);
const form = reactive({ name: '', email: '', password: '', is_admin: true });
const errors = ref({});
const formMessage = ref('');
const saving = ref(false);
const showPassword = ref(false);

// Delete dialog
const deleteDialog = ref(null);
const target = ref(null);
const deleting = ref(false);
const deleteError = ref('');

const dateFormat = new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

function formatDate(value) {
    return value ? dateFormat.format(new Date(value)) : '';
}

function flash(message) {
    notice.value = message;
    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(() => (notice.value = ''), 4000);
}

async function load(showSpinner = true) {
    if (showSpinner) loading.value = true;
    loadError.value = '';

    try {
        users.value = (await api.get('/users')).data;
    } catch (error) {
        loadError.value = error.message;
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    form.name = '';
    form.email = '';
    form.password = '';
    form.is_admin = true;
    errors.value = {};
    formMessage.value = '';
    showPassword.value = false;
}

async function openCreate() {
    resetForm();
    mode.value = 'create';
    editing.value = null;
    formDialog.value.showModal();
    await nextTick();
    nameInput.value?.focus();
}

async function openEdit(user) {
    resetForm();
    mode.value = 'edit';
    editing.value = user;
    form.name = user.name;
    form.email = user.email;
    form.is_admin = user.is_admin;
    formDialog.value.showModal();
    await nextTick();
    nameInput.value?.focus();
}

function closeForm() {
    formDialog.value?.close();
}

function onFormClosed() {
    form.password = '';
    showPassword.value = false;
}

function generatePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    const bytes = crypto.getRandomValues(new Uint32Array(16));
    form.password = Array.from(bytes, (value) => chars[value % chars.length]).join('');
    showPassword.value = true;
}

async function save() {
    saving.value = true;
    errors.value = {};
    formMessage.value = '';

    const payload = { name: form.name, email: form.email, is_admin: form.is_admin };

    if (mode.value === 'create' || form.password) {
        payload.password = form.password;
    }

    try {
        if (mode.value === 'create') {
            await api.post('/users', payload);
            flash('User added.');
        } else {
            await api.put(`/users/${editing.value.id}`, payload);
            flash('Changes saved.');
        }

        closeForm();
        await load(false);
    } catch (error) {
        if (error.status === 422 && Object.keys(error.errors).length) {
            errors.value = error.errors;
        } else {
            formMessage.value = error.message;
        }
    } finally {
        saving.value = false;
    }
}

function askDelete(user) {
    target.value = user;
    deleteError.value = '';
    deleteDialog.value.showModal();
}

function closeDelete() {
    deleteDialog.value?.close();
}

async function confirmDelete() {
    deleting.value = true;
    deleteError.value = '';

    try {
        await api.delete(`/users/${target.value.id}`);
        flash('User deleted.');
        closeDelete();
        await load(false);
    } catch (error) {
        deleteError.value = error.message;
    } finally {
        deleting.value = false;
    }
}

onMounted(() => load());
</script>

<template>
    <section class="st" aria-labelledby="settings-title">
        <header class="ma-page-heading">
            <h1 id="settings-title">Settings</h1>
        </header>

        <div class="st-bar">
            <div>
                <h2>Users</h2>
                <p>People who can sign in to this admin panel.</p>
            </div>
            <button class="ma-primary-button" type="button" @click="openCreate">Add user</button>
        </div>

        <p v-if="notice" class="st-notice" role="status">{{ notice }}</p>

        <p v-if="loading" class="st-muted">Loading users…</p>

        <div v-else-if="loadError" class="st-failed" role="alert">
            <p>{{ loadError }}</p>
            <button class="st-ghost" type="button" @click="load()">Try again</button>
        </div>

        <table v-else class="st-table">
            <thead>
                <tr>
                    <th scope="col">User</th>
                    <th scope="col">Access</th>
                    <th scope="col">Added</th>
                    <th scope="col"><span class="st-sr">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id">
                    <td data-label="User">
                        <span class="st-name">
                            {{ user.name }}
                            <span v-if="user.is_you" class="st-you">You</span>
                        </span>
                        <span class="st-email">{{ user.email }}</span>
                    </td>
                    <td data-label="Access">
                        <span class="st-badge" :class="{ 'st-badge--off': !user.is_admin }">
                            {{ user.is_admin ? 'Administrator' : 'Sign-in blocked' }}
                        </span>
                    </td>
                    <td data-label="Added">{{ formatDate(user.created_at) }}</td>
                    <td class="st-actions">
                        <button class="st-link" type="button" @click="openEdit(user)">Edit</button>
                        <button class="st-link st-link--danger" type="button" :disabled="user.is_you"
                                :title="user.is_you ? 'You can’t delete your own account' : null"
                                @click="askDelete(user)">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Add / edit user -->
        <dialog ref="formDialog" class="st-dialog" aria-labelledby="user-form-title"
                @click.self="closeForm" @close="onFormClosed">
            <form class="st-dialog-body" novalidate @submit.prevent="save">
                <h2 id="user-form-title">{{ mode === 'create' ? 'Add user' : 'Edit user' }}</h2>

                <div class="st-field">
                    <label for="user-name">Name</label>
                    <input id="user-name" ref="nameInput" v-model="form.name" type="text" autocomplete="off"
                           :aria-invalid="errors.name ? 'true' : null">
                    <small v-if="errors.name" class="st-error">{{ errors.name[0] }}</small>
                </div>

                <div class="st-field">
                    <label for="user-email">Email address</label>
                    <input id="user-email" v-model="form.email" type="email" autocomplete="off"
                           :aria-invalid="errors.email ? 'true' : null">
                    <small v-if="errors.email" class="st-error">{{ errors.email[0] }}</small>
                </div>

                <div class="st-field">
                    <label for="user-password">Password</label>
                    <div class="st-pw">
                        <input id="user-password" v-model="form.password"
                               :type="showPassword ? 'text' : 'password'" autocomplete="new-password"
                               :placeholder="mode === 'edit' ? 'Leave blank to keep the current password' : 'At least 8 characters'"
                               :aria-invalid="errors.password ? 'true' : null">
                        <button class="st-icon" type="button"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                @click="showPassword = !showPassword">
                            <AdminIcon :name="showPassword ? 'eyeOff' : 'eye'" :size="18" />
                        </button>
                    </div>
                    <small v-if="errors.password" class="st-error">{{ errors.password[0] }}</small>
                    <button class="st-link st-gen" type="button" @click="generatePassword">Generate a password</button>
                </div>

                <div class="st-field">
                    <label class="st-check">
                        <input v-model="form.is_admin" type="checkbox" :disabled="editing?.is_you">
                        <span>
                            <strong>Administrator</strong>
                            <small>Can sign in to this panel. Turn off to block sign-in without deleting the account.</small>
                        </span>
                    </label>
                    <small v-if="errors.is_admin" class="st-error">{{ errors.is_admin[0] }}</small>
                </div>

                <p v-if="formMessage" class="st-error st-error--box" role="alert">{{ formMessage }}</p>

                <div class="st-buttons">
                    <button class="st-ghost" type="button" @click="closeForm">Cancel</button>
                    <button class="ma-primary-button" type="submit" :disabled="saving">
                        {{ saving ? 'Saving…' : mode === 'create' ? 'Add user' : 'Save changes' }}
                    </button>
                </div>
            </form>
        </dialog>

        <!-- Delete user -->
        <dialog ref="deleteDialog" class="st-dialog" aria-labelledby="user-delete-title" @click.self="closeDelete">
            <div class="st-dialog-body">
                <h2 id="user-delete-title">Delete {{ target?.name }}?</h2>
                <p class="st-muted">They’ll be signed out and lose access right away. This can’t be undone.</p>
                <p v-if="deleteError" class="st-error st-error--box" role="alert">{{ deleteError }}</p>
                <div class="st-buttons">
                    <button class="st-ghost" type="button" @click="closeDelete">Cancel</button>
                    <button class="st-danger" type="button" :disabled="deleting" @click="confirmDelete">
                        {{ deleting ? 'Deleting…' : 'Delete user' }}
                    </button>
                </div>
            </div>
        </dialog>
    </section>
</template>

<style scoped>
.st {
    --line: color-mix(in srgb, currentColor 16%, transparent);
    --soft: color-mix(in srgb, currentColor 6%, transparent);
    --muted: color-mix(in srgb, currentColor 62%, transparent);
    --danger: #b42318;
}

.st-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 16px;
    margin: 8px 0 18px;
}

.st-bar h2 {
    margin: 0 0 2px;
    font-size: 1.125rem;
}

.st-bar p,
.st-muted {
    margin: 0;
    color: var(--muted);
}

.st-notice {
    margin: 0 0 16px;
    padding: 10px 14px;
    border-radius: 10px;
    background: color-mix(in srgb, #1a7f4b 16%, transparent);
    font-weight: 500;
}

.st-failed {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid var(--danger);
    border-radius: 12px;
}

.st-failed p {
    margin: 0;
}

/* Table */
.st-table {
    width: 100%;
    border-collapse: collapse;
}

.st-table th {
    padding: 10px 14px;
    border-bottom: 1px solid var(--line);
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--muted);
}

.st-table td {
    padding: 14px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
}

.st-name {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.st-email {
    display: block;
    color: var(--muted);
    font-size: 0.9rem;
    overflow-wrap: anywhere;
}

.st-you {
    padding: 1px 8px;
    border-radius: 999px;
    background: var(--soft);
    font-size: 0.75rem;
    font-weight: 600;
}

.st-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    background: color-mix(in srgb, #1a7f4b 16%, transparent);
    font-size: 0.85rem;
    font-weight: 600;
}

.st-badge--off {
    background: var(--soft);
    color: var(--muted);
}

.st-actions {
    text-align: right;
    white-space: nowrap;
}

.st-sr {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}

/* Buttons */
.st-link {
    padding: 6px 8px;
    border: 0;
    border-radius: 8px;
    background: none;
    color: inherit;
    font: inherit;
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 3px;
    cursor: pointer;
}

.st-link--danger {
    color: var(--danger);
}

.st-link:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.st-ghost,
.st-danger {
    min-height: 42px;
    padding: 0 18px;
    border-radius: 10px;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

.st-ghost {
    border: 1px solid var(--line);
    background: none;
    color: inherit;
}

.st-danger {
    border: 0;
    background: var(--danger);
    color: #fff;
}

.st-danger:disabled {
    opacity: 0.7;
    cursor: progress;
}

.st-link:focus-visible,
.st-ghost:focus-visible,
.st-danger:focus-visible,
.st-icon:focus-visible,
.st-check input:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

/* Dialog */
.st-dialog {
    width: min(480px, calc(100vw - 32px));
    padding: 0;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: Canvas;
    color: CanvasText;
    color-scheme: light dark;
}

.st-dialog::backdrop {
    background: rgb(8 16 22 / 0.55);
}

.st-dialog-body {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 24px;
}

.st-dialog-body h2 {
    margin: 0;
    font-size: 1.25rem;
}

.st-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.st-field > label,
.st-field > .st-link {
    font-size: 0.9rem;
    font-weight: 600;
}

.st-field input[type="text"],
.st-field input[type="email"],
.st-field input[type="password"] {
    width: 100%;
    box-sizing: border-box;
    height: 44px;
    padding: 0 12px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: transparent;
    color: inherit;
    font: inherit;
}

.st-field input:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 1px;
}

.st-field input[aria-invalid="true"] {
    border-color: var(--danger);
}

.st-pw {
    position: relative;
}

.st-pw input {
    padding-right: 44px !important;
}

.st-icon {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 8px;
    background: none;
    color: var(--muted);
    cursor: pointer;
}

.st-gen {
    align-self: flex-start;
    padding-left: 0;
}

.st-check {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
}

.st-check input {
    width: 18px;
    height: 18px;
    margin: 2px 0 0;
}

.st-check span {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.st-check small {
    color: var(--muted);
    font-size: 0.85rem;
    line-height: 1.4;
}

.st-error {
    color: var(--danger);
    font-size: 0.875rem;
    font-weight: 500;
}

.st-error--box {
    margin: 0;
    padding: 10px 12px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--danger) 14%, transparent);
}

.st-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 4px;
}

/* Phones: each user becomes a stacked card */
@media (max-width: 720px) {
    .st-table thead {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0 0 0 0);
    }

    .st-table,
    .st-table tbody,
    .st-table tr,
    .st-table td {
        display: block;
    }

    .st-table tr {
        padding: 8px 0;
        border-bottom: 1px solid var(--line);
    }

    .st-table td {
        padding: 6px 0;
        border: 0;
    }

    .st-table td[data-label]::before {
        content: attr(data-label);
        display: block;
        font-size: 0.8rem;
        color: var(--muted);
    }

    .st-actions {
        text-align: left;
    }

    .st-actions .st-link:first-child {
        padding-left: 0;
    }
}
</style>
