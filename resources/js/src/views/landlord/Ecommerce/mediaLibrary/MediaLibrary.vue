<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import Axios from "@/services/axios/index.js";
import { MEDIAS, MEDIAS_BULK_DELETE } from "@/services/endpoints/Ecommerce.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import MediaInfoModal from "@/views/landlord/Ecommerce/mediaLibrary/MediaInfoModal.vue";
import ImageAltModal from "@/views/landlord/Ecommerce/mediaLibrary/ImageAltModal.vue";
import FolderCreateModal from "@/views/landlord/Ecommerce/mediaLibrary/FolderCreateModal.vue";
import BulkDeleteModal from "@/views/landlord/Ecommerce/mediaLibrary/BulkDeleteModal.vue";
import { MoreVert, Xmark, Search, Folder } from '@iconoir/vue';
import usePermission from "@/composable/permission/usePermission.js";
import { useToast } from "vue-toastification";

// ✅ Media list and pagination
const mediaList = ref([]);
const mediaFolders = ref([]);
const folderMap = ref({});
const selectedFolder = ref(null);
const folderMediaList = ref([]);
const folderAttachments = ref([]);
const uploadingFiles = ref([]);
const selectedMediaIds = ref(new Set());
const selectedFolderMediaIds = ref(new Set());
const folderFormData = ref({ images: [] });
const isBulkDeleteModalVisible = ref(false);
const bulkDeleteIds = ref([]);

// ✅ Search
const searchQuery = ref('');
const searchTimer = ref(null);

const onSearchInput = () => {
    clearTimeout(searchTimer.value);
    searchTimer.value = setTimeout(() => {
        if (selectedFolder.value) {
            fetchFolderMedia(selectedFolder.value.id, 1);
        } else {
            fetchMedia(1);
        }
    }, 500);
};

// ✅ Filtered folders by search (client-side on folder name)
const filteredFolders = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) return null; // null = not in search mode, use normal folder logic
    return mediaFolders.value.filter(f =>
        f.name.toLowerCase().includes(query)
    );
});

// Root folders shown when NOT inside a folder and NOT searching
// const rootFolders = computed(() =>
//     mediaFolders.value.filter(f => f.parent_id === null && f.id !== 1)
// );

const rootFolders = computed(() =>
    folderMap.value['root'] || []
);

const showBulkDeleteModal = (ids) => {
    bulkDeleteIds.value = ids;
    isBulkDeleteModalVisible.value = true;
};

const onBulkDeleteConfirmed = async () => {
    if (!bulkDeleteIds.value.length) {
        isBulkDeleteModalVisible.value = false;
        return;
    }
    try {
        preloader.value = true;
        await Axios.post(`${MEDIAS_BULK_DELETE}`, { ids: bulkDeleteIds.value });
        toast.success(`${bulkDeleteIds.value.length} files deleted successfully.`);
        selectedMediaIds.value.clear();
        selectedFolderMediaIds.value.clear();
        bulkDeleteIds.value = [];
        if (selectedFolder.value) {
            await fetchFolderMedia(selectedFolder.value.id, 1);
        } else {
            await fetchMedia(1);
        }
    } catch (err) {
        toast.error('Failed to delete some files.');
        console.error(err);
    } finally {
        preloader.value = false;
        isBulkDeleteModalVisible.value = false;
    }
};

const folderErrors = ref({});
const folderLoading = ref(false);
const folderCurrentPage = ref(1);
const folderLastPage = ref(1);
const currentPage = ref(1);
const lastPage = ref(1);
const loadingMore = ref(false);
const modalType = ref('');
const emit = defineEmits(['close', 'refresh']);
const { canAccess } = usePermission();

const formData = ref({ images: [] });
const attachments = ref([]);
const deletableAttachmentIds = ref([]);
const preloader = ref(false);
const errors = ref({});
const toast = useToast();

