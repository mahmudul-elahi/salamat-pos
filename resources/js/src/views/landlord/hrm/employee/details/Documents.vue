<script setup>
defineOptions({ inheritAttrs: false });

import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES } from '@/services/endpoints/hrm.js';
import { useToast } from 'vue-toastification';
import { Upload, Trash, Page, Attachment, Download, Xmark, OpenNewWindow } from '@iconoir/vue';

const route      = useRoute();
const toast      = useToast();
const employeeId = route.params.id;

const documents          = ref([]);
const loading            = ref(true);
const uploading          = ref(false);
const afterUploadSuccess = ref(false);

const name      = ref('');
const files     = ref([]);
const nameError = ref('');
const fileError = ref('');

/* ── Fetch ──────────────────────────────────── */
const fetchDocuments = async () => {
    try {
        const { data } = await Axios.get(`${HRM_EMPLOYEES}/${employeeId}/documents`);
        documents.value = data;
    } finally {
        loading.value = false;
    }
};

onMounted(fetchDocuments);

/* ── Upload ─────────────────────────────────── */
const onFilesChanged = (selected) => {
    files.value = selected;
    fileError.value = '';
};

const upload = async () => {
    nameError.value = '';
    fileError.value = '';

    if (!name.value.trim())  { nameError.value = 'Document name is required.'; return; }
    if (!files.value.length) { fileError.value = 'Please select at least one file.'; return; }

    try {
        uploading.value = true;
        const fd = new FormData();
        fd.append('name', name.value.trim());
        files.value.forEach(f => fd.append('files[]', f));

        const { data } = await Axios.post(
            `${HRM_EMPLOYEES}/${employeeId}/documents`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        toast.success(data.message);
        name.value  = '';
        files.value = [];

        // signal the uploader to clear itself
        afterUploadSuccess.value = true;
        setTimeout(() => { afterUploadSuccess.value = false; }, 100);

        await fetchDocuments();
    } catch ({ response }) {
        if (response?.status === 422) {
            const errs = response.data.errors ?? {};
            nameError.value = errs.name?.[0] ?? '';
            fileError.value = errs.files?.[0] ?? errs['files.0']?.[0] ?? '';
        } else {
            toast.error(response?.data?.message ?? 'Upload failed.');
        }
    } finally {
        uploading.value = false;
    }
};

/* ── Delete ─────────────────────────────────── */
const deleting = ref(null);

const deleteDoc = async (doc) => {
    if (!confirm(`Delete "${doc.name}"?`)) return;
    try {
        deleting.value = doc.id;
        await Axios.delete(`/admin/hrm/employee-documents/${doc.id}`);
        toast.success('Document deleted.');
        documents.value = documents.value.filter(d => d.id !== doc.id);
    } catch {
        toast.error('Failed to delete document.');
    } finally {
        deleting.value = null;
    }
};

/* ── Helpers ─────────────────────────────────── */
const fileType = (url) => {
    const ext = url?.split('.').pop()?.toLowerCase();
    if (['jpg','jpeg','png','gif','webp','svg'].includes(ext)) return 'image';
    if (ext === 'pdf')                                          return 'pdf';
    if (['doc','docx'].includes(ext))                          return 'word';
    if (['xls','xlsx'].includes(ext))                          return 'excel';
    return 'file';
};

const typeColor = { image: '#10b981', pdf: '#ef4444', word: '#3b82f6', excel: '#22c55e', file: '#6b7280' };

/* ── Preview modal ───────────────────────────── */
const previewDoc = ref(null);

const openPreview = (doc) => { previewDoc.value = doc; };
const closePreview = () => { previewDoc.value = null; };

const previewType = (doc) => fileType(doc?.file_url);

const onOverlayClick = (e) => { if (e.target === e.currentTarget) closePreview(); };
</script>

<template>
    <div>
        <div class="detail-section-header">
            <h6 class="detail-section-title">Documents</h6>
            <p class="detail-section-sub">Upload and manage employee documents and attachments</p>
        </div>

        <!-- ── Upload form ────────────────────────── -->
        <div class="doc-upload-card">
            <div class="row g-3 align-items-start">

                <!-- Name -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Document Name <span class="text-danger">*</span>
                    </label>
                    <input
                        v-model="name"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': nameError }"
                        placeholder="e.g. NID Card, Resume"
                    />
                    <div v-if="nameError" class="invalid-feedback">{{ nameError }}</div>
                </div>

                <!-- Multi-uploader -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">
                        Attachments <span class="text-danger">*</span>
                    </label>
                    <app-input
                        id="doc_files"
                        type="multi-image-uploader"
                        :after-upload-success="afterUploadSuccess"
                        @changeFile="onFilesChanged"
                    />
                    <div v-if="fileError" class="text-danger small mt-1">{{ fileError }}</div>
                </div>
            </div>

            <!-- Submit -->
            <div class="mt-3">
                <button class="btn btn-primary" :disabled="uploading" @click="upload">
                    <app-button-loader v-if="uploading" />
                    <Upload v-else />
                    {{ uploading ? 'Uploading…' : 'Upload Documents' }}
                </button>
            </div>
        </div>

        <!-- ── Document list ──────────────────────── -->
        <div class="mt-4">
            <app-loader v-if="loading" />

            <div v-else-if="!documents.length" class="doc-empty">
                <Attachment class="doc-empty-icon" />
                <p>No documents uploaded yet.</p>
            </div>

            <div v-else class="doc-list">
                <div v-for="doc in documents" :key="doc.id" class="doc-row">
                    <div
                        class="doc-row-icon"
                        :style="{ background: (typeColor[fileType(doc.file_url)] ?? '#6b7280') + '18' }"
                    >
                        <Page :style="{ color: typeColor[fileType(doc.file_url)] ?? '#6b7280' }" />
                    </div>

                    <div class="doc-row-info">
                        <p class="doc-row-name">{{ doc.name }}</p>
                        <p class="doc-row-meta">
                            Added by {{ doc.added_by ?? 'Unknown' }} &middot; {{ doc.created_at }}
                        </p>
                    </div>

                    <div class="doc-row-actions">
                        <button class="btn btn-sm btn-outline-primary doc-btn" @click="openPreview(doc)">
                            <Page /> View
                        </button>
                        <button
                            class="btn btn-sm btn-outline-danger doc-btn"
                            :disabled="deleting === doc.id"
                            @click="deleteDoc(doc)"
                        >
                            <Trash />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Preview Modal ─────────────────────── -->
    <Teleport to="body">
        <Transition name="doc-fade">
            <div v-if="previewDoc" class="doc-preview-overlay" @click="onOverlayClick">
                <div class="doc-preview-modal">

                    <!-- Header -->
                    <div class="doc-preview-header">
                        <div class="doc-preview-title">
                            <Page />
                            <span>{{ previewDoc.name }}</span>
                        </div>
                        <div class="doc-preview-actions">
                            <a
                                :href="previewDoc.file_url"
                                :download="previewDoc.name"
                                class="btn btn-sm btn-outline-secondary doc-btn"
                                title="Download"
                            >
                                <Download />
                            </a>
                            <a
                                :href="previewDoc.file_url"
                                target="_blank"
                                class="btn btn-sm btn-outline-secondary doc-btn"
                                title="Open in new tab"
                            >
                                <OpenNewWindow />
                            </a>
                            <button class="doc-preview-close" @click="closePreview" title="Close">
                                <Xmark />
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="doc-preview-body">
                        <!-- Image -->
                        <img
                            v-if="previewType(previewDoc) === 'image'"
                            :src="previewDoc.file_url"
                            :alt="previewDoc.name"
                            class="doc-preview-img"
                        />

                        <!-- PDF -->
                        <iframe
                            v-else-if="previewType(previewDoc) === 'pdf'"
                            :src="previewDoc.file_url"
                            class="doc-preview-iframe"
                            frameborder="0"
                        />

                        <!-- Unsupported -->
                        <div v-else class="doc-preview-fallback">
                            <Page class="doc-preview-fallback-icon" />
                            <p class="doc-preview-fallback-title">{{ previewDoc.name }}</p>
                            <p class="doc-preview-fallback-sub">This file type cannot be previewed in the browser.</p>
                            <a
                                :href="previewDoc.file_url"
                                :download="previewDoc.name"
                                class="btn btn-primary"
                            >
                                <Download /> Download File
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped lang="scss">
.detail-section-header {
    padding-bottom: 18px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 24px;
}
.detail-section-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}
.detail-section-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

