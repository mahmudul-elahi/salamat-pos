<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Modal } from 'bootstrap';
import { NavArrowLeft, Check, Xmark, Printer } from '@iconoir/vue';
import { CASH_TRANSFERS } from '@/services/endpoints/landlord.js';
import { numberWithCurrencySymbol } from '@/utils/helper.js';
import Axios from '@/services/axios/index.js';
import { useToast } from 'vue-toastification';
import router from '@/router/index.js';
import usePermission from '@/composable/permission/usePermission.js';

const { canAccess } = usePermission();

const props = defineProps({ id: {} });
const toast = useToast();

const transfer = ref(null);
const loading = ref(false);
const actionLoading = ref(false);

const confirmModalActive = ref(false);
const confirmType = ref(null);
let modalInstance = null;

const confirmConfig = {
    approve: {
        icon: Check,
        iconClass: 'text-success',
        title: 'Approve Cash Transfer?',
        message: 'This will mark the cash transfer as approved. This action cannot be undone.',
        btnClass: 'btn-success',
        btnLabel: 'Approve',
    },
    cancel: {
        icon: Xmark,
        iconClass: 'text-danger',
        title: 'Cancel Transfer?',
        message: 'This transfer will be marked as cancelled.',
        btnClass: 'btn-danger',
        btnLabel: 'Cancel Transfer',
    },
};

const openConfirm = async (type) => {
    confirmType.value = type;
    confirmModalActive.value = true;
    await nextTick();
    const el = document.getElementById('ct-confirm-modal');
    if (el) {
        modalInstance = new Modal(el);
        modalInstance.show();
        el.addEventListener('hidden.bs.modal', closeConfirm, { once: true });
    }
};

const closeConfirm = () => {
    // confirmModalActive drives a v-if in this same component, so it tears
    // the modal's DOM down on this same tick — calling hide() here would
    // just start an async fade transition that gets cut off immediately,
    // leaving Bootstrap's transitionend-based body cleanup ('modal-open'
    // class + inline overflow/padding) stuck forever and the whole app
    // unscrollable. Force that cleanup synchronously instead, excluding
    // this modal's own element from the "is another modal open" check
    // since it may still carry the 'show' class at this exact instant.
    const el = document.getElementById('ct-confirm-modal');
    confirmModalActive.value = false;
    confirmType.value = null;
    if (modalInstance) { modalInstance.dispose(); modalInstance = null; }

    const anotherModalOpen = Array.from(document.querySelectorAll('.modal.show')).some((modalEl) => modalEl !== el);
    if (!anotherModalOpen) {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
    }
};

const load = async () => {
    loading.value = true;
    try {
        const { data } = await Axios.get(`${CASH_TRANSFERS}/${props.id}`);
        transfer.value = data;
    } catch {
        toast.error('Failed to load transfer.');
    } finally {
        loading.value = false;
    }
};

onMounted(load);

const runAction = async () => {
    const type = confirmType.value;
    closeConfirm();
    actionLoading.value = true;
    try {
        await Axios.post(`${CASH_TRANSFERS}/${props.id}/${type}`);
        toast.success(type === 'approve' ? 'Cash transfer approved.' : 'Cash transfer cancelled.');
        await load();
    } catch (e) {
        toast.error(e.response?.data?.message ?? `Failed to ${type}.`);
    } finally {
        actionLoading.value = false;
    }
};

const statusStyle = (status) => {
    const map = {
        draft: { color: '#868e96', bg: '#f1f3f5' },
        approved: { color: '#2f9e44', bg: '#d3f9d8' },
        cancelled: { color: '#e03131', bg: '#fff0f0' },
    };
    return map[status] ?? map.draft;
};