// 📎 Copy media URL
const copyUrl = async (text) => {
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            const textarea = document.createElement("textarea");
            textarea.value = text;
            textarea.style.position = "fixed";
            textarea.style.opacity = "0";
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand("copy");
            document.body.removeChild(textarea);
        }
        toast.success("Copied URL to clipboard");
    } catch (e) {
        toast.error("Clipboard copy not supported in this browser.");
    }
};

const deleteMedia = (id) => {
    deleteUrl.value = `${MEDIAS}/${id}`;
    isDeleteModal.value = true;
};

const folderFilesSelected = (files) => {
    if (!selectedFolder.value) return;
    folderAttachments.value = files;
    const data = new FormData();
    files.forEach(file => { data.append('files[]', file); });
    data.append('collection', selectedFolder.value.name);
    uploadingFiles.value = files.map(file => ({ name: file.name, size: file.size, progress: 0 }));
    preloader.value = true;
    Axios.post(
        `/admin/ecommerce/upload-medias/${selectedFolder.value.id}`,
        data,
        {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (progressEvent) => {
                const totalLength = progressEvent.total;
                if (totalLength !== undefined) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / totalLength);
                    uploadingFiles.value = uploadingFiles.value.map(file => ({ ...file, progress: percentCompleted }));
                }
            }
        }
    )
        .then(() => {
            fetchFolderMedia(selectedFolder.value.id, 1).finally(() => {
                folderFormData.value.images = [];
                folderAttachments.value = [];
            });
        })
        .catch(error => {
            folderErrors.value = error?.response?.data?.errors || {};
            console.error('Folder upload error', error);
        })
        .finally(() => {
            preloader.value = false;
            setTimeout(() => { uploadingFiles.value = []; }, 1000);
        });
};

const filesSelected = (files) => {
    attachments.value = files;
    const data = new FormData();
    files.forEach(file => data.append('files[]', file));
    uploadingFiles.value = files.map(file => ({ name: file.name, size: file.size, progress: 0 }));
    preloader.value = true;
    Axios.post('/admin/ecommerce/upload-medias', data, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
                const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                uploadingFiles.value = uploadingFiles.value.map(f => ({ ...f, progress: percent }));
            }
        }
    })
        .then(() => fetchMedia(1))
        .finally(() => {
            preloader.value = false;
            setTimeout(() => uploadingFiles.value = [], 1000);
        });
};

const removeFolderAttachments = (id) => {
    folderFormData.value.images = folderFormData.value.images.filter(img => img.id !== id);
};

// const subFolders = computed(() => {
//     if (!selectedFolder.value) return [];
//     return mediaFolders.value.filter(folder => folder.parent_id === selectedFolder.value.id);
// });

