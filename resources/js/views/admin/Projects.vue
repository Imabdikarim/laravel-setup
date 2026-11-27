<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import { api } from '../../lib/api';

const PER_PAGE = 8;

// Must match the icons the website can draw (src/data/systemIcons.js) and Project::ICONS in Laravel.
const ICON_OPTIONS = [
    { value: 'truck', label: 'Truck (cargo)' },
    { value: 'bag', label: 'Shopping bag (business)' },
    { value: 'plane', label: 'Plane (airline)' },
    { value: 'medical', label: 'Medical cross (hospital)' },
    { value: 'money', label: 'Money (finance)' },
    { value: 'heartbeat', label: 'Heartbeat (clinic)' },
    { value: 'globe', label: 'Globe (travel)' },
    { value: 'register', label: 'Cash register (POS)' },
    { value: 'briefcase', label: 'Briefcase (corporate)' },
    { value: 'chart', label: 'Chart (reports)' },
];
const DEFAULT_COLOR = '#2f8be6';

const projects = ref([]);
const loading = ref(true);
const loadError = ref('');
const notice = ref('');
let noticeTimer = null;

/* ---------- Filters ---------- */
const search = ref('');
const statusFilter = ref('all'); // all | published | draft
const tagFilter = ref('all');
const featuredOnly = ref(false);
const sortBy = ref('order'); // order | newest | updated | title
const page = ref(1);

const counts = computed(() => ({
    all: projects.value.length,
    published: projects.value.filter((p) => p.status === 'published').length,
    draft: projects.value.filter((p) => p.status !== 'published').length,
    featured: projects.value.filter((p) => p.featured).length,
}));

const allTags = computed(() => {
    const set = new Set();
    projects.value.forEach((p) => (p.tags || []).forEach((t) => set.add(t)));
    return [...set].sort((a, b) => a.localeCompare(b));
});

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    const list = projects.value.filter((p) => {
        if (statusFilter.value === 'published' && p.status !== 'published') return false;
        if (statusFilter.value === 'draft' && p.status === 'published') return false;
        if (tagFilter.value !== 'all' && !(p.tags || []).includes(tagFilter.value)) return false;
        if (featuredOnly.value && !p.featured) return false;
        if (!q) return true;

        return [p.title, p.summary, p.slug, ...(p.tags || [])].some((v) => (v || '').toLowerCase().includes(q));
    });

    const sorters = {
        order: (a, b) => a.sort_order - b.sort_order || b.id - a.id,
        newest: (a, b) => b.id - a.id,
        updated: (a, b) => new Date(b.updated_at) - new Date(a.updated_at),
        title: (a, b) => a.title.localeCompare(b.title),
    };

    return [...list].sort(sorters[sortBy.value]);
});

const pageCount = computed(() => Math.max(1, Math.ceil(filtered.value.length / PER_PAGE)));
const paged = computed(() => filtered.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE));
const rangeText = computed(() => {
    if (!filtered.value.length) return '';
    const from = (page.value - 1) * PER_PAGE + 1;
    const to = Math.min(page.value * PER_PAGE, filtered.value.length);
    return `Showing ${from}–${to} of ${filtered.value.length}`;
});
const hasFilters = computed(
    () => search.value.trim() !== '' || statusFilter.value !== 'all' || tagFilter.value !== 'all' || featuredOnly.value,
);

watch([search, statusFilter, tagFilter, featuredOnly, sortBy], () => (page.value = 1));
watch(pageCount, (count) => {
    if (page.value > count) page.value = count;
});

function clearFilters() {
    search.value = '';
    statusFilter.value = 'all';
    tagFilter.value = 'all';
    featuredOnly.value = false;
}

/* ---------- Helpers ---------- */
const dateFormat = new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
const formatDate = (value) => (value ? dateFormat.format(new Date(value)) : '');

function flash(message) {
    notice.value = message;
    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(() => (notice.value = ''), 4000);
}

// Only normal web links are shown or opened.
function safeUrl(value) {
    if (!value) return null;
    try {
        const url = new URL(value, window.location.href);
        return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : null;
    } catch {
        return null;
    }
}

