<script setup>
import { ref, onMounted, computed } from "vue";
import { Edit } from "@iconoir/vue";
import { DIRECTORS, DIRECTOR_PROFILE_PDF_STATUS, DIRECTOR_PROFILE_PDF_CLEANUP } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import DepositTransactions from "@/views/landlord/pos/director/DepositTransactions.vue";
import WithdrawalTransactions from "@/views/landlord/pos/director/WithdrawalTransactions.vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import router from "@/router/index.js";
import usePermission from "@/composable/permission/usePermission.js";

const props = defineProps({ id: {} });
const toast = useToast();
const { canAccess } = usePermission();

const director = ref(null);
const pageLoader = ref(false);

// Shop balances state (one section per shop)
const shopBalances = ref([]);
const shopDateRanges = ref({});
const shopLoading = ref({});

const fetchDirector = async () => {
    try {
        pageLoader.value = true;
        const { data } = await Axios.get(`${DIRECTORS}/${props.id}`);
        director.value = data;
    } catch {
        toast.error('Failed to fetch director');
    } finally {
        pageLoader.value = false;
    }
};

const fetchShopBalances = async (shopIndex, dateRange = {}) => {
    shopLoading.value[shopIndex] = true;
    try {
        const { data } = await Axios.get(`${DIRECTORS}/${props.id}/shop-balances`, {
            params: { date_from: dateRange.from, date_to: dateRange.to }
        });
        shopBalances.value = data;
        shopBalances.value.forEach((_, i) => {
            if (!shopDateRanges.value[i]) shopDateRanges.value[i] = { from: '', to: '' };
        });
    } catch {
        // silently fail for design phase
    } finally {
        shopLoading.value[shopIndex] = false;
    }
};



const statistics = ref({ total_deposit: 0, total_withdraw: 0, total_balance: 0 });
const fetchDirectorStatistics = async () => {
    try {
        const { data } = await Axios.get(`${DIRECTORS}/${props.id}/statistics`);
        statistics.value.total_deposit = data.total_deposit;
        statistics.value.total_withdraw = data.total_withdraw;
        statistics.value.total_balance = data.total_balance;

    } catch {
        // silently fail for design phase
    }
};

const pdfGenerating = ref(false);
const shopPdfGenerating = ref({});
const POLL_INTERVAL_MS = 2000;
const MAX_POLL_ATTEMPTS = 150; // ~5 minutes

const pollPdfStatus = (token, filename, setGenerating, attempt = 0) => {
    if (attempt >= MAX_POLL_ATTEMPTS) {
        toast.error('Report generation is taking too long. Please try again later.');
        setGenerating(false);
        return;
    }

    setTimeout(async () => {
        try {
            const { data } = await Axios.get(`${DIRECTOR_PROFILE_PDF_STATUS}/${token}`);

            if (data.status === 'completed') {
                const fileResponse = await Axios.get(data.url, { responseType: 'blob' });
                const blobUrl = URL.createObjectURL(new Blob([fileResponse.data], { type: 'application/pdf' }));
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(blobUrl);
                setGenerating(false);

                Axios.delete(`${DIRECTOR_PROFILE_PDF_CLEANUP}/${token}`).catch(() => { });
            } else if (data.status === 'failed') {
                toast.error(data.message || 'Something went wrong');
                setGenerating(false);
            } else {
                pollPdfStatus(token, filename, setGenerating, attempt + 1);
            }
        } catch (error) {
            toast.error(error?.response?.data?.message || 'Something went wrong');
            setGenerating(false);
        }
    }, POLL_INTERVAL_MS);
};

const downloadDirectorProfilePdf = async () => {
    try {
        pdfGenerating.value = true;
        const { data } = await Axios.post(`${DIRECTORS}/${props.id}/profile-pdf`);
        pollPdfStatus(data.token, `director-profile-${props.id}.pdf`, (v) => { pdfGenerating.value = v; });
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Something went wrong');
        pdfGenerating.value = false;
    }
};

const downloadShopProfilePdf = async (shopIndex, shop) => {
    try {
        shopPdfGenerating.value[shopIndex] = true;
        const { data } = await Axios.post(`${DIRECTORS}/${props.id}/shop-balances/${shop.shop_id}/profile-pdf`);
        pollPdfStatus(
            data.token,
            `director-profile-${props.id}-${shop.shop_name}.pdf`,
            (v) => { shopPdfGenerating.value[shopIndex] = v; },
        );
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Something went wrong');
        shopPdfGenerating.value[shopIndex] = false;
    }
};

