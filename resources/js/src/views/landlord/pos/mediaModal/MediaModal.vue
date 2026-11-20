<script setup>
import { ref, onMounted, watch, computed, onUnmounted } from 'vue'
import { Modal } from "bootstrap";
import Axios from "@/services/axios/index.js";
import { MEDIA_FOLDERS, MEDIAS, MEDIAS_BULK_DELETE } from "@/services/endpoints/Ecommerce.js";
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
const props = defineProps({
    modalId: String,
    media: Object,
    selectionMode: {
        type: String,
        default: 'single', // 'single' | 'multi'
    }
})
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
const modalType = ref('')
const emit = defineEmits(['close', 'selected']);
const ctrlPressed = ref(false);
const { canAccess } = usePermission();
// ✅ Form data for selected images
const formData = ref({
    images: []
});

// ✅ Attachments for upload
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

const removeFolderAttachments = (id) => {
    folderFormData.value.images = folderFormData.value.images.filter(img => img.id !== id);
};
const subFolders = computed(() => {
    if (!selectedFolder.value) return [];

    return folderMap.value[selectedFolder.value.id] || [];
});
const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()
const viewImageInfo = (media) => {
    selectedData.value = media;
    isModalActive.value = true
    modalType.value = 'view-image-info'
}
const altTextCreate = (media) => {
    selectedData.value = media;
    isModalActive.value = true
    modalType.value = 'image-alt-text'
}
const folderCreateName = () => {
    selectedData.value = selectedFolder.value;
    isModalActive.value = true
    modalType.value = 'folder-name-create'
}
// 🖼 Check if file is image
const isImage = (url) => {
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'tiff'];
    const ext = url.split('?')[0].split('#')[0].split('.').pop().toLowerCase();
    return imageExtensions.includes(ext);
}

// 🧾 Get file type icon
const getFileIcon = (url) => {
    const ext = url.split('.').pop().toLowerCase();
    switch (ext) {
        case 'pdf':
            return 'assets/images/icons/pdf-icon.png';
        case 'doc':
        case 'docx':
            return 'assets/images/icons/doc-icon.png';
        case 'xls':
        case 'xlsx':
            return 'assets/images/icons/xls-icon.png';
        case 'zip':
            return 'assets/images/icons/zip-icon.png';
        default:
            return 'assets/images/icons/file-icon.png';
    }
}

// 📥 Fetch media with pagination
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
// 📂 Handle file selection and upload
const filesSelected = (files) => {
    attachments.value = files;

    const data = new FormData();
    files.forEach(file => data.append('files[]', file));

    uploadingFiles.value = files.map(file => ({
        name: file.name,
        size: file.size,
        progress: 0,
    }));

    preloader.value = true;

    Axios.post('/admin/ecommerce/upload-medias', data, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
                const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                uploadingFiles.value = uploadingFiles.value.map(f => ({
                    ...f,
                    progress: percent,
                }));
            }
        }
    })
        .then(() => fetchMedia(1))
        .finally(() => {
            preloader.value = false;
            setTimeout(() => uploadingFiles.value = [], 1000);
        });
};