// The project's own colour when it has one, otherwise a steady colour made from the title.
function artStyle(project) {
    if (/^#[0-9a-f]{6}$/i.test(project.color || '')) {
        return `linear-gradient(140deg, ${project.color}, color-mix(in srgb, ${project.color} 55%, #000))`;
    }

    let hash = 0;
    for (const ch of project.title) hash = (hash * 31 + ch.charCodeAt(0)) >>> 0;
    const hue = hash % 360;
    return `linear-gradient(140deg, hsl(${hue} 75% 56%), hsl(${(hue + 48) % 360} 68% 38%))`;
}

// First error message for a field, including list items like "tags.0".
const errors = ref({});
function err(field) {
    const key = Object.keys(errors.value).find((k) => k === field || k.startsWith(`${field}.`));
    return key ? errors.value[key][0] : '';
}

function payloadFrom(project, overrides = {}) {
    return {
        title: project.title,
        summary: project.summary,
        description: project.description || null,
        tags: project.tags || [],
        live_url: project.live_url || null,
        repo_url: project.repo_url || null,
        image_url: project.image_url || null,
        color: project.color || null,
        icon: project.icon || null,
        status: project.status,
        featured: project.featured,
        sort_order: project.sort_order,
        ...overrides,
    };
}

async function load(showSpinner = true) {
    if (showSpinner) loading.value = true;
    loadError.value = '';

    try {
        projects.value = (await api.get('/projects')).data;
    } catch (error) {
        loadError.value = error.message;
    } finally {
        loading.value = false;
    }
}

/* ---------- Add / edit ---------- */
const formDialog = ref(null);
const titleInput = ref(null);
const mode = ref('create');
const editing = ref(null);
const blank = () => ({
    title: '',
    summary: '',
    description: '',
    tags: '',
    live_url: '',
    repo_url: '',
    image_url: '',
    color: '',
    icon: '',
    status: 'draft',
    featured: false,
    sort_order: 0,
});
const form = reactive(blank());
const formMessage = ref('');
const saving = ref(false);

function resetForm() {
    Object.assign(form, blank());
    errors.value = {};
    formMessage.value = '';
}

async function openCreate() {
    resetForm();
    mode.value = 'create';
    editing.value = null;
    formDialog.value.showModal();
    await nextTick();
    titleInput.value?.focus();
}

async function openEdit(project) {
    resetForm();
    mode.value = 'edit';
    editing.value = project;
    Object.assign(form, {
        title: project.title,
        summary: project.summary,
        description: project.description || '',
        tags: (project.tags || []).join(', '),
        live_url: project.live_url || '',
        repo_url: project.repo_url || '',
        image_url: project.image_url || '',
        color: project.color || '',
        icon: project.icon || '',
        status: project.status,
        featured: project.featured,
        sort_order: project.sort_order,
    });
    formDialog.value.showModal();
    await nextTick();
    titleInput.value?.focus();
}

function closeForm() {
    formDialog.value?.close();
}