onMounted(async () => {
    await fetchDirector();
    fetchDirectorStatistics();
    fetchShopBalances(0);
});
</script>

<template>
    <div class="content-wrapper">

        <div class="d-flex align-items-center gap-2 mb-4">
            <span class="page-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                </svg>
            </span>
            <h5 class="mb-0 fw-bold text-uppercase" style="letter-spacing:1px;">CREATE PROFILES</h5>
        </div>

        <app-loader v-if="pageLoader" />

        <template v-else-if="director">

            <!-- ── Profile Card ── -->
            <div class="profile-card mb-4">
                <div class="d-flex w-100">

                    <!-- Avatar + Info -->
                    <div class="d-flex align-items-start gap-4 flex-grow-1">
                        <div class="profile-avatar-wrap">
                            <img v-if="director.profile_picture || director.profile_photo"
                                :src="director.profile_picture || director.profile_photo" :alt="director.name"
                                class="profile-avatar" />
                            <div v-else class="profile-avatar-placeholder">
                                {{ director.name?.charAt(0)?.toUpperCase() }}
                            </div>
                        </div>
                        <div class="profile-details">
                            <div class="profile-name">{{ director.name }}</div>
                            <div class="profile-title mb-2">{{ director.title ?? '—' }}</div>
                            <div v-if="director.phone" class="profile-contact">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" class="me-2">
                                    <path
                                        d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 10.8a19.79 19.79 0 01-3.07-8.7A2 2 0 012 0h3a2 2 0 012 1.72c.12.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.34 1.85.58 2.81.7A2 2 0 0122 16.92z" />
                                </svg>
                                {{ director.phone }}
                            </div>
                            <div v-if="director.email" class="profile-contact">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" class="me-2">
                                    <path
                                        d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                    <polyline points="22,6 12,13 2,6" />
                                </svg>
                                {{ director.email }}
                            </div>
                            <div v-if="director.username || director.name" class="profile-username mt-1">
                                <strong>user name:</strong> {{ director.username ??
                                    director.name?.toLowerCase().replace(/\s+/g, '') }}
                            </div>
                        </div>
                    </div>

                    <!-- Financial Stats -->
                    <div class="profile-fin-section">
                        <!-- Deposit | Withdraw -->
                        <div class="d-flex align-items-start mb-3">
                            <div class="fin-col text-end pe-4">
                                <div class="fin-label">Total Deposit</div>
                                <div class="fin-amount">{{ numberWithCurrencySymbol(statistics.total_deposit ?? 0) }}
                                </div>
                            </div>
                            <div class="fin-vr"></div>
                            <div class="fin-col ps-4">
                                <div class="fin-label">Total Withdraw</div>
                                <div class="fin-amount">{{ numberWithCurrencySymbol(statistics.total_withdraw ?? 0) }}
                                </div>
                            </div>
                        </div>
                        <!-- Balance + Edit -->
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <div class="text-end">
                                <div class="balance-hint">(deposit - withdrawal = balance) Total Balance</div>
                                <div class="fin-amount">{{ numberWithCurrencySymbol(statistics.total_balance) }}</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button v-if="canAccess('generate_director_profile_pdf')"
                                class="btn btn-outline-secondary btn-sm edit-btn" :disabled="pdfGenerating"
                                @click="downloadDirectorProfilePdf">
                                {{ pdfGenerating ? 'Generating...' : 'Download PDF' }}
                            </button>
                            <button class="btn btn-outline-secondary btn-sm edit-btn"
                                @click="router.push(`/edit-directors/${director.id}`)">
                                Edit Profile &nbsp;
                                <Edit width="13" height="13" />
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── Shop Balance Sections ── -->
            <div v-for="(shop, index) in shopBalances" :key="index" class="section-card mb-4">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-shop-name">
                        Shop : <strong>{{ shop.shop_name }}</strong>
                    </div>
                    <button v-if="canAccess('generate_director_shop_profile_pdf')"
                        class="btn btn-outline-secondary btn-sm edit-btn" :disabled="shopPdfGenerating[index]"
                        @click="downloadShopProfilePdf(index, shop)">
                        {{ shopPdfGenerating[index] ? 'Generating...' : 'Download PDF' }}
                    </button>
                </div>

                <!-- <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="date-range-wrap">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2"
                            class="date-icon">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>

                    </div>


                    <button class="btn btn-outline-secondary btn-sm search-btn">
                        Search
                    </button>
                </div> -->

                <div class="custom-table">
                    <div class="table-header-row">
                        <div class="th">Shop Name</div>
                        <div class="th">Total Deposit</div>
                        <div class="th">Total Withdraw</div>
                        <div class="th">Total Balance<br>
                            <small class="text-muted fw-normal">(Deposit - Withdrawal = Balance)</small>
                        </div>
                    </div>
                    <div class="table-data-row">
                        <div class="td">{{ shop.shop_name }}</div>
                        <div class="td">{{ numberWithCurrencySymbol(shop.total_deposit ?? 0) }}</div>
                        <div class="td">{{ numberWithCurrencySymbol(shop.total_withdraw ?? 0) }}</div>
                        <div class="td">{{ numberWithCurrencySymbol((parseFloat(shop.total_deposit ?? 0) -
                            parseFloat(shop.total_withdraw ?? 0))) }}</div>
                    </div>
                </div>
            </div>


            <!-- ── Deposit Transactions ── -->

            <deposit-transactions :directorId="props.id"></deposit-transactions>

            <!-- ── Withdrawal Transactions ── -->

            <withdrawal-transactions :directorId="props.id"></withdrawal-transactions>
        </template>
    </div>