// Mirrors ViewStockTransfer.vue's print approach: clone a hidden on-page
// template into a fresh window and call print() - no extra backend call
// needed since load() already returns everything the voucher needs. No
// item table here (cash transfer is a single amount, not a serial list),
// so the amount itself is the focal block instead.
const printTransfer = () => {
    const el = document.getElementById('cash-transfer-print');
    if (!el) return;

    const css = `
        @page { size: A4 portrait; margin: 16mm 14mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Georgia', 'Times New Roman', serif; color: #1f2937; }
        .cti-doc { font-family: Arial, Helvetica, sans-serif; font-size: 11.5px; color: #1f2937; line-height: 1.5; }
        .clearfix::after { content: ""; display: table; clear: both; }

        .cti-masthead { border-bottom: 3px solid #92400e; padding-bottom: 14px; margin-bottom: 20px; }
        .cti-kicker { font-family: 'Georgia', serif; font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: #92400e; font-weight: 700; margin-bottom: 6px; }
        .cti-title-row { display: flex; justify-content: space-between; align-items: flex-end; }
        .cti-ref { font-family: 'Georgia', serif; font-size: 26px; font-weight: 700; color: #0f172a; letter-spacing: 0.5px; }
        .cti-meta { text-align: right; }
        .cti-status { display: inline-block; padding: 4px 14px; border-radius: 3px; font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .cti-date { font-size: 11.5px; color: #475569; }

        .cti-route { width: 100%; margin: 24px 0 26px 0; display: table; table-layout: fixed; }
        .cti-route-card { display: table-cell; width: 44%; border: 1px solid #d6d3d1; border-radius: 4px; padding: 14px 16px; vertical-align: top; background: #fafaf9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .cti-route-card--to { background: #fffbeb; border-color: #fde68a; }
        .cti-route-arrow { display: table-cell; width: 12%; text-align: center; vertical-align: middle; font-size: 22px; color: #92400e; font-weight: 700; }
        .cti-route-label { font-size: 9.5px; letter-spacing: 2px; text-transform: uppercase; color: #92400e; font-weight: 700; margin-bottom: 6px; }
        .cti-route-shop { font-size: 15px; font-weight: 700; color: #0f172a; }

        .cti-amount-wrap { border: 1px solid #333; border-radius: 8px; padding: 26px; text-align: center; margin-bottom: 24px; }
        .cti-amount-label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #57534e; margin-bottom: 8px; }
        .cti-amount-value { font-size: 34px; font-weight: 700; color: #0f172a; }

        .cti-note { font-size: 11px; color: #57534e; margin-bottom: 24px; }

        .cti-signatures { width: 100%; display: table; margin-top: 46px; }
        .cti-sig { display: table-cell; width: 33.33%; text-align: center; padding: 0 10px; }
        .cti-sig-line { border-top: 1px solid #57534e; margin-bottom: 6px; padding-top: 6px; font-size: 11.5px; font-weight: 700; color: #0f172a; }
        .cti-sig-role { font-size: 9.5px; letter-spacing: 1px; text-transform: uppercase; color: #92400e; }

        .cti-footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e7e5e4; text-align: center; font-size: 10px; color: #a8a29e; font-style: italic; }
    `;

    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Cash Transfer ${transfer.value?.reference_no || ''}</title>
<style>${css}</style>
</head>
<body>
${el.innerHTML}
</body>
</html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 400);
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Cash Transfer Detail" />
        <app-loader v-if="loading" />

        <template v-else-if="transfer">
            <div class="card mb-3">
                <div class="card-body">

                    <!-- Top bar -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-light btn-sm d-flex align-items-center gap-1"
                                @click="router.push({ name: 'cashTransfers' })">
                                <NavArrowLeft style="width:15px;height:15px;" /> Back
                            </button>
                            <div>
                                <span style="font-size:1rem;font-weight:700;color:#212529;">
                                    {{ transfer.reference_no }}
                                </span>
                                <span
                                    style="display:inline-block;margin-left:10px;padding:2px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;"
                                    :style="{ background: statusStyle(transfer.status).bg, color: statusStyle(transfer.status).color }">
                                    {{ transfer.status.charAt(0).toUpperCase() + transfer.status.slice(1) }}
                                </span>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="background:#fff7ed;border:1px solid #fed7aa;color:#92400e;font-weight:600;"
                                @click="printTransfer">
                                <Printer style="width:15px;height:15px;" />
                                Print
                            </button>
                            <template v-if="transfer.status === 'draft' && (canAccess('approve_cash_transfers') || canAccess('cancel_cash_transfers'))">
                            <button v-if="canAccess('approve_cash_transfers')"
                                class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="background:#d3f9d8;border:1px solid #8ce99a;color:#2f9e44;font-weight:600;"
                                :disabled="actionLoading"
                                @click="openConfirm('approve')">
                                <span v-if="actionLoading" class="spinner-border spinner-border-sm"></span>
                                <Check v-else style="width:15px;height:15px;" />
                                Approve
                            </button>
                            <button v-if="canAccess('cancel_cash_transfers')"
                                class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="background:#fff0f0;border:1px solid #ffd0d0;color:#e03131;font-weight:600;"
                                :disabled="actionLoading"
                                @click="openConfirm('cancel')">
                                <span v-if="actionLoading" class="spinner-border spinner-border-sm"></span>
                                <Xmark v-else style="width:15px;height:15px;" />
                                Cancel
                            </button>
                            </template>
                        </div>
                    </div>

                    <!-- Amount highlight -->
                    <div class="ct-amount-banner mb-4">
                        <div class="ct-amount-banner__label">Transfer Amount</div>
                        <div class="ct-amount-banner__value">{{ numberWithCurrencySymbol(transfer.amount) }}</div>
                    </div>

                    <!-- Info cards -->
                    <div class="row g-3 mb-2">
                        <div class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">From Shop</div>
                                <div class="info-value">{{ transfer.from_tenant?.name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">To Shop</div>
                                <div class="info-value">{{ transfer.to_tenant?.name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">Transfer Date</div>
                                <div class="info-value">{{ transfer.transfer_date }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">Created At</div>
                                <div class="info-value" style="font-size:0.82rem;">{{ transfer.created_at }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">Created By</div>
                                <div class="info-value">{{ transfer.created_by ?? '-' }}</div>
                            </div>
                        </div>
                        <div v-if="transfer.approved_by" class="col-6 col-md-2">
                            <div class="info-card info-card--approved">
                                <div class="info-label" style="color:#2f9e44;">Approved By</div>
                                <div class="info-value" style="color:#2f9e44;">{{ transfer.approved_by }}</div>
                            </div>
                        </div>
                        <div v-if="transfer.cancelled_by" class="col-6 col-md-2">
                            <div class="info-card info-card--cancelled">
                                <div class="info-label" style="color:#e03131;">Cancelled By</div>
                                <div class="info-value" style="color:#e03131;">{{ transfer.cancelled_by }}</div>
                            </div>
                        </div>
                        <div v-if="transfer.note" class="col-12 col-md-4">
                            <div class="info-card">
                                <div class="info-label">Note</div>
                                <div class="info-value" style="font-weight:400;font-size:0.85rem;">{{ transfer.note }}</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Hidden premium print template - cloned into a fresh window by
                 printTransfer() rather than rendered on screen. -->
            <div id="cash-transfer-print" style="display:none">
                <div class="cti-doc">
                    <div class="cti-masthead clearfix">
                        <div class="cti-title-row">
                            <div>
                                <div class="cti-kicker">Cash Transfer Voucher</div>
                                <div class="cti-ref">{{ transfer.reference_no }}</div>
                            </div>
                            <div class="cti-meta">
                                <div class="cti-status"
                                    :style="{ background: statusStyle(transfer.status).bg, color: statusStyle(transfer.status).color }">
                                    {{ transfer.status.charAt(0).toUpperCase() + transfer.status.slice(1) }}
                                </div>
                                <div class="cti-date">Transfer Date: {{ transfer.transfer_date }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="cti-route">
                        <div class="cti-route-card">
                            <div class="cti-route-label">From</div>
                            <div class="cti-route-shop">{{ transfer.from_tenant?.name ?? '-' }}</div>
                        </div>
                        <div class="cti-route-arrow">&rarr;</div>
                        <div class="cti-route-card cti-route-card--to">
                            <div class="cti-route-label">To</div>
                            <div class="cti-route-shop">{{ transfer.to_tenant?.name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="cti-amount-wrap">
                        <div class="cti-amount-label">Transfer Amount</div>
                        <div class="cti-amount-value">{{ numberWithCurrencySymbol(transfer.amount) }}</div>
                    </div>

                    <div v-if="transfer.note" class="cti-note"><strong>Note:</strong> {{ transfer.note }}</div>

                    <div class="cti-signatures">
                        <div class="cti-sig">
                            <div class="cti-sig-line">{{ transfer.created_by ?? '-' }}</div>
                            <div class="cti-sig-role">Prepared By</div>
                        </div>
                        <div class="cti-sig">
                            <div class="cti-sig-line">{{ transfer.approved_by ?? ' ' }}</div>
                            <div class="cti-sig-role">Approved By</div>
                        </div>
                        <div class="cti-sig">
                            <div class="cti-sig-line">&nbsp;</div>
                            <div class="cti-sig-role">Received By</div>
                        </div>
                    </div>

                    <div class="cti-footer">This is a system-generated cash transfer voucher.</div>
                </div>
            </div>
        </template>
    </div>

    <!-- Confirm Modal -->
    <div v-if="confirmModalActive" class="modal fade" id="ct-confirm-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" v-if="confirmType">
                <div class="modal-body">
                    <component :is="confirmConfig[confirmType].icon"
                        :class="[confirmConfig[confirmType].iconClass, 'display-3']"
                        style="width:56px;height:56px;" />
                    <br>
                    <h4 class="fw-bold mt-3">{{ confirmConfig[confirmType].title }}</h4>
                    <p class="text-muted">{{ confirmConfig[confirmType].message }}</p>

                    <div class="border-top my-3"></div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button class="btn btn-primary-danger-outline" @click="closeConfirm">
                            {{ $t('cancel') }}
                        </button>
                        <button :class="['btn', 'btn-sm', 'px-4', confirmConfig[confirmType].btnClass]"
                            @click="runAction">
                            {{ confirmConfig[confirmType].btnLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ct-amount-banner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #86efac;
    text-align: center;
}

.ct-amount-banner__label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #16a34a;
    margin-bottom: 4px;
}

.ct-amount-banner__value {
    font-size: 2rem;
    font-weight: 800;
    color: #15803d;
}

.info-card {
    padding: 12px;
    border-radius: 8px;
    background: var(--base-color);
    border: 1px solid var(--border-color);
}

.info-card--approved {
    background: #d3f9d8;
    border-color: #8ce99a;
}

.info-card--cancelled {
    background: #fff0f0;
    border-color: #ffd0d0;
}

.info-label {
    font-size: 0.72rem;
    color: var(--table-th-font-color);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--font-color);
    margin-top: 4px;
}
</style>
