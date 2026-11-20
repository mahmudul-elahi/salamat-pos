<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useToast } from "vue-toastification";
import Axios from "@/services/axios/index.js";
import { ELITES } from "@/services/endpoints/landlord.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import EliteModal from "./EliteModal.vue";
import { Trash } from "@iconoir/vue";

const { canAccess } = usePermission();

const toast = useToast();
const emitter = useEmitter();

const tableId = ref('elite-table');
const elites = ref([]);
const loading = ref(false);
const isModalActive = ref(false);
const selectedUrl = ref(null);

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const loadElites = () => {
    loading.value = true;
    Axios.get(ELITES, { params: { per_page: 100 } })
        .then(({ data }) => { elites.value = data.data ?? []; })
        .catch(() => toast.error('Something went wrong.'))
        .finally(() => { loading.value = false; });
};

const openAdd = () => {
    selectedUrl.value = null;
    isModalActive.value = true;
};

const openEdit = (elite) => {
    selectedUrl.value = `${ELITES}/${elite.id}`;
    isModalActive.value = true;
};

const closeModal = () => {
    isModalActive.value = false;
    selectedUrl.value = null;
};

const confirmDelete = (elite) => {
    isDeleteModal.value = true;
    deleteUrl.value = `${ELITES}/${elite.id}`;
};

onMounted(() => {
    loadElites();
    emitter.on(`reload-${tableId.value}`, loadElites);
});

onBeforeUnmount(() => {
    emitter.off(`reload-${tableId.value}`, loadElites);
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('elite')" />

        <div class="py-4 px-4">

            <div class="d-flex justify-content-end mb-4">
                <button v-if="canAccess('create_elites')" type="button" class="btn btn-primary px-4" @click="openAdd">
                    <i class="bi bi-plus me-1"></i> {{ $t('add_elite') }}
                </button>
            </div>

            <div v-if="loading" class="d-flex justify-content-center py-5">
                <app-loader />
            </div>

            <template v-else>
                <div v-if="elites.length === 0" class="text-center py-5 text-muted">
                    {{ $t('no_data_found') }}
                </div>

                <div class="row g-4">
                    <div v-for="elite in elites" :key="elite.id" class="col-md-6 col-lg-4">
                        <div class="elite-card rounded-4 overflow-hidden position-relative"
                            :style="elite.banner ? `background-image: url(${elite.banner})` : ''">
                            <div class="elite-card-overlay p-4 d-flex flex-column h-100">

                                <div class="d-flex justify-content-end align-items-start gap-3">
                                    <div class="text-end">
                                        <h4 class="text-white fw-bold mb-1">{{ elite.status_name }}</h4>
                                        <p v-if="elite.tagline" class="text-white-50 small mb-0">{{ elite.tagline }}</p>
                                    </div>
                                    <div v-if="elite.logo" class="elite-logo rounded-circle overflow-hidden flex-shrink-0">
                                        <img :src="elite.logo" alt="logo" class="w-100 h-100 object-fit-cover" />
                                    </div>
                                </div>

                                <div class="mt-auto d-flex justify-content-end gap-2 pt-3">
                                    <button v-if="canAccess('update_elites')" type="button"
                                        class="btn btn-light btn-sm px-4 rounded-pill"
                                        @click="openEdit(elite)">
                                        {{ $t('update') }}
                                    </button>
                                    <button v-if="canAccess('delete_elites')" type="button"
                                        class="btn btn-light btn-sm px-3 rounded-pill"
                                        @click="confirmDelete(elite)">
                                        <Trash width="16" height="16" />
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <elite-modal v-if="isModalActive" :table-id="tableId" modal-id="elite-modal"
        :selected-url="selectedUrl" @close="closeModal" />

    <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl"
        @cancelled="cancelled" />
</template>

<style scoped lang="scss">
.elite-card {
    min-height: 200px;
    background-color: #0f1f3d;
    background-size: cover;
    background-position: center;
    display: flex;
    flex-direction: column;
}

.elite-card-overlay {
    background: linear-gradient(135deg, rgba(10, 20, 50, 0.6) 0%, rgba(10, 20, 50, 0.3) 100%);
    min-height: 200px;
}

.elite-logo {
    width: 48px;
    height: 48px;
    border: 2px solid rgba(255, 255, 255, 0.4);
    background: rgba(255,255,255,0.1);
    flex-shrink: 0;
}
</style>