</template>

<style scoped lang="scss">
/* ── Profile Card ── */
.profile-card {
    background: #fff;
    border-radius: 12px;
    padding: 28px 32px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.profile-avatar-wrap {
    flex-shrink: 0;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e0e0e0;
}

.profile-avatar-placeholder {
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

.profile-name {
    font-size: 26px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.2;
    margin-bottom: 2px;
}

.profile-title {
    font-size: 14px;
    color: #888;
}

.profile-contact {
    font-size: 13px;
    color: #444;
    display: flex;
    align-items: center;
    margin-bottom: 3px;
}

.profile-username {
    font-size: 13px;
    color: #444;
}

/* ── Financial section (right of profile card) ── */
.profile-fin-section {
    flex-shrink: 0;
    min-width: 460px;
}

.fin-col {
    min-width: 190px;
}

.fin-vr {
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

.edit-btn {
    font-size: 13px;
    color: #555;
    border-color: #ccc;
    white-space: nowrap;

    &:hover {
        background: #f5f5f5;
    }
}

/* ── Section Cards (shop balance / deposit) ── */
.section-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px 28px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.section-title {
    font-size: 17px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 4px;
}

.section-shop-name {
    font-size: 14px;
    color: #555;
}

/* ── Date Range ── */
.date-range-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 6px 12px;
    background: #fff;
    min-width: 280px;
}

.date-icon {
    flex-shrink: 0;
}

.date-input {
    border: none;
    outline: none;
    font-size: 13px;
    color: #333;
    width: 130px;
    background: transparent;
}

.date-sep {
    color: #aaa;
    font-size: 13px;
}

.date-placeholder {
    font-size: 13px;
    color: #aaa;
    flex: 1;
}

.search-btn {
    padding: 6px 20px;
    font-size: 13px;
}

/* ── Custom Table ── */
.custom-table {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #f0f0f0;
}

.table-header-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    background: #f5f5f5;
    padding: 10px 16px;
    gap: 8px;

    &.deposit-header {
        grid-template-columns: 1.5fr 1.5fr 1fr 2fr 1fr;
    }
}

.th {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    line-height: 1.3;
}

.table-data-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    padding: 10px 16px;
    gap: 8px;
    border-top: 1px solid #f0f0f0;

    &.deposit-row {
        grid-template-columns: 1.5fr 1.5fr 1fr 2fr 1fr;
    }
}

.td {
    font-size: 13px;
    color: #333;
}

.table-empty-row {
    padding: 16px;
    text-align: center;
    color: #aaa;
    font-size: 13px;
    border-top: 1px solid #f0f0f0;
}
</style>