const subFolders = computed(() => {
    if (!selectedFolder.value) return [];

    return folderMap.value[selectedFolder.value.id] || [];
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const viewImageInfo = (media) => {
    selectedData.value = media;
    isModalActive.value = true;
    modalType.value = 'view-image-info';
};
const altTextCreate = (media) => {
    selectedData.value = media;
    isModalActive.value = true;
    modalType.value = 'image-alt-text';
};
const folderCreateName = () => {
    selectedData.value = selectedFolder.value;
    isModalActive.value = true;
    modalType.value = 'folder-name-create';
};

const isImage = (url) => {
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
    const ext = url.split('.').pop().toLowerCase();
    return imageExtensions.includes(ext);
};

const getFileIcon = (url) => {
    const ext = url.split('.').pop().toLowerCase();
    switch (ext) {
        case 'pdf': return 'assets/images/icons/pdf-icon.png';
        case 'doc': case 'docx': return 'assets/images/icons/doc-icon.png';
        case 'xls': case 'xlsx': return 'assets/images/icons/xls-icon.png';
        case 'zip': return 'assets/images/icons/zip-icon.png';
        default: return 'assets/images/icons/file-icon.png';
    }
};

// 📥 Fetch media — search query server-side এ পাঠানো হচ্ছে
const fetchMedia = async (page = 1) => {
    try {
        loadingMore.value = true;
        const params = new URLSearchParams({ page });
        if (searchQuery.value.trim()) params.append('search', searchQuery.value.trim());
        const { data } = await Axios.get(`${MEDIAS}?${params}`);
        if (page === 1) {
            mediaList.value = data.data;
        } else {
            mediaList.value = [...mediaList.value, ...data.data];
        }
        currentPage.value = data.current_page;
        lastPage.value = data.last_page;
    } catch (error) {
        console.error('Error fetching media:', error);
    } finally {
        loadingMore.value = false;
    }
};

const openFolderPanel = async (folder) => {
    selectedFolder.value = folder;
    folderCurrentPage.value = 1;
    folderMediaList.value = [];
    searchQuery.value = '';

    // 🔥 load subfolders only when needed
    if (!folderMap.value[folder.id]) {
        await fetchFolders(folder.id);
    }

    await fetchFolderMedia(folder.id, 1);
};

// 📥 Fetch folder media — search query server-side এ পাঠানো হচ্ছে
const fetchFolderMedia = async (folderId, page = 1) => {
    try {
        folderLoading.value = true;
        const params = new URLSearchParams({ folder_id: folderId, page });
        if (searchQuery.value.trim()) params.append('search', searchQuery.value.trim());
        const { data } = await Axios.get(`${MEDIAS}?${params}`);
        if (page === 1) {
            folderMediaList.value = data.data;
        } else {
            folderMediaList.value = [...folderMediaList.value, ...data.data];
        }
        folderCurrentPage.value = data.current_page;
        folderLastPage.value = data.last_page;
    } catch (error) {
        console.error('Error fetching folder media:', error);
    } finally {
        folderLoading.value = false;
    }
};

const loadMoreFolderMedia = () => {
    if (folderCurrentPage.value < folderLastPage.value) {
        fetchFolderMedia(selectedFolder.value.id, folderCurrentPage.value + 1);
    }
};

const fetchFolders = async (parentId = null) => {
    try {
        const params = parentId ? { parent_id: parentId } : {};
        const { data } = await Axios.get('/admin/ecommerce/media-folders', { params });

        if (parentId) {
            // store children by parent_id
            folderMap.value[parentId] = data;

            // merge into mediaFolders (so old UI still works)
            mediaFolders.value = [...mediaFolders.value, ...data];
        } else {
            // root folders
            folderMap.value['root'] = data;
            mediaFolders.value = data;
        }
    } catch (error) {
        console.error('Error fetching folders:', error);
    }
};

const handleHeaderUpload = (filesEvent) => {
    const files = filesEvent.target.files;
    if (!files || !files.length) return;
    if (selectedFolder.value) {
        folderFilesSelected(Array.from(files));
    } else {
        filesSelected(Array.from(files));
    }
    filesEvent.target.value = '';
};

const handleVideoUpload = (filesEvent) => {
    const files = filesEvent.target.files;
    if (!files || !files.length) return;
    if (selectedFolder.value) {
        folderFilesVideoSelected(Array.from(files));
    } else {
        filesVideoSelected(Array.from(files));
    }
    filesEvent.target.value = '';
};

const CHUNK_SIZE = 2 * 1024 * 1024;

const uploadFileInChunks = async (file, uploadUrl, onProgress) => {
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
    const fileId = `${Date.now()}-${file.name}`;
    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
        const start = chunkIndex * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, file.size);
        const chunk = file.slice(start, end);
        const formData = new FormData();
        formData.append('chunk', chunk);
        formData.append('chunkIndex', chunkIndex);
        formData.append('totalChunks', totalChunks);
        formData.append('fileId', fileId);
        formData.append('fileName', file.name);
        await Axios.post(uploadUrl, formData, { headers: { 'Content-Type': 'multipart/form-data' } });
        const percent = Math.round(((chunkIndex + 1) / totalChunks) * 100);
        onProgress(percent);
    }
};

