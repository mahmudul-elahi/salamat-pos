<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import { DIRECTORS } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import { Edit } from "@iconoir/vue";
import router from "@/router/index.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const toast = useToast();

const directors = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);


const fetchDirectors = async (page = 1) => {
    try {
        loading.value = true;
        const { data } = await Axios.get(DIRECTORS);
        directors.value = data;
    } catch {
        toast.error(t('something_went_wrong'));
    } finally {
        loading.value = false;
    }
};


const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

onMounted(() => fetchDirectors());
</script>

<template>
    <div class="content-wrapper">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="page-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                    </svg>
                </span>
                <h5 class="mb-0 fw-bold text-uppercase" style="letter-spacing:1px;">DIRECTOR PROFILES</h5>
            </div>
            <button v-if="canAccess('create_news')" class="btn btn-success btn-sm px-4"
                @click="router.push('/create-directors')">
                Creat Profile +
            </button>
        </div>

        <div v-if="loading" class="py-5 text-center">
            <app-loader />
        </div>

        <div v-else class="director-list">
            <div v-for="director in directors" :key="director.id" class="director-card mb-3">
                <div class="d-flex align-items-center w-100">

                    <!-- Profile Photo -->
                    <div class="director-avatar-wrap me-4">
                        <img v-if="director?.profile_photo" :src="director?.profile_photo" :alt="director?.name"
                            class="director-avatar" />
                        <div v-else class="director-avatar-placeholder">
                            {{ director?.name }}
                        </div>
                    </div>

                    <!-- Director Info -->
                    <div class="director-info me-auto">

                        <div class="director-name">{{ director?.name ?? '—' }}</div>
                        <div class="director-title mb-3">{{ director?.title ?? '—' }}</div>
                        <button class="btn-view" @click="router.push(`/director-profile/${director.id}`)">
                            View &nbsp;›
                        </button>
                    </div>

                    <!-- Financial Stats -->
                    <div class="financial-section">
                        <!-- Row 1: Withdraw | Deposit -->
                        <div class="d-flex align-items-start mb-2">
                            <div class="fin-col text-end pe-4">
                                <div class="fin-label">Total Withdraw</div>
                                <div class="fin-amount">{{ numberWithCurrencySymbol(director.total_withdraw ?? 0) }}
                                </div>
                            </div>
                            <div class="fin-divider"></div>
                            <div class="fin-col ps-4">
                                <div class="fin-label">Total Deposit</div>
                                <div class="fin-amount">{{ numberWithCurrencySymbol(director.total_deposit ?? 0) }}
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Balance + Edit -->
                        <div class="d-flex align-items-center justify-content-end gap-4">
                            <div class="text-end">
                                <div class="balance-hint">(deposit - withdrawal = balance) Total Balance</div>
                                <div class="fin-amount">{{ numberWithCurrencySymbol(director.total_balance) }}
                                </div>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm edit-profile-btn"
                                @click="router.push(`/edit-directors/${director.id}`)">
                                Edit Profile &nbsp;
                                <Edit width="13" height="13" />
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div v-if="directors.length === 0 && !loading" class="text-center py-5 text-muted">
                {{ $t('no_data_found') }}
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="d-flex justify-content-end mt-3">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                        <button class="page-link" @click="goToPage(currentPage - 1)">&laquo;</button>
                    </li>
                    <li v-for="p in lastPage" :key="p" class="page-item" :class="{ active: p === currentPage }">
                        <button class="page-link" @click="goToPage(p)">{{ p }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage === lastPage }">
                        <button class="page-link" @click="goToPage(currentPage + 1)">&raquo;</button>
                    </li>
                </ul>
            </nav>
        </div>

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss">
.director-list {
    display: flex;
    flex-direction: column;
}

.director-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 28px 32px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.director-avatar-wrap {
    flex-shrink: 0;
}

.director-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e0e0e0;
}

.director-avatar-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    font-size: 36px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #e0e0e0;
}

.director-info {
    min-width: 200px;
}

.company-name {
    font-size: 13px;
    color: #555;
    font-weight: 500;
}

.director-name {
    font-size: 26px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.2;
    margin-bottom: 2px;
}

.director-title {
    font-size: 14px;
    color: #888;
}

.btn-view {
    background: #4caf50;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 6px 22px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;

    &:hover {
        background: #43a047;
    }
}

.financial-section {
    flex-shrink: 0;
    min-width: 480px;
}

.fin-col {
    min-width: 200px;
}

.fin-divider {
    width: 1px;
    background: #dee2e6;
    align-self: stretch;
    min-height: 50px;
}

.fin-label {
    font-size: 13px;
    color: #555;
    margin-bottom: 2px;
}

.fin-amount {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a1a;
    white-space: nowrap;
}

.balance-hint {
    font-size: 11px;
    color: #999;
    margin-bottom: 1px;
}

.edit-profile-btn {
    white-space: nowrap;
    font-size: 13px;
    color: #555;
    border-color: #ccc;
    flex-shrink: 0;

    &:hover {
        background: #f5f5f5;
    }
}
</style>