/* ── Upload card ──────────────────────────── */
.doc-upload-card {
    background: var(--dashboard-bg);
    border-radius: 14px;
    padding: 20px;
    border: 1px solid var(--border-color);
}

/* ── Document list ────────────────────────── */
.doc-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.doc-row {
    display: flex;
    align-items: center;
    gap: 14px;
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 14px 16px;
    transition: box-shadow 0.2s;

    &:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
}

.doc-row-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    svg { width: 22px; height: 22px; }
}

.doc-row-info {
    flex: 1;
    min-width: 0;
}

.doc-row-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--font-color);
    margin: 0 0 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.doc-row-meta {
    font-size: 12px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

.doc-row-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.doc-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    svg { width: 15px; height: 15px; }
}

/* ── Empty state ──────────────────────────── */
.doc-empty {
    text-align: center;
    padding: 48px 20px;
    color: var(--secondary-font-color, #6b7280);
    p { font-size: 14px; margin: 0; }
}

.doc-empty-icon {
    width: 48px;
    height: 48px;
    color: var(--border-color);
    margin-bottom: 12px;
    stroke-width: 1.2;
}

/* ── Preview Modal ────────────────────────────── */
.doc-preview-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.72);
    backdrop-filter: blur(4px);
    z-index: 1060;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.doc-preview-modal {
    background: var(--white);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);
}