const folderFilesVideoSelected = async (files) => {
    if (!selectedFolder.value) return;
    folderAttachments.value = files;
    preloader.value = true;
    uploadingFiles.value = files.map(file => ({ name: file.name, size: file.size, progress: 0 }));
    try {
        await Promise.all(
            files.map((file, index) =>
                uploadFileInChunks(
                    file,
                    `/admin/ecommerce/upload-medias-chunk/${selectedFolder.value.id}`,
                    (percent) => { uploadingFiles.value[index] = { ...uploadingFiles.value[index], progress: percent }; }
                )
            )
        );
        await fetchFolderMedia(selectedFolder.value.id, 1);
        folderFormData.value.images = [];
        folderAttachments.value = [];
    } catch (error) {
        folderErrors.value = error?.response?.data?.errors || {};
        console.error('Folder chunk upload error', error);
    } finally {
        preloader.value = false;
        setTimeout(() => { uploadingFiles.value = []; }, 1000);
    }
};

const filesVideoSelected = async (files) => {
    attachments.value = files;
    preloader.value = true;
    uploadingFiles.value = files.map(file => ({ name: file.name, size: file.size, progress: 0 }));
    try {
        await Promise.all(
            files.map((file, index) =>
                uploadFileInChunks(
                    file,
                    '/admin/ecommerce/upload-medias-chunk',
                    (percent) => { uploadingFiles.value[index] = { ...uploadingFiles.value[index], progress: percent }; }
                )
            )
        );
        await fetchMedia(1);
    } catch (error) {
        console.error('Chunk upload error', error);
    } finally {
        preloader.value = false;
        setTimeout(() => uploadingFiles.value = [], 1000);
    }
};

const toggleSelectMedia = (id, checked) => {
    if (checked) { selectedMediaIds.value.add(id); } else { selectedMediaIds.value.delete(id); }
    selectedMediaIds.value = new Set(selectedMediaIds.value);
};

const toggleSelectFolderMedia = (id, checked) => {
    if (checked) { selectedFolderMediaIds.value.add(id); } else { selectedFolderMediaIds.value.delete(id); }
    selectedFolderMediaIds.value = new Set(selectedFolderMediaIds.value);
};

const loadMore = () => {
    if (currentPage.value < lastPage.value) { fetchMedia(currentPage.value + 1); }
};

const folderBreadcrumbs = computed(() => {
    if (!selectedFolder.value) return [];
    const trail = [];
    let current = selectedFolder.value;
    while (current) {
        trail.unshift(current);
        current = mediaFolders.value.find(f => f.id === current.parent_id);
    }
    return trail;
});

const goBack = () => {
    if (!selectedFolder.value) return;
    if (selectedFolder.value.parent_id) {
        const parent = mediaFolders.value.find(f => f.id === selectedFolder.value.parent_id);
        if (parent) { openFolderPanel(parent); }
    } else {
        selectedFolder.value = null;
    }
};

// ── Delete confirm handler (needed for app-delete-modal @confirmed)
const handleDeleteConfirmed = async () => {
    if (selectedFolder.value) {
        await fetchFolderMedia(selectedFolder.value.id, 1);
    } else {
        await fetchMedia(1);
    }
};

watch(selectedFolder, () => {
    folderFormData.value.images = [];
    folderAttachments.value = [];
    folderErrors.value = {};
    selectedFolderMediaIds.value.clear();
    selectedMediaIds.value.clear();
});

watch(mediaList, () => { selectedMediaIds.value.clear(); });
watch(folderMediaList, () => { selectedFolderMediaIds.value.clear(); });