async function save() {
    saving.value = true;
    errors.value = {};
    formMessage.value = '';

    const payload = {
        title: form.title,
        summary: form.summary,
        description: form.description || null,
        tags: form.tags.split(',').map((tag) => tag.trim()).filter(Boolean),
        live_url: form.live_url || null,
        repo_url: form.repo_url || null,
        image_url: form.image_url || null,
        color: form.color.trim() || null,
        icon: form.icon || null,
        status: form.status,
        featured: form.featured,
        sort_order: Number(form.sort_order) || 0,
    };

    try {
        if (mode.value === 'create') {
            await api.post('/projects', payload);
            flash('Project added.');
        } else {
            await api.put(`/projects/${editing.value.id}`, payload);
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

/* ---------- Publish / unpublish ---------- */
const busyId = ref(null);

async function togglePublish(project) {
    const publishing = project.status !== 'published';
    busyId.value = project.id;

    try {
        await api.put(`/projects/${project.id}`, payloadFrom(project, { status: publishing ? 'published' : 'draft' }));
        flash(publishing ? 'Published. It’s now on the website.' : 'Unpublished. It’s now a draft.');
        await load(false);

        if (viewing.value?.id === project.id) {
            viewing.value = projects.value.find((p) => p.id === project.id) || null;
        }
    } catch (error) {
        flash(error.message);
    } finally {
        busyId.value = null;
    }
}

/* ---------- View ---------- */
const viewDialog = ref(null);
const viewing = ref(null);

function openView(project) {
    viewing.value = project;
    viewDialog.value.showModal();
}

function closeView() {
    viewDialog.value?.close();
}

function editFromView() {
    const project = viewing.value;
    closeView();
    if (project) openEdit(project);
}

const viewParagraphs = computed(() =>
    (viewing.value?.description || '').split(/\n{2,}/).map((s) => s.trim()).filter(Boolean),
);

/* ---------- Delete ---------- */
const deleteDialog = ref(null);
const target = ref(null);
const deleting = ref(false);
const deleteError = ref('');

function askDelete(project) {
    target.value = project;
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
        await api.delete(`/projects/${target.value.id}`);
        flash('Project deleted.');
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
    <section class="pj" aria-labelledby="projects-title">
        <header class="pj-head">
            <div>
                <h1 id="projects-title">Projects</h1>
                <p class="pj-sub">Published projects appear on your website. Drafts stay hidden.</p>
            </div>
            <button class="ma-primary-button" type="button" @click="openCreate">Add project</button>
        </header>

        <p v-if="notice" class="pj-notice" role="status">{{ notice }}</p>

        <p v-if="loading" class="pj-muted">Loading projects…</p>

        <div v-else-if="loadError" class="pj-failed" role="alert">
            <p>{{ loadError }}</p>
            <button class="pj-ghost" type="button" @click="load()">Try again</button>
        </div>

        <div v-else-if="!projects.length" class="pj-empty">
            <h2>No projects yet</h2>
            <p>Add your first project, then publish it to show it on the website.</p>
            <button class="ma-primary-button" type="button" @click="openCreate">Add project</button>
        </div>

        <template v-else>
            <!-- Status tabs with counts -->
            <div class="pj-tabs" role="group" aria-label="Filter by status">
                <button type="button" :aria-pressed="statusFilter === 'all'" @click="statusFilter = 'all'">
                    All <span>{{ counts.all }}</span>
                </button>
                <button type="button" :aria-pressed="statusFilter === 'published'" @click="statusFilter = 'published'">
                    Published <span>{{ counts.published }}</span>
                </button>
                <button type="button" :aria-pressed="statusFilter === 'draft'" @click="statusFilter = 'draft'">
                    Drafts <span>{{ counts.draft }}</span>
                </button>
            </div>

            <!-- Search + filters -->
            <div class="pj-tools">
                <div class="pj-search">
                    <label class="pj-sr" for="pj-search">Search projects</label>
                    <input id="pj-search" v-model="search" type="search" placeholder="Search by title, summary or tag"
                           autocomplete="off">
                </div>

                <div class="pj-select">
                    <label for="pj-tag">Tag</label>
                    <select id="pj-tag" v-model="tagFilter">
                        <option value="all">All tags</option>
                        <option v-for="tag in allTags" :key="tag" :value="tag">{{ tag }}</option>
                    </select>
                </div>

                <div class="pj-select">
                    <label for="pj-sort">Sort by</label>
                    <select id="pj-sort" v-model="sortBy">
                        <option value="order">Website order</option>
                        <option value="newest">Newest first</option>
                        <option value="updated">Recently updated</option>
                        <option value="title">Title A to Z</option>
                    </select>
                </div>

                <label class="pj-toggle">
                    <input v-model="featuredOnly" type="checkbox">
                    <span>Featured only ({{ counts.featured }})</span>
                </label>
            </div>

            <!-- No matches -->
            <div v-if="!filtered.length" class="pj-empty">
                <h2>No projects match</h2>
                <p>Try a different search or clear the filters.</p>
                <button class="pj-ghost" type="button" @click="clearFilters">Clear filters</button>
            </div>

            <template v-else>
                <table class="pj-table">
                    <thead>
                        <tr>
                            <th scope="col">Project</th>
                            <th scope="col">Tags</th>
                            <th scope="col">Status</th>
                            <th scope="col">Updated</th>
                            <th scope="col"><span class="pj-sr">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="project in paged" :key="project.id">
                            <td data-label="Project">
                                <div class="pj-cell">
                                    <span class="pj-thumb" :style="{ backgroundImage: artStyle(project) }" aria-hidden="true">
                                        <img v-if="safeUrl(project.image_url)" :src="safeUrl(project.image_url)" alt=""
                                             loading="lazy" @error="$event.target.remove()">
                                        <b v-else>{{ project.title.charAt(0).toUpperCase() }}</b>
                                    </span>
                                    <span class="pj-main">
                                        <button class="pj-title" type="button" @click="openView(project)">
                                            {{ project.title }}
                                        </button>
                                        <span v-if="project.featured" class="pj-pill">Featured</span>
                                        <span class="pj-summary">{{ project.summary }}</span>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Tags">
                                <span v-if="project.tags.length" class="pj-tags">
                                    <span v-for="tag in project.tags.slice(0, 3)" :key="tag">{{ tag }}</span>
                                    <span v-if="project.tags.length > 3">+{{ project.tags.length - 3 }}</span>
                                </span>
                                <span v-else class="pj-muted">None</span>
                            </td>
                            <td data-label="Status">
                                <span class="pj-badge" :class="{ 'pj-badge--draft': project.status !== 'published' }">
                                    {{ project.status === 'published' ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td data-label="Updated">{{ formatDate(project.updated_at) }}</td>
                            <td class="pj-actions">
                                <button class="pj-link" type="button" @click="openView(project)">View</button>
                                <button class="pj-link" type="button" @click="openEdit(project)">Edit</button>
                                <button class="pj-link" type="button" :disabled="busyId === project.id"
                                        @click="togglePublish(project)">
                                    {{ project.status === 'published' ? 'Unpublish' : 'Publish' }}
                                </button>
                                <button class="pj-link pj-link--danger" type="button" @click="askDelete(project)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="pj-foot">
                    <p class="pj-muted">{{ rangeText }}</p>
                    <div v-if="pageCount > 1" class="pj-pager">
                        <button class="pj-ghost" type="button" :disabled="page === 1" @click="page--">Previous</button>
                        <span>Page {{ page }} of {{ pageCount }}</span>
                        <button class="pj-ghost" type="button" :disabled="page === pageCount" @click="page++">Next</button>
                    </div>
                </div>
            </template>
        </template>

        <!-- View project -->
        <dialog ref="viewDialog" class="pj-dialog" aria-labelledby="view-title" @click.self="closeView">
            <div v-if="viewing" class="pj-view">
                <div class="pj-view-art" :style="{ backgroundImage: artStyle(viewing) }" aria-hidden="true">
                    <img v-if="safeUrl(viewing.image_url)" :src="safeUrl(viewing.image_url)" alt="" @error="$event.target.remove()">
                    <b v-else>{{ viewing.title.charAt(0).toUpperCase() }}</b>
                </div>

                <div class="pj-dialog-body">
                    <div class="pj-view-top">
                        <h2 id="view-title">{{ viewing.title }}</h2>
                        <span class="pj-badge" :class="{ 'pj-badge--draft': viewing.status !== 'published' }">
                            {{ viewing.status === 'published' ? 'Published' : 'Draft' }}
                        </span>
                    </div>

                    <p class="pj-view-summary">{{ viewing.summary }}</p>

                    <div v-if="viewParagraphs.length" class="pj-view-text">
                        <p v-for="(paragraph, i) in viewParagraphs" :key="i">{{ paragraph }}</p>
                    </div>
                    <p v-else class="pj-muted">No description yet.</p>

                    <span v-if="viewing.tags.length" class="pj-tags">
                        <span v-for="tag in viewing.tags" :key="tag">{{ tag }}</span>
                    </span>

                    <dl class="pj-facts">
                        <div><dt>Slug</dt><dd>{{ viewing.slug }}</dd></div>
                        <div><dt>Website order</dt><dd>{{ viewing.sort_order }}</dd></div>
                        <div><dt>Featured</dt><dd>{{ viewing.featured ? 'Yes' : 'No' }}</dd></div>
                        <div v-if="viewing.color"><dt>Colour</dt><dd>{{ viewing.color }}</dd></div>
                        <div v-if="viewing.icon"><dt>Icon</dt><dd>{{ viewing.icon }}</dd></div>
                        <div><dt>Added</dt><dd>{{ formatDate(viewing.created_at) }}</dd></div>
                        <div><dt>Last updated</dt><dd>{{ formatDate(viewing.updated_at) }}</dd></div>
                        <div v-if="safeUrl(viewing.live_url)"><dt>Live link</dt><dd><a :href="safeUrl(viewing.live_url)" target="_blank" rel="noopener noreferrer">{{ viewing.live_url }}</a></dd></div>
                        <div v-if="safeUrl(viewing.repo_url)"><dt>Code link</dt><dd><a :href="safeUrl(viewing.repo_url)" target="_blank" rel="noopener noreferrer">{{ viewing.repo_url }}</a></dd></div>
                    </dl>

                    <div class="pj-buttons">
                        <button class="pj-ghost" type="button" @click="closeView">Close</button>
                        <button class="pj-ghost" type="button" :disabled="busyId === viewing.id" @click="togglePublish(viewing)">
                            {{ viewing.status === 'published' ? 'Unpublish' : 'Publish' }}
                        </button>
                        <button class="ma-primary-button" type="button" @click="editFromView">Edit</button>
                    </div>
                </div>
            </div>
        </dialog>

        <!-- Add / edit project -->
        <dialog ref="formDialog" class="pj-dialog" aria-labelledby="project-form-title" @click.self="closeForm">
            <form class="pj-dialog-body" novalidate @submit.prevent="save">
                <h2 id="project-form-title">{{ mode === 'create' ? 'Add project' : 'Edit project' }}</h2>

                <div class="pj-field">
                    <label for="p-title">Title</label>
                    <input id="p-title" ref="titleInput" v-model="form.title" type="text" autocomplete="off"
                           :aria-invalid="err('title') ? 'true' : null">
                    <small v-if="err('title')" class="pj-error">{{ err('title') }}</small>
                </div>

                <div class="pj-field">
                    <label for="p-summary">Short summary</label>
                    <input id="p-summary" v-model="form.summary" type="text" autocomplete="off"
                           placeholder="One sentence shown on the project card"
                           :aria-invalid="err('summary') ? 'true' : null">
                    <small v-if="err('summary')" class="pj-error">{{ err('summary') }}</small>
                </div>

                <div class="pj-field">
                    <label for="p-description">Description</label>
                    <textarea id="p-description" v-model="form.description" rows="5"
                              placeholder="Shown when someone opens the project. Leave a blank line between paragraphs."
                              :aria-invalid="err('description') ? 'true' : null"></textarea>
                    <small v-if="err('description')" class="pj-error">{{ err('description') }}</small>
                </div>

                <div class="pj-field">
                    <label for="p-tags">Tags</label>
                    <input id="p-tags" v-model="form.tags" type="text" autocomplete="off"
                           placeholder="Website, Laravel, Vue"
                           :aria-invalid="err('tags') ? 'true' : null">
                    <small class="pj-hint">Separate tags with commas. Up to 8.</small>
                    <small v-if="err('tags')" class="pj-error">{{ err('tags') }}</small>
                </div>

                <div class="pj-two">
                    <div class="pj-field">
                        <label for="p-live">Live link</label>
                        <input id="p-live" v-model="form.live_url" type="url" autocomplete="off"
                               placeholder="https://…" :aria-invalid="err('live_url') ? 'true' : null">
                        <small v-if="err('live_url')" class="pj-error">{{ err('live_url') }}</small>
                    </div>
                    <div class="pj-field">
                        <label for="p-repo">Code link</label>
                        <input id="p-repo" v-model="form.repo_url" type="url" autocomplete="off"
                               placeholder="https://github.com/…" :aria-invalid="err('repo_url') ? 'true' : null">
                        <small v-if="err('repo_url')" class="pj-error">{{ err('repo_url') }}</small>
                    </div>
                </div>

                <div class="pj-field">
                    <label for="p-image">Image link</label>
                    <input id="p-image" v-model="form.image_url" type="text" autocomplete="off"
                           placeholder="https://… (optional, a colour tile is used if empty)"
                           :aria-invalid="err('image_url') ? 'true' : null">
                    <small v-if="err('image_url')" class="pj-error">{{ err('image_url') }}</small>
                </div>

                <div class="pj-two">
                    <div class="pj-field">
                        <label for="p-color-text">Colour</label>
                        <div class="pj-colour">
                            <input type="color" aria-label="Pick a colour" :value="form.color || DEFAULT_COLOR"
                                   @input="form.color = $event.target.value">
                            <input id="p-color-text" v-model="form.color" type="text" autocomplete="off"
                                   :placeholder="DEFAULT_COLOR" maxlength="7"
                                   :aria-invalid="err('color') ? 'true' : null">
                        </div>
                        <small class="pj-hint">Used for the card accent on the website.</small>
                        <small v-if="err('color')" class="pj-error">{{ err('color') }}</small>
                    </div>
                    <div class="pj-field">
                        <label for="p-icon">Icon</label>
                        <select id="p-icon" v-model="form.icon" :aria-invalid="err('icon') ? 'true' : null">
                            <option value="">Default</option>
                            <option v-for="icon in ICON_OPTIONS" :key="icon.value" :value="icon.value">{{ icon.label }}</option>
                        </select>
                        <small v-if="err('icon')" class="pj-error">{{ err('icon') }}</small>
                    </div>
                </div>

                <div class="pj-two">
                    <div class="pj-field">
                        <label for="p-status">Status</label>
                        <select id="p-status" v-model="form.status">
                            <option value="draft">Draft (hidden)</option>
                            <option value="published">Published (on the website)</option>
                        </select>
                        <small v-if="err('status')" class="pj-error">{{ err('status') }}</small>
                    </div>
                    <div class="pj-field">
                        <label for="p-order">Order</label>
                        <input id="p-order" v-model="form.sort_order" type="number" min="0" max="9999"
                               :aria-invalid="err('sort_order') ? 'true' : null">
                        <small class="pj-hint">Lower numbers come first.</small>
                        <small v-if="err('sort_order')" class="pj-error">{{ err('sort_order') }}</small>
                    </div>
                </div>

                <label class="pj-check">
                    <input v-model="form.featured" type="checkbox">
                    <span>
                        <strong>Featured</strong>
                        <small>Shown first and larger on the website.</small>
                    </span>
                </label>

                <p v-if="formMessage" class="pj-error pj-error--box" role="alert">{{ formMessage }}</p>

                <div class="pj-buttons">
                    <button class="pj-ghost" type="button" @click="closeForm">Cancel</button>
                    <button class="ma-primary-button" type="submit" :disabled="saving">
                        {{ saving ? 'Saving…' : mode === 'create' ? 'Add project' : 'Save changes' }}
                    </button>
                </div>
            </form>
        </dialog>

        <!-- Delete project -->
        <dialog ref="deleteDialog" class="pj-dialog pj-dialog--small" aria-labelledby="project-delete-title"
                @click.self="closeDelete">
            <div class="pj-dialog-body">
                <h2 id="project-delete-title">Delete {{ target?.title }}?</h2>
                <p class="pj-muted">It will disappear from the website right away. This can’t be undone.</p>
                <p v-if="deleteError" class="pj-error pj-error--box" role="alert">{{ deleteError }}</p>
                <div class="pj-buttons">
                    <button class="pj-ghost" type="button" @click="closeDelete">Cancel</button>
                    <button class="pj-danger" type="button" :disabled="deleting" @click="confirmDelete">
                        {{ deleting ? 'Deleting…' : 'Delete project' }}
                    </button>
                </div>
            </div>
        </dialog>
    </section>
</template>

<style scoped>
.pj {
    --line: color-mix(in srgb, currentColor 16%, transparent);
    --soft: color-mix(in srgb, currentColor 7%, transparent);
    --muted: color-mix(in srgb, currentColor 62%, transparent);
    --danger: #b42318;
}

.pj-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px 16px;
    margin-bottom: 20px;
}

.pj-head h1 {
    margin: 0;
}

.pj-sub,
.pj-muted {
    margin: 4px 0 0;
    color: var(--muted);
}

.pj-notice {
    margin: 0 0 16px;
    padding: 10px 14px;
    border-radius: 10px;
    background: color-mix(in srgb, #1a7f4b 16%, transparent);
    font-weight: 500;
}

.pj-failed {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid var(--danger);
    border-radius: 12px;
}

.pj-failed p {
    margin: 0;
}

.pj-empty {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding: 28px;
    border: 1px dashed var(--line);
    border-radius: 14px;
}

.pj-empty h2,
.pj-empty p {
    margin: 0;
}

.pj-empty p {
    margin-bottom: 8px;
    color: var(--muted);
}

/* Status tabs */
.pj-tabs {
    display: inline-flex;
    gap: 4px;
    margin-bottom: 14px;
    padding: 4px;
    border: 1px solid var(--line);
    border-radius: 12px;
}

.pj-tabs button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 0;
    border-radius: 9px;
    background: none;
    color: inherit;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

.pj-tabs button span {
    padding: 0 8px;
    border-radius: 999px;
    background: var(--soft);
    font-size: 0.8rem;
}

.pj-tabs button[aria-pressed="true"] {
    background: color-mix(in srgb, currentColor 12%, transparent);
}

/* Search and filters */
.pj-tools {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 12px 14px;
    margin-bottom: 14px;
}

.pj-search {
    flex: 1 1 240px;
}

.pj-search input,
.pj-select select {
    width: 100%;
    height: 44px;
    padding: 0 12px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: transparent;
    color: inherit;
    font: inherit;
}

.pj-select {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 150px;
}

.pj-select label {
    color: var(--muted);
    font-size: 0.8rem;
    font-weight: 600;
}

.pj-select option {
    background: Canvas;
    color: CanvasText;
}

.pj-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    height: 44px;
    font-weight: 600;
    cursor: pointer;
}

.pj-toggle input {
    width: 18px;
    height: 18px;
    margin: 0;
}

.pj-search input:focus-visible,
.pj-select select:focus-visible,
.pj-tabs button:focus-visible,
.pj-toggle input:focus-visible,
.pj-title:focus-visible,
.pj-link:focus-visible,
.pj-ghost:focus-visible,
.pj-danger:focus-visible,
.pj-check input:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

/* Table */
.pj-table {
    width: 100%;
    border-collapse: collapse;
}

.pj-table th {
    padding: 10px 14px;
    border-bottom: 1px solid var(--line);
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--muted);
}

.pj-table td {
    padding: 14px;
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
}

.pj-table td[data-label="Updated"] {
    white-space: nowrap;
}

.pj-cell {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.pj-thumb {
    position: relative;
    flex: none;
    display: grid;
    place-items: center;
    width: 56px;
    height: 42px;
    overflow: hidden;
    border-radius: 9px;
    background-size: cover;
}

.pj-thumb img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pj-thumb b {
    color: rgb(255 255 255 / 0.92);
    font-size: 1.1rem;
}

.pj-main {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 2px 8px;
    min-width: 0;
}

.pj-title {
    padding: 0;
    border: 0;
    background: none;
    color: inherit;
    font: inherit;
    font-weight: 650;
    text-align: left;
    cursor: pointer;
}

.pj-title:hover {
    text-decoration: underline;
    text-underline-offset: 3px;
}

.pj-summary {
    flex-basis: 100%;
    color: var(--muted);
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 38ch;
}

.pj-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.pj-tags span,
.pj-pill {
    padding: 1px 9px;
    border-radius: 999px;
    background: var(--soft);
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
}

.pj-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    background: color-mix(in srgb, #1a7f4b 16%, transparent);
    font-size: 0.85rem;
    font-weight: 600;
    white-space: nowrap;
}

.pj-badge--draft {
    background: var(--soft);
    color: var(--muted);
}

.pj-actions {
    text-align: right;
    white-space: nowrap;
}

.pj-sr {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}

.pj-foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 16px;
}

.pj-foot p {
    margin: 0;
}

.pj-pager {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Buttons */
.pj-link {
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

.pj-link--danger {
    color: var(--danger);
}

.pj-link:disabled {
    opacity: 0.45;
    cursor: progress;
}

.pj-ghost,
.pj-danger {
    min-height: 42px;
    padding: 0 18px;
    border-radius: 10px;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

.pj-ghost {
    border: 1px solid var(--line);
    background: none;
    color: inherit;
}

.pj-ghost:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.pj-danger {
    border: 0;
    background: var(--danger);
    color: #fff;
}

.pj-danger:disabled {
    opacity: 0.7;
    cursor: progress;
}

/* Dialogs */
.pj-dialog {
    width: min(620px, calc(100vw - 32px));
    max-height: calc(100vh - 32px);
    padding: 0;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: Canvas;
    color: CanvasText;
    color-scheme: light dark;
}

.pj-dialog--small {
    width: min(460px, calc(100vw - 32px));
}

.pj-dialog::backdrop {
    background: rgb(8 16 22 / 0.55);
}

.pj-dialog-body {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 24px;
}

.pj-dialog-body h2 {
    margin: 0;
    font-size: 1.25rem;
}

.pj-two {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.pj-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.pj-field > label {
    font-size: 0.9rem;
    font-weight: 600;
}

.pj-field input[type="text"],
.pj-field input[type="url"],
.pj-field input[type="number"],
.pj-field select,
.pj-field textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 0 12px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: transparent;
    color: inherit;
    font: inherit;
}

.pj-field input,
.pj-field select {
    height: 44px;
}

.pj-field textarea {
    padding: 10px 12px;
    resize: vertical;
    line-height: 1.5;
}

.pj-field select option {
    background: Canvas;
    color: CanvasText;
}

.pj-field input:focus-visible,
.pj-field select:focus-visible,
.pj-field textarea:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 1px;
}

.pj-field [aria-invalid="true"] {
    border-color: var(--danger);
}

.pj-colour {
    display: flex;
    gap: 8px;
}

.pj-colour input[type="color"] {
    flex: none;
    width: 52px;
    height: 44px;
    padding: 4px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: transparent;
    cursor: pointer;
}

.pj-hint {
    color: var(--muted);
    font-size: 0.82rem;
}

.pj-check {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
}

.pj-check input {
    width: 18px;
    height: 18px;
    margin: 2px 0 0;
}

.pj-check span {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.pj-check small {
    color: var(--muted);
    font-size: 0.85rem;
}

.pj-error {
    color: var(--danger);
    font-size: 0.875rem;
    font-weight: 500;
}

:root[data-theme="dark"] .pj-error,
:root[data-theme="dark"] .pj-link--danger {
    color: #ffb4ab;
}

.pj-error--box {
    margin: 0;
    padding: 10px 12px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--danger) 14%, transparent);
}

.pj-buttons {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 4px;
}

/* View dialog */
.pj-view-art {
    position: relative;
    display: grid;
    place-items: center;
    aspect-ratio: 16 / 6;
    overflow: hidden;
    border-radius: 14px 14px 0 0;
    background-size: cover;
}

.pj-view-art img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pj-view-art b {
    color: rgb(255 255 255 / 0.9);
    font-size: 3rem;
}

.pj-view-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.pj-view-summary {
    margin: 0;
    color: var(--muted);
    font-size: 1.05rem;
}

.pj-view-text p {
    margin: 0 0 10px;
}

.pj-facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px 16px;
    margin: 0;
    padding-top: 14px;
    border-top: 1px solid var(--line);
}

.pj-facts div {
    min-width: 0;
}

.pj-facts dt {
    color: var(--muted);
    font-size: 0.8rem;
    font-weight: 600;
}

.pj-facts dd {
    margin: 2px 0 0;
    overflow-wrap: anywhere;
}

.pj-facts a {
    color: inherit;
}

/* Phones: each project becomes a stacked card */
@media (max-width: 760px) {
    .pj-two,
    .pj-facts {
        grid-template-columns: 1fr;
    }

    .pj-tabs {
        display: flex;
    }

    .pj-tabs button {
        flex: 1;
        justify-content: center;
        padding: 0 8px;
    }

    .pj-table thead {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0 0 0 0);
    }

    .pj-table,
    .pj-table tbody,
    .pj-table tr,
    .pj-table td {
        display: block;
    }

    .pj-table tr {
        padding: 10px 0;
        border-bottom: 1px solid var(--line);
    }

    .pj-table td {
        padding: 6px 0;
        border: 0;
    }

    .pj-table td[data-label]:not([data-label="Project"])::before {
        content: attr(data-label);
        display: block;
        font-size: 0.8rem;
        color: var(--muted);
    }

    .pj-summary {
        max-width: none;
        white-space: normal;
    }

    .pj-actions {
        text-align: left;
    }

    .pj-actions .pj-link:first-child {
        padding-left: 0;
    }
}
</style>