.doc-preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-color);
    background: var(--dashboard-bg);
    flex-shrink: 0;
}

.doc-preview-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--font-color);
    overflow: hidden;
    span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    svg { width: 18px; height: 18px; flex-shrink: 0; color: var(--primary); }
}

.doc-preview-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.doc-preview-close {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--font-color);
    transition: background 0.15s, color 0.15s;

    svg { width: 18px; height: 18px; }

    &:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fca5a5;
    }
}

.doc-preview-body {
    flex: 1;
    overflow: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1e1e2e;
    min-height: 400px;
}

.doc-preview-img {
    max-width: 100%;
    max-height: 76vh;
    object-fit: contain;
    display: block;
}

.doc-preview-iframe {
    width: 100%;
    height: 76vh;
    display: block;
    border: none;
}

.doc-preview-fallback {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 60px 40px;
    text-align: center;
    background: var(--white);
    border-radius: 12px;
    margin: 20px;
}

.doc-preview-fallback-icon {
    width: 56px;
    height: 56px;
    color: #94a3b8;
    stroke-width: 1;
}

.doc-preview-fallback-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--font-color);
    margin: 0;
}

.doc-preview-fallback-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

/* ── Transition ───────────────────────────────── */
.doc-fade-enter-active,
.doc-fade-leave-active { transition: opacity 0.2s ease; }
.doc-fade-enter-from,
.doc-fade-leave-to { opacity: 0; }

.doc-fade-enter-active .doc-preview-modal,
.doc-fade-leave-active .doc-preview-modal { transition: transform 0.2s ease; }
.doc-fade-enter-from .doc-preview-modal,
.doc-fade-leave-to .doc-preview-modal { transform: scale(0.95) translateY(8px); }
</style>