onMounted(() => {
    fetchMedia(1);
    fetchFolders();
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('media_library')" />

        <!-- Breadcrumbs + Back -->
        <div class="d-flex align-items-center gap-2 mb-3">
            <button v-if="selectedFolder" class="btn btn-sm btn-outline-secondary" @click="goBack">
                ← Back
            </button>
            <nav class="breadcrumb mb-0">
                <span class="breadcrumb-item text-primary" style="cursor:pointer"
                    @click="selectedFolder = null; searchQuery = ''">
                    All Media
                </span>
                <span v-for="(folder, index) in folderBreadcrumbs" :key="folder.id" class="breadcrumb-item"
                    :class="{ active: index === folderBreadcrumbs.length - 1 }" style="cursor:pointer"
                    @click="index !== folderBreadcrumbs.length - 1 && openFolderPanel(folder)">
                    {{ folder.name }}
                </span>
            </nav>
        </div>

        <div class="media-library-section">
            <div class="media-library">

                <!-- Header: Search + Actions -->
                <div class="d-flex justify-content-between align-items-center">
                    <div class="search-input flex-shrink-0 mb-4">
                        <span class="search-icon">
                            <Search />
                        </span>
                        <input v-model="searchQuery" @input="onSearchInput" :placeholder="$t('search')" type="text"
                            name="search" autocomplete="off"
                            class="form-control form-control-sm bg-card border-transparent rounded-default" />
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Bulk delete all -->
                        <div class="bulk-actions" v-if="canAccess('bulk_delete_medias')">
                            <button class="btn btn-danger" @click="showBulkDeleteModal(
                                selectedFolder
                                    ? folderMediaList.map(m => m.id)
                                    : mediaList.map(m => m.id)
                            )" :disabled="(selectedFolder ? folderMediaList.length : mediaList.length) === 0">
                                Delete All ({{ selectedFolder ? folderMediaList.length : mediaList.length }})
                            </button>
                        </div>

                        <button class="btn btn-primary" @click="folderCreateName"
                            v-if="canAccess('create_media_folders')">
                            {{ $t('create_folder') }}
                        </button>

                        <div v-if="canAccess('update_medias')">
                            <button class="btn btn-primary" @click="$refs.headerFileInput.click()">Upload Files</button>
                            <input type="file" multiple ref="headerFileInput" style="display: none;"
                                @change="handleHeaderUpload" />
                        </div>

                        <div v-if="canAccess('upload_medias_video')">
                            <button class="btn btn-info" @click="$refs.videoInput.click()">Upload Video</button>
                            <input type="file" ref="videoInput" style="display: none;" accept="video/*"
                                @change="handleVideoUpload" />
                        </div>

                        <div v-if="selectedFolder">
                            <button class="btn btn-secondary" @click="selectedFolder = null">Back to All Media</button>
                        </div>
                    </div>
                </div>

                <!-- ── Search result: matching folders across all levels ── -->
                <template v-if="searchQuery.trim() && filteredFolders !== null">
                    <div v-if="filteredFolders.length" class="mb-3">
                        <h6 class="text-muted mb-2">Folders matching "{{ searchQuery }}"</h6>
                        <ul class="media-folders-list">
                            <li v-for="folder in filteredFolders" :key="folder.id"
                                class="folder-item flex items-center gap-2" @click="openFolderPanel(folder)"
                                style="cursor: pointer;">
                                <div class="d-flex gap-2 flex-column">
                                    <Folder />
                                    <span>{{ folder.name }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <p v-else-if="!mediaList.length && !folderMediaList.length" class="text-muted">
                        No folders or files found for "{{ searchQuery }}"
                    </p>
                </template>

                <!-- ── Normal folder grid (no search) ── -->
                <div class="media-folders">
                    <template v-if="!selectedFolder && !searchQuery.trim()">
                        <h5>Folders</h5>
                        <ul class="media-folders-list">
                            <li v-for="folder in rootFolders" :key="folder.id"
                                class="folder-item flex items-center gap-2"
                                :class="{ 'selected': selectedFolder?.id === folder.id }"
                                @click="openFolderPanel(folder)" style="cursor: pointer;">
                                <div class="d-flex gap-2 flex-column">
                                    <Folder />
                                    <span>{{ folder.name }}</span>
                                </div>
                            </li>
                        </ul>
                    </template>

                    <!-- ── Folder panel (inside a folder) ── -->
                    <div v-if="selectedFolder" class="folder-media-panel mt-4 p-3 border rounded">
                        <div class="mb-4">
                            <!-- Sub-folders (only when not searching) -->
                            <div v-if="subFolders.length && !searchQuery.trim()" class="mb-4">
                                <ul class="media-folders-list">
                                    <li v-for="folder in subFolders" :key="folder.id"
                                        class="folder-item flex items-center gap-2" @click="openFolderPanel(folder)"
                                        style="cursor:pointer;">
                                        <div class="d-flex gap-2 flex-column">
                                            <Folder />
                                            <span>{{ folder.name }}</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="folderFormData.images?.length" class="w-100 d-flex flex-wrap gap-2 mt-3">
                                <template v-for="attachment in folderFormData.images" :key="attachment.id">
                                    <div class="d-block position-relative">
                                        <img v-if="isImage(attachment.url)" :src="attachment.url" alt="attachment"
                                            class="height-80 border rounded-3 overflow-hidden" />
                                        <div v-else>
                                            <a :href="attachment.url" target="_blank"
                                                class="d-flex align-items-center justify-content-center height-80 width-80 border rounded-3 bg-light">
                                                <img :src="getFileIcon(attachment.url)" alt="file-icon"
                                                    class="height-80 border rounded-3 overflow-hidden" />
                                            </a>
                                        </div>
                                        <div class="remove-media" @click="removeFolderAttachments(attachment.id)">
                                            <Xmark />
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Bulk delete selected inside folder -->
                        <div v-if="selectedFolderMediaIds.size" class="bulk-actions mb-3">
                            <button class="btn btn-danger" @click="showBulkDeleteModal([...selectedFolderMediaIds])">
                                Delete Selected ({{ selectedFolderMediaIds.size }})
                            </button>
                        </div>

                        <!-- Folder media grid -->
                        <div class="media-library-content folder-media-content">
                            <div v-for="media in folderMediaList" :key="media.id" class="media-item">
                                <div class="form-check job-check">
                                    <input class="form-check-input" type="checkbox" :value="media.id"
                                        @change="toggleSelectFolderMedia(media.id, $event.target.checked)"
                                        :checked="selectedFolderMediaIds.has(media.id)" />
                                </div>
                                <div class="dropdown image-dropdown">
                                    <button class="btn btn-light" type="button" :id="`folderDropdown-${media.id}`"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <MoreVert />
                                    </button>
                                    <ul class="dropdown-menu" :aria-labelledby="`folderDropdown-${media.id}`">
                                        <li>
                                            <button class="dropdown-item" v-if="canAccess('view_medias')"
                                                @click="viewImageInfo(media)">View</button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item" v-if="canAccess('create_media_alt_text')"
                                                @click="altTextCreate(media)">Alt Text</button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item" @click="copyUrl(media.url)">Copy URL</button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger" v-if="canAccess('delete_medias')"
                                                @click="deleteMedia(media.id)">Delete</button>
                                        </li>
                                    </ul>
                                </div>
                                <div class="image-card">
                                    <a v-if="media.mime_type?.includes('image')" :href="media.url" target="_blank">
                                        <img :src="media.url" alt="media" />
                                    </a>
                                    <div v-else class="file-icon">
                                        <img :src="getFileIcon(media.url)" alt="icon" />
                                    </div>
                                </div>
                                <div class="media-item-info">{{ media.name }}</div>
                            </div>
                        </div>

                        <!-- Empty state inside folder -->
                        <p v-if="!folderLoading && !folderMediaList.length" class="text-muted text-center mt-4">
                            {{ searchQuery.trim() ? `No files found for "${searchQuery}"` : 'This folder is empty.' }}
                        </p>

                        <button v-if="folderCurrentPage < folderLastPage" @click="loadMoreFolderMedia"
                            :disabled="folderLoading" class="btn btn-primary mt-3">
                            {{ folderLoading ? 'Loading...' : 'Load More' }}
                        </button>
                    </div>
                </div>

                <!-- ── Root / search: bulk delete selected ── -->
                <div v-if="selectedMediaIds.size" class="bulk-actions mb-3">
                    <button class="btn btn-danger" @click="showBulkDeleteModal([...selectedMediaIds])">
                        Delete Selected ({{ selectedMediaIds.size }})
                    </button>
                </div>

                <!-- ── Root media grid ── -->
                <template v-if="!selectedFolder">
                    <div class="media-library-content">
                        <div v-for="media in mediaList" :key="media.id" class="media-item">
                            <div class="form-check job-check">
                                <input class="form-check-input" type="checkbox" :value="media.id"
                                    @change="toggleSelectMedia(media.id, $event.target.checked)"
                                    :checked="selectedMediaIds.has(media.id)" />
                            </div>
                            <div class="dropdown image-dropdown">
                                <button class="btn btn-light" type="button" :id="`dropdownMenu-${media.id}`"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <MoreVert />
                                </button>
                                <ul class="dropdown-menu" :aria-labelledby="`dropdownMenu-${media.id}`">
                                    <li>
                                        <button class="dropdown-item" v-if="canAccess('view_medias')"
                                            @click="viewImageInfo(media)">View</button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" v-if="canAccess('create_media_alt_text')"
                                            @click="altTextCreate(media)">Alt Text</button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" @click="copyUrl(media.url)">Copy URL</button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-danger" v-if="canAccess('delete_medias')"
                                            @click="deleteMedia(media.id)">Delete</button>
                                    </li>
                                </ul>
                            </div>
                            <div class="image-card">
                                <a v-if="media.mime_type.includes('image')" :href="media.url" target="_blank">
                                    <img :src="media.url" alt="media" />
                                </a>
                                <div v-else class="file-icon">
                                    <img :src="getFileIcon(media.url)" alt="icon" />
                                </div>
                            </div>
                            <div class="media-item-info">{{ media.name }}</div>
                        </div>
                    </div>

                    <!-- Empty state root -->
                    <p v-if="!loadingMore && !mediaList.length" class="text-muted text-center mt-4">
                        {{ searchQuery.trim() ? `No files found for "${searchQuery}"` : 'No media files yet.' }}
                    </p>

                    <!-- Load more root -->
                    <div class="text-center mt-3" v-if="currentPage < lastPage">
                        <button class="btn btn-primary" @click="loadMore" :disabled="loadingMore">
                            {{ loadingMore ? 'Loading...' : 'Load More' }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <image-alt-modal v-if="isModalActive && modalType === 'image-alt-text'" modal-id="image-alt-text-modal"
        :media="selectedData" @close="closeModal" @refresh="fetchMedia()" />
    <folder-create-modal v-if="isModalActive && modalType === 'folder-name-create'" :parent-folder="selectedData"
        modal-id="folder-create-modal" @submit="fetchFolders" @close="closeModal" />
    <media-info-modal v-if="isModalActive && modalType === 'view-image-info'" modal-id="view-image-info-modal"
        :media="selectedData" :showFooter="false" @copy-url="copyUrl" @close="closeModal" />
    <bulk-delete-modal :show="isBulkDeleteModalVisible" :selected-ids="bulkDeleteIds" @confirmed="onBulkDeleteConfirmed"
        @cancelled="isBulkDeleteModalVisible = false" />
    <app-delete-modal v-if="isDeleteModal" :selected-url="deleteUrl" @cancelled="cancelled"
        @confirmed="handleDeleteConfirmed" />

    <!-- Upload progress -->
    <div v-if="uploadingFiles.length" class="upload-progress-wrapper">
        <h6 class="upload-progress-title">Uploading Files</h6>
        <ul class="upload-progress-list">
            <li v-for="(file, index) in uploadingFiles" :key="index" class="upload-progress-item">
                <div class="file-info">
                    <strong class="file-name">{{ file.name }}</strong>
                    <small class="file-size">{{ (file.size / 1024).toFixed(2) }} KB</small>
                </div>
                <div class="progress-bar-wrapper" role="progressbar" :aria-valuenow="file.progress" aria-valuemin="0"
                    aria-valuemax="100">
                    <div class="progress-bar-fill" :style="{ width: file.progress + '%' }"></div>
                </div>
                <span class="progress-percent">{{ file.progress }}%</span>
            </li>
        </ul>
    </div>
</template>

<style scoped lang="scss"></style>