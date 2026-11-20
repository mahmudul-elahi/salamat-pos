<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Modal } from 'bootstrap';
import { NavArrowLeft, Check, Xmark, Printer } from '@iconoir/vue';
import { STOCK_TRANSFERS } from '@/services/endpoints/landlord.js';
import Axios from '@/services/axios/index.js';
import { useToast } from 'vue-toastification';
import router from '@/router/index.js';
import usePermission from "@/composable/permission/usePermission.js";

const { canAccess } = usePermission();

const props = defineProps({ id: {} });
const toast = useToast();

const transfer = ref(null);
const loading = ref(false);
const actionLoading = ref(false);

// Confirm modal state
const confirmModalActive = ref(false);
const confirmType = ref(null); // 'approve' | 'cancel'
let modalInstance = null;

const confirmConfig = {
    approve: {
        icon: Check,
        iconClass: 'text-success',
        title: 'Approve Transfer?',
        message: 'Serials will be moved to the destination shop immediately. This cannot be undone.',
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
    const el = document.getElementById('st-confirm-modal');
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
    const el = document.getElementById('st-confirm-modal');
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
        const { data } = await Axios.get(`${STOCK_TRANSFERS}/${props.id}`);
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
        await Axios.post(`${STOCK_TRANSFERS}/${props.id}/${type}`);
        toast.success(type === 'approve' ? 'Transfer approved. Serials moved successfully.' : 'Transfer cancelled.');
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

const hasUnavailable = () =>
    transfer.value?.items?.some(i => i.is_available === false) ?? false;

const pad = (n) => String(n).padStart(2, '0');

// Mirrors PurchaseDetails.vue's print approach (clone a hidden on-page
// template into a fresh window and call print()) rather than a DomPDF
// round-trip - no extra backend call needed since show() already returns
// everything the note needs.
const printTransfer = () => {
    const el = document.getElementById('stock-transfer-print');
    if (!el) return;

    const css = `
        @page { size: A4 portrait; margin: 16mm 14mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Georgia', 'Times New Roman', serif; color: #1f2937; }
        .sti-doc { font-family: Arial, Helvetica, sans-serif; font-size: 11.5px; color: #1f2937; line-height: 1.5; }
        .clearfix::after { content: ""; display: table; clear: both; }

        .sti-masthead { border-bottom: 3px solid #92400e; padding-bottom: 14px; margin-bottom: 20px; }
        .sti-kicker { font-family: 'Georgia', serif; font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: #92400e; font-weight: 700; margin-bottom: 6px; }
        .sti-title-row { display: flex; justify-content: space-between; align-items: flex-end; }
        .sti-ref { font-family: 'Georgia', serif; font-size: 26px; font-weight: 700; color: #0f172a; letter-spacing: 0.5px; }
        .sti-meta { text-align: right; }
        .sti-status { display: inline-block; padding: 4px 14px; border-radius: 3px; font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sti-date { font-size: 11.5px; color: #475569; }

        .sti-route { width: 100%; margin: 24px 0 26px 0; display: table; table-layout: fixed; }
        .sti-route-card { display: table-cell; width: 44%; border: 1px solid #d6d3d1; border-radius: 4px; padding: 14px 16px; vertical-align: top; background: #fafaf9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sti-route-card--to { background: #fffbeb; border-color: #fde68a; }
        .sti-route-arrow { display: table-cell; width: 12%; text-align: center; vertical-align: middle; font-size: 22px; color: #92400e; font-weight: 700; }
        .sti-route-label { font-size: 9.5px; letter-spacing: 2px; text-transform: uppercase; color: #92400e; font-weight: 700; margin-bottom: 6px; }
        .sti-route-shop { font-size: 15px; font-weight: 700; color: #0f172a; }

        .sti-items-wrap { border: 1px solid #333; border-radius: 8px; overflow: hidden; margin-bottom: 22px; }
        .sti-items { width: 100%; border-collapse: collapse; }
        .sti-items thead th { padding: 12px 14px; text-align: left; font-size: 9.5px; letter-spacing: 1.2px; text-transform: uppercase; font-weight: 700; color: #1f2937; border-bottom: 2px solid #333; }
        .sti-items tbody td { padding: 13px 14px; border-bottom: 1px solid #ddd; vertical-align: middle; }
        .sti-items tbody tr:last-child td { border-bottom: none; }
        .sti-items tfoot td { padding: 11px 14px; border-top: 2px solid #333; font-weight: 700; font-size: 11px; color: #1f2937; letter-spacing: 0.4px; text-transform: uppercase; }
        .sti-row-num { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; border: 1px solid #333; color: #1f2937; font-weight: 700; font-size: 10px; }
        .sti-serial { font-family: 'Courier New', monospace; font-weight: 700; color: #1f2937; border: 1px solid #999; border-radius: 3px; padding: 3px 7px; font-size: 10.5px; letter-spacing: 0.3px; }
        .sti-product { font-weight: 700; font-size: 12.5px; margin-bottom: 3px; color: #1f2937; }
        .sti-badge { display: inline-block; padding: 2px 9px; border: 1px solid #999; border-radius: 10px; font-size: 9.5px; color: #444; }

        .sti-summary { width: 100%; display: table; margin-bottom: 30px; }
        .sti-summary-note { display: table-cell; width: 60%; vertical-align: top; font-size: 11px; color: #57534e; padding-right: 20px; }
        .sti-summary-total { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
        .sti-total-pill { display: inline-block; border: 2px solid #0f172a; border-radius: 5px; padding: 10px 18px; }
        .sti-total-label { font-size: 9.5px; letter-spacing: 1.5px; text-transform: uppercase; color: #57534e; margin-bottom: 3px; }
        .sti-total-value { font-size: 20px; font-weight: 700; color: #0f172a; }

        .sti-signatures { width: 100%; display: table; margin-top: 46px; }
        .sti-sig { display: table-cell; width: 33.33%; text-align: center; padding: 0 10px; }
        .sti-sig-line { border-top: 1px solid #57534e; margin-bottom: 6px; padding-top: 6px; font-size: 11.5px; font-weight: 700; color: #0f172a; }
        .sti-sig-role { font-size: 9.5px; letter-spacing: 1px; text-transform: uppercase; color: #92400e; }

        .sti-footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e7e5e4; text-align: center; font-size: 10px; color: #a8a29e; font-style: italic; }
    `;

    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Stock Transfer ${transfer.value?.reference_no || ''}</title>
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
        <app-breadcrumb page-title="Stock Transfer Detail" />
        <app-loader v-if="loading" />

        <template v-else-if="transfer">
            <div class="card mb-3">
                <div class="card-body">

                    <!-- Top bar -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-light btn-sm d-flex align-items-center gap-1"
                                @click="router.push({ name: 'stockTransfers' })">
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
                            <template v-if="transfer.status === 'draft' && (canAccess('approve_stock_transfers') || canAccess('cancel_stock_transfers'))">
                            <button v-if="canAccess('approve_stock_transfers')"
                                class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="background:#d3f9d8;border:1px solid #8ce99a;color:#2f9e44;font-weight:600;"
                                :disabled="actionLoading || hasUnavailable()"
                                :title="hasUnavailable() ? 'Some serials are no longer available' : ''"
                                @click="openConfirm('approve')">
                                <span v-if="actionLoading" class="spinner-border spinner-border-sm"></span>
                                <Check v-else style="width:15px;height:15px;" />
                                Approve
                            </button>
                            <button v-if="canAccess('cancel_stock_transfers')"
                                class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="background:#fff0f0;border:1px solid #ffd0d0;color:#e03131;font-weight:600;"
                                :disabled="actionLoading" @click="openConfirm('cancel')">
                                <span v-if="actionLoading" class="spinner-border spinner-border-sm"></span>
                                <Xmark v-else style="width:15px;height:15px;" />
                                Cancel
                            </button>
                            </template>
                        </div>
                    </div>

                    <!-- Warning if any serial unavailable -->
                    <div v-if="transfer.status === 'draft' && hasUnavailable()"
                        class="mb-3 px-3 py-2 rounded-2 d-flex align-items-center gap-2"
                        style="background:#fff5f5;border:1px solid #ffd0d0;font-size:0.83rem;color:#e03131;">
                        ⚠️ Some serials are no longer available in the source shop. Approval is blocked.
                    </div>

                    <!-- Info cards -->
                    <div class="row g-3 mb-4">
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
                        <div v-if="transfer.note" class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">Note</div>
                                <div class="info-value" style="font-weight:400;font-size:0.85rem;">{{ transfer.note }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Items table -->
                    <div class="vst-table-wrap">
                        <div class="vst-table-header">
                            Transfer Serials ({{ transfer.items?.length ?? 0 }})
                        </div>

                        <!-- Desktop table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-sm mb-0 vst-table">
                                <thead>
                                    <tr>
                                        <th class="vst-th">#</th>
                                        <th class="vst-th">Serial No.</th>
                                        <th class="vst-th">Product</th>
                                        <th class="vst-th">SKU</th>
                                        <th class="vst-th">Variant</th>
                                        <th v-if="transfer.status === 'draft'" class="vst-th text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in transfer.items" :key="item.id"
                                        class="vst-tr"
                                        :class="{ 'vst-tr--unavailable': item.is_available === false }">
                                        <td class="vst-td vst-muted">{{ idx + 1 }}</td>
                                        <td class="vst-td">
                                            <span class="vst-serial">{{ item.serial_number }}</span>
                                        </td>
                                        <td class="vst-td vst-bold">{{ item.product_name }}</td>
                                        <td class="vst-td">{{ item.sku }}</td>
                                        <td class="vst-td">
                                            <span v-if="item.attributes" class="vst-badge">{{ item.attributes }}</span>
                                            <span v-else class="vst-muted">-</span>
                                        </td>
                                        <td v-if="transfer.status === 'draft'" class="vst-td text-center">
                                            <span v-if="item.is_available" class="vst-status vst-status--ok">Available</span>
                                            <span v-else class="vst-status vst-status--err">Not Available</span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="vst-tfoot">
                                    <tr>
                                        <td colspan="5" class="vst-td vst-bold">
                                            Total: {{ transfer.items?.length ?? 0 }} serial{{ (transfer.items?.length ?? 0) !== 1 ? 's' : '' }}
                                        </td>
                                        <td v-if="transfer.status === 'draft'" class="vst-td"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Mobile stacked cards -->
                        <div class="d-md-none">
                            <div v-for="(item, idx) in transfer.items" :key="item.id"
                                class="mobile-item-row"
                                :class="{ 'mobile-item-row--unavailable': item.is_available === false }">
                                <div class="mobile-item-field">
                                    <span class="mobile-item-label">#</span>
                                    <span class="mobile-item-value vst-muted">{{ idx + 1 }}</span>
                                </div>
                                <div class="mobile-item-field">
                                    <span class="mobile-item-label">Serial No.</span>
                                    <span class="mobile-item-value vst-serial">{{ item.serial_number }}</span>
                                </div>
                                <div class="mobile-item-field">
                                    <span class="mobile-item-label">Product</span>
                                    <span class="mobile-item-value vst-bold">{{ item.product_name }}</span>
                                </div>
                                <div class="mobile-item-field">
                                    <span class="mobile-item-label">SKU</span>
                                    <span class="mobile-item-value">{{ item.sku }}</span>
                                </div>
                                <div v-if="item.attributes" class="mobile-item-field">
                                    <span class="mobile-item-label">Variant</span>
                                    <span class="mobile-item-value"><span class="vst-badge">{{ item.attributes }}</span></span>
                                </div>
                                <div v-if="transfer.status === 'draft'" class="mobile-item-field">
                                    <span class="mobile-item-label">Status</span>
                                    <span class="mobile-item-value">
                                        <span v-if="item.is_available" class="vst-status vst-status--ok">Available</span>
                                        <span v-else class="vst-status vst-status--err">Not Available</span>
                                    </span>
                                </div>
                            </div>
                            <div class="vst-tfoot-mobile">
                                Total: {{ transfer.items?.length ?? 0 }} serial{{ (transfer.items?.length ?? 0) !== 1 ? 's' : '' }}
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- Hidden premium print template - cloned into a fresh window by
                 printTransfer() rather than rendered on screen. -->
            <div id="stock-transfer-print" style="display:none">
                <div class="sti-doc">
                    <div class="sti-masthead clearfix">
                        <div class="sti-title-row">
                            <div>
                                <div class="sti-kicker">Stock Transfer Note</div>
                                <div class="sti-ref">{{ transfer.reference_no }}</div>
                            </div>
                            <div class="sti-meta">
                                <div class="sti-status"
                                    :style="{ background: statusStyle(transfer.status).bg, color: statusStyle(transfer.status).color }">
                                    {{ transfer.status.charAt(0).toUpperCase() + transfer.status.slice(1) }}
                                </div>
                                <div class="sti-date">Transfer Date: {{ transfer.transfer_date }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="sti-route">
                        <div class="sti-route-card">
                            <div class="sti-route-label">From</div>
                            <div class="sti-route-shop">{{ transfer.from_tenant?.name ?? '-' }}</div>
                        </div>
                        <div class="sti-route-arrow">&rarr;</div>
                        <div class="sti-route-card sti-route-card--to">
                            <div class="sti-route-label">To</div>
                            <div class="sti-route-shop">{{ transfer.to_tenant?.name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="sti-items-wrap">
                        <table class="sti-items">
                            <thead>
                                <tr>
                                    <th style="width:38px;">#</th>
                                    <th>Serial No.</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Variant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in transfer.items" :key="item.id">
                                    <td><span class="sti-row-num">{{ pad(idx + 1) }}</span></td>
                                    <td><span class="sti-serial">{{ item.serial_number }}</span></td>
                                    <td class="sti-product">{{ item.product_name }}</td>
                                    <td>{{ item.sku || '-' }}</td>
                                    <td>
                                        <span v-if="item.attributes" class="sti-badge">{{ item.attributes }}</span>
                                        <span v-else>-</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">
                                        Total {{ transfer.items?.length ?? 0 }} Serial{{ (transfer.items?.length ?? 0) !== 1 ? 's' : '' }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="sti-summary clearfix">
                        <div class="sti-summary-note">
                            <div v-if="transfer.note"><strong>Note:</strong> {{ transfer.note }}</div>
                        </div>
                        <div class="sti-summary-total">
                            <div class="sti-total-pill">
                                <div class="sti-total-label">Total Serials</div>
                                <div class="sti-total-value">{{ transfer.items?.length ?? 0 }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="sti-signatures">
                        <div class="sti-sig">
                            <div class="sti-sig-line">{{ transfer.created_by ?? '-' }}</div>
                            <div class="sti-sig-role">Prepared By</div>
                        </div>
                        <div class="sti-sig">
                            <div class="sti-sig-line">{{ transfer.approved_by ?? ' ' }}</div>
                            <div class="sti-sig-role">Approved By</div>
                        </div>
                        <div class="sti-sig">
                            <div class="sti-sig-line">&nbsp;</div>
                            <div class="sti-sig-role">Received By</div>
                        </div>
                    </div>

                    <div class="sti-footer">This is a system-generated stock transfer note.</div>
                </div>
            </div>
        </template>
    </div>

    <!-- Confirm Modal (Approve / Cancel) -->
    <div v-if="confirmModalActive" class="modal fade" id="st-confirm-modal" tabindex="-1" aria-hidden="true">
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
/* Info cards */
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

/* Items table */
.vst-table-wrap {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}

.vst-table-header {
    padding: 12px 16px;
    background: var(--base-color);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--table-th-font-color);
    border-bottom: 1px solid var(--border-color);
}

.vst-table {
    font-size: 0.83rem;
    --bs-table-bg: var(--white);
    --bs-table-border-color: var(--border-color);
    --bs-table-color: var(--font-color);
    --bs-table-hover-bg: var(--base-color);
}

.vst-th {
    padding: 10px 16px;
    color: var(--table-th-font-color);
    background-color: var(--base-color);
    font-size: 0.72rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.4px;
}

.vst-tr { border-top: 1px solid var(--border-color); }
.vst-tr:hover .vst-td { background-color: var(--base-color) !important; }
.vst-tr--unavailable .vst-td { background-color: #fff5f5 !important; }

.vst-td {
    padding: 12px 16px;
    color: var(--font-color);
    background-color: var(--white);
}

.vst-tfoot { border-top: 2px solid var(--border-color); }
.vst-tfoot .vst-td { background-color: var(--base-color); }

.vst-muted  { color: var(--table-th-font-color); }
.vst-bold   { font-weight: 600; }
.vst-serial { font-family: monospace; font-weight: 700; color: #1c7ed6; font-size: 0.9rem; }

.vst-badge {
    padding: 2px 10px;
    border-radius: 12px;
    background: var(--base-color);
    color: var(--font-color);
    font-size: 0.75rem;
    border: 1px solid var(--border-color);
}

.vst-status {
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.73rem;
    font-weight: 600;
}

.vst-status--ok  { background: #d3f9d8; color: #2f9e44; }
.vst-status--err { background: #fff0f0; color: #e03131; }

/* Mobile cards */
.mobile-item-row {
    padding: 12px 16px;
    background: var(--white);
    border-top: 1px solid var(--border-color);
}

.mobile-item-row--unavailable { background: #fff5f5 !important; }

.vst-tfoot-mobile {
    padding: 10px 16px;
    background: var(--base-color);
    border-top: 2px solid var(--border-color);
    font-weight: 700;
    color: var(--font-color);
    font-size: 0.83rem;
}

.mobile-item-field {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    font-size: 0.83rem;
}

.mobile-item-label {
    font-weight: 600;
    color: var(--table-th-font-color);
    flex-shrink: 0;
    margin-right: 12px;
}

.mobile-item-value { text-align: right; }
</style>