const handleHeaderUpload = (filesEvent) => {
    const files = filesEvent.target.files;
    if (!files || !files.length) return;

    // ✅ If folder is selected → upload INTO folder
    if (selectedFolder.value) {
        folderFilesSelected(Array.from(files));
    }
    // ✅ Otherwise → upload to root / all media
    else {
        filesSelected(Array.from(files));
    }

    // Reset input so selecting same files again works
    filesEvent.target.value = '';
};
// 📤 Save/upload files
const save = (url, data) => {
    preloader.value = true;
    Axios.post(url, data, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(() => fetchMedia(1))
        .catch(({ response }) => console.error('Upload error:', response))
        .finally(() => preloader.value = false);
}

// ❌ Remove attachment from selection
const removeAttachments = (id) => {
    formData.value.images = formData.value.images.filter(att => att.id !== id);
    deletableAttachmentIds.value.push(id);
}
// delete
const toggleSelectMedia = (id, checked) => {
    if (checked) {
        selectedMediaIds.value.add(id);
    } else {
        selectedMediaIds.value.delete(id);
    }
    selectedMediaIds.value = new Set(selectedMediaIds.value);
};

const toggleSelectFolderMedia = (id, checked) => {
    if (checked) {
        selectedFolderMediaIds.value.add(id);
    } else {
        selectedFolderMediaIds.value.delete(id);
    }
    selectedFolderMediaIds.value = new Set(selectedFolderMediaIds.value);
};
// ⬇ Load more media
const loadMore = () => {
    if (currentPage.value < lastPage.value) {
        fetchMedia(currentPage.value + 1);
    }
}
// breadcrumbs
const folderBreadcrumbs = computed(() => {
    if (!selectedFolder.value) return [];

    const trail = [];
    let current = selectedFolder.value;

    while (current) {
        trail.unshift(current);
        current = mediaFolders.value.find(
            f => f.id === current.parent_id
        );
    }

    return trail;
});
// back button
const goBack = () => {
    if (!selectedFolder.value) return;

    if (selectedFolder.value.parent_id) {
        const parent = mediaFolders.value.find(
            f => f.id === selectedFolder.value.parent_id
        );
        if (parent) {
            openFolderPanel(parent);
        }
    } else {
        selectedFolder.value = null;
    }
};


const emitSelectedMedia = () => {
    const selectedUrls = mediaList.value
        .filter(media => selectedMediaIds.value.has(media.id))
        .map(media => media.url);

    // ✅ single mode hole string, multi mode hole array
    const result = props.selectionMode === 'single' ? selectedUrls[0] : selectedUrls;
    emit('selected', result);
    emit('close');
};


const emitSelectedFolderMedia = () => {
    const selectedUrls = folderMediaList.value
        .filter(media => selectedFolderMediaIds.value.has(media.id))
        .map(media => media.url);

    const result = props.selectionMode === 'single' ? selectedUrls[0] : selectedUrls;
    emit('selected', result);
    emit('close');
};
const handleKeyDown = (e) => {
    if (e.key === 'Control' || e.key === 'Meta') {
        ctrlPressed.value = true;
    }
};

const handleKeyUp = (e) => {
    if (e.key === 'Control' || e.key === 'Meta') {
        ctrlPressed.value = false;

        // ✅ Normal media list
        if (!selectedFolder.value && selectedMediaIds.value.size > 0) {
            emitSelectedMedia();
        }

        // ✅ Folder media
        if (selectedFolder.value && selectedFolderMediaIds.value.size > 0) {
            emitSelectedFolderMedia();
        }
    }
};


onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', handleKeyUp);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('keyup', handleKeyUp);
});
const handleClickSelectMedia = (media, event) => {
    if (event.target.type === 'checkbox') return;
    const isMultiSelect = event.ctrlKey || event.metaKey;
    if (isMultiSelect) {
        if (selectedMediaIds.value.has(media.id)) {
            selectedMediaIds.value.delete(media.id);
        } else {
            selectedMediaIds.value.add(media.id);
        }
        selectedMediaIds.value = new Set(selectedMediaIds.value);
    } else {
        selectedMediaIds.value = new Set([media.id]);
        emitSelectedMedia();
    }
};

const handleClickSelectFolderMedia = (media, event) => {
    if (event.target.type === 'checkbox') return;
    const isMultiSelect = event.ctrlKey || event.metaKey;
    if (isMultiSelect) {
        if (selectedFolderMediaIds.value.has(media.id)) {
            selectedFolderMediaIds.value.delete(media.id);
        } else {
            selectedFolderMediaIds.value.add(media.id);
        }
        selectedFolderMediaIds.value = new Set(selectedFolderMediaIds.value);
    } else {
        selectedFolderMediaIds.value = new Set([media.id]);
        emitSelectedFolderMedia();
    }
};
watch(selectedFolder, () => {
    folderFormData.value.images = [];
    folderAttachments.value = [];
    folderErrors.value = {};
});
watch(mediaList, () => {
    selectedMediaIds.value.clear();
});

watch(folderMediaList, () => {
    selectedFolderMediaIds.value.clear();
});

watch(selectedFolder, () => {
    selectedFolderMediaIds.value.clear();
    selectedMediaIds.value.clear();
});
const closeNewModal = () => {
    let modal = Modal.getInstance(document.getElementById(props.modalId))
    modal.hide()
    emit('close')
}
// ✅ Fetch media on mount
onMounted(() => {
    fetchMedia(1);
    fetchFolders();
});
</script>
<template>
    <app-modal :modal-id="modalId" modal-size="extra-large" title="Media Library" :preloader="preloader"
        @close="closeNewModal">
        <template v-slot:body>
            <div class="content-wrapper">
                <app-breadcrumb :page-title="$t('media_library')" />
                <!-- Breadcrumbs + Back -->
                <div class="d-flex align-items-center gap-2 mb-3">

                    <!-- Back Button -->
                    <button v-if="selectedFolder" class="btn btn-sm btn-outline-secondary" @click="goBack">
                        ← Back
                    </button>

                    <!-- Breadcrumb -->
                    <nav class="breadcrumb mb-0">
                        <span class="breadcrumb-item text-primary" style="cursor:pointer"
                            @click="selectedFolder = null">
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
                        <!-- Header: Upload -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="search-input flex-shrink-0 mb-4">
                                <span class="search-icon">
                                    <Search />
                                </span>
                                <input v-model="searchQuery" @input="onSearchInput" :placeholder="$t('search')"
                                    type="text" name="search" autocomplete="off"
                                    class="form-control form-control-sm bg-card border-transparent rounded-default" />
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bulk-actions">
                                    <button class="btn btn-danger" @click="showBulkDeleteModal(
                                        selectedFolder
                                            ? folderMediaList.map(m => m.id)
                                            : mediaList.map(m => m.id)
                                    )" :disabled="(selectedFolder ? folderMediaList.length : mediaList.length) === 0">
                                        Delete All ({{ selectedFolder ? folderMediaList.length : mediaList.length }})
                                    </button>
                                </div>
                                <button class="btn btn-primary" @click="folderCreateName">
                                    {{ $t('create_folder') }}
                                </button>
                                <div v-if="canAccess('update_medias')">
                                    <button class="btn btn-primary" @click="$refs.headerFileInput.click()">Upload
                                        Files</button>
                                    <input type="file" multiple ref="headerFileInput" style="display: none;"
                                        @change="handleHeaderUpload" />
                                </div>
                                <div v-if="selectedFolder">
                                    <button class="btn btn-secondary" @click="selectedFolder = null">
                                        Back to All Media
                                    </button>
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


                        <!-- Media Grid -->
                        <div class="media-folders">
                            <template v-if="!selectedFolder && !searchQuery.trim()">
                                <h5>Folders</h5>
                                <ul class="media-folders-list">
                                    <li v-for="folder in mediaFolders.filter(folder => folder.parent_id === null && folder.id !== 1)"
                                        :key="folder.id" class="folder-item flex items-center gap-2"
                                        :class="{ 'selected': selectedFolder?.id === folder.id }"
                                        @click="openFolderPanel(folder)" style="cursor: pointer;">
                                        <div class="d-flex gap-2 flex-column">
                                            <Folder />
                                            <span>{{ folder.name }}</span>
                                        </div>
                                    </li>
                                </ul>
                            </template>
                            <div v-if="selectedFolder" class="folder-media-panel mt-4 p-3 border rounded">
                                <div class="mb-4">
                                    <div v-if="subFolders.length" class="mb-4">

                                        <ul class="media-folders-list">
                                            <li v-for="folder in subFolders" :key="folder.id"
                                                class="folder-item flex items-center gap-2"
                                                @click="openFolderPanel(folder)" style="cursor:pointer;">
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
                                                <img v-if="isImage(attachment.url)" :src="attachment.url"
                                                    alt="attachment"
                                                    class="height-80 border rounded-3 overflow-hidden" />

                                                <div v-else>
                                                    <a :href="attachment.url" target="_blank" class="d-flex align-items-center justify-content-center
                                        height-80 width-80 border rounded-3 bg-light">
                                                        <img :src="getFileIcon(attachment.url)" alt="file-icon"
                                                            class="height-80 border rounded-3 overflow-hidden" />
                                                    </a>
                                                </div>

                                                <div class="remove-media"
                                                    @click="removeFolderAttachments(attachment.id)">
                                                    <Xmark />
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- ── Search result: matching folders across all levels ── -->
                                <template v-if="searchQuery.trim() && filteredFolders !== null">
                                    <div v-if="filteredFolders.length" class="mb-3">
                                        <h6 class="text-muted mb-2">Folders matching "{{ searchQuery }}"</h6>
                                        <ul class="media-folders-list">
                                            <li v-for="folder in filteredFolders" :key="folder.id"
                                                class="folder-item flex items-center gap-2"
                                                @click="openFolderPanel(folder)" style="cursor: pointer;">
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

                                <!-- Folder media list -->
                                <div v-if="selectedFolderMediaIds.size"
                                    class="d-flex align-items-center gap-2 mb-3 p-2 bg-light rounded">
                                    <span class="text-muted">{{ selectedFolderMediaIds.size }} file(s)
                                        selected</span>
                                    <button class="btn btn-primary btn-sm" @click="emitSelectedFolderMedia">
                                        ✓ Use Selected ({{ selectedFolderMediaIds.size }})
                                    </button>
                                    <button class="btn btn-danger"
                                        @click="showBulkDeleteModal([...selectedFolderMediaIds])">
                                        Delete Selected ({{ selectedFolderMediaIds.size }})
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm"
                                        @click="selectedFolderMediaIds = new Set()">
                                        Clear
                                    </button>
                                </div>

                                <div class="media-library-content folder-media-content">

                                    <div v-for="media in folderMediaList" :key="media.id" class="media-item"
                                        :class="{ selected: selectedFolderMediaIds.has(media.id) }"
                                        @click.stop="handleClickSelectFolderMedia(media, $event)">

                                        <!-- Checkbox -->
                                        <div class="form-check job-check">
                                            <input class="form-check-input" type="checkbox" :value="media.id"
                                                @change="toggleSelectFolderMedia(media.id, $event.target.checked)"
                                                :checked="selectedFolderMediaIds.has(media.id)" @click.stop />
                                        </div>

                                        <!-- Dropdown -->
                                        <div class="dropdown image-dropdown">
                                            <button class="btn btn-light" type="button"
                                                :id="`folderDropdown-${media.id}`" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <MoreVert />
                                            </button>

                                            <ul class="dropdown-menu" :aria-labelledby="`folderDropdown-${media.id}`">
                                                <li>
                                                    <button class="dropdown-item" v-if="canAccess('view_medias')"
                                                        @click="viewImageInfo(media)">
                                                        View
                                                    </button>
                                                </li>

                                                <li>
                                                    <button class="dropdown-item"
                                                        v-if="canAccess('create_media_alt_text')"
                                                        @click="altTextCreate(media)">
                                                        Alt Text
                                                    </button>
                                                </li>

                                                <li>
                                                    <button class="dropdown-item" @click="copyUrl(media.url)">
                                                        Copy URL
                                                    </button>
                                                </li>

                                                <li>
                                                    <button class="dropdown-item text-danger"
                                                        v-if="canAccess('delete_medias')"
                                                        @click="deleteMedia(media.id)">
                                                        Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Image / File -->
                                        <div class="image-card">
                                            <img v-if="media.mime_type?.includes('image')" :src="media.url"
                                                alt="media" />

                                            <div v-else class="file-icon">
                                                <img :src="getFileIcon(media.url)" alt="icon" />
                                            </div>
                                        </div>

                                        <!-- Name -->
                                        <div class="media-item-info">
                                            {{ media.name }}
                                        </div>

                                    </div>

                                </div>
                                <button v-if="folderCurrentPage < folderLastPage" @click="loadMoreFolderMedia"
                                    :disabled="folderLoading" class="btn btn-primary mt-3">
                                    {{ folderLoading ? 'Loading...' : 'Load More' }}
                                </button>
                            </div>
                        </div>

                        <!-- ✅ All media bulk select bar -->
                        <div v-if="selectedMediaIds.size"
                            class="d-flex align-items-center gap-2 mb-3 p-2 bg-light rounded">
                            <span class="text-muted">{{ selectedMediaIds.size }} file(s) selected</span>
                            <button class="btn btn-primary btn-sm" @click="emitSelectedMedia">
                                ✓ Use Selected ({{ selectedMediaIds.size }})
                            </button>

                            <button class="btn btn-danger" @click="showBulkDeleteModal([...selectedMediaIds])">
                                Delete Selected ({{ selectedMediaIds.size }})
                            </button>

                            <button class="btn btn-outline-secondary btn-sm" @click="selectedMediaIds = new Set()">
                                Clear
                            </button>
                        </div>
                        <!-- all media list -->

                        <template v-if="!selectedFolder">
                            <div class="media-library-content">

                                <div v-for="media in mediaList" :key="media.id" class="media-item"
                                    :class="{ selected: selectedMediaIds.has(media.id) }"
                                    @click.stop="handleClickSelectMedia(media, $event)">
                                    <div class="form-check job-check">
                                        <input class="form-check-input" type="checkbox" :value="media.id"
                                            @change="toggleSelectMedia(media.id, $event.target.checked)"
                                            :checked="selectedMediaIds.has(media.id)" @click.stop />
                                    </div>

                                    <!-- Dropdown -->
                                    <div class="dropdown image-dropdown">
                                        <button class="btn btn-light" type="button" :id="`dropdownMenu-${media.id}`"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <MoreVert />
                                        </button>
                                        <ul class="dropdown-menu" :aria-labelledby="`dropdownMenu-${media.id}`">
                                            <!-- <li>
                                                    <a class="dropdown-item" :href="media.url" target="_blank">Preview</a>
                                                </li> -->
                                            <li>
                                                <button class="dropdown-item" v-if="canAccess('view_medias')"
                                                    @click="viewImageInfo(media)">View</button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" v-if="canAccess('create_media_alt_text')"
                                                    @click="altTextCreate(media)">Alt Text</button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" @click="copyUrl(media.url)">Copy
                                                    URL</button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger"
                                                    v-if="canAccess('delete_medias')"
                                                    @click="deleteMedia(media.id)">Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Image / File -->
                                    <div class="image-card">
                                        <img v-if="media.mime_type?.includes('image')" :src="media.url" alt="media" />
                                        <div v-else class="file-icon">
                                            <img :src="getFileIcon(media.url)" alt="icon" />
                                        </div>
                                    </div>
                                    <div class="media-item-info">{{ media.name }}</div>
                                </div>
                            </div>
                            <!-- Load More Button -->
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
                :media="selectedData" @close="closeModal" />
            <folder-create-modal v-if="isModalActive && modalType === 'folder-name-create'"
                :parent-folder="selectedData" modal-id="folder-create-modal" @submit="fetchFolders"
                @close="closeModal" />
            <media-info-modal v-if="isModalActive && modalType === 'view-image-info'" modal-id="view-image-info-modal"
                :media="selectedData" :showFooter="false" @copy-url="copyUrl" @close="closeModal" />

            <bulk-delete-modal :show="isBulkDeleteModalVisible" :selected-ids="bulkDeleteIds"
                @confirmed="onBulkDeleteConfirmed" @cancelled="isBulkDeleteModalVisible = false" />
            <app-delete-modal v-if="isDeleteModal" :selected-url="deleteUrl" @cancelled="cancelled"
                @confirmed="handleDeleteConfirmed" />

            <div v-if="uploadingFiles.length" class="upload-progress-wrapper">
                <h6 class="upload-progress-title">Uploading Files</h6>
                <ul class="upload-progress-list">
                    <li v-for="(file, index) in uploadingFiles" :key="index" class="upload-progress-item">
                        <div class="file-info">
                            <strong class="file-name">{{ file.name }}</strong>
                            <small class="file-size">{{ (file.size / 1024).toFixed(2) }} KB</small>
                        </div>
                        <div class="progress-bar-wrapper" role="progressbar" :aria-valuenow="file.progress"
                            aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar-fill" :style="{ width: file.progress + '%' }"></div>
                        </div>
                        <span class="progress-percent">{{ file.progress }}%</span>
                    </li>
                </ul>
            </div>

        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.media-item.selected {
    border: 2px solid #007bff;
    background-color: #e6f0ff;
}
</style>