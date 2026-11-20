<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Modal } from 'bootstrap';
import { NavArrowLeft, Check, Xmark, Printer } from '@iconoir/vue';
import { DAMAGE_PRODUCTS } from '@/services/endpoints/landlord.js';
import Axios from '@/services/axios/index.js';
import { useToast } from 'vue-toastification';
import router from '@/router/index.js';
import usePermission from "@/composable/permission/usePermission.js";

const { canAccess } = usePermission();

const props = defineProps({ id: {} });
const toast = useToast();

const damageProduct = ref(null);
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
        title: 'Approve Damage Entry?',
        message: 'These serials will be removed from sellable stock immediately. This cannot be undone.',
        btnClass: 'btn-success',
        btnLabel: 'Approve',
    },
    cancel: {
        icon: Xmark,
        iconClass: 'text-danger',
        title: 'Cancel Damage Entry?',
        message: 'This damage record will be marked as cancelled.',
        btnClass: 'btn-danger',
        btnLabel: 'Cancel Entry',
    },
};

const openConfirm = async (type) => {
    confirmType.value = type;
    confirmModalActive.value = true;
    await nextTick();
    const el = document.getElementById('dp-confirm-modal');
    if (el) {
        modalInstance = new Modal(el);
        modalInstance.show();
        el.addEventListener('hidden.bs.modal', closeConfirm, { once: true });
    }
};

const closeConfirm = () => {
    // confirmModalActive drives a v-if in this same component, so it tears
    // the modal's DOM down on this same tick - calling hide() here would
    // just start an async fade transition that gets cut off immediately,
    // leaving Bootstrap's transitionend-based body cleanup stuck forever.
    // Force that cleanup synchronously instead.
    const el = document.getElementById('dp-confirm-modal');
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
        const { data } = await Axios.get(`${DAMAGE_PRODUCTS}/${props.id}`);
        damageProduct.value = data;
    } catch {
        toast.error('Failed to load damage entry.');
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
        await Axios.post(`${DAMAGE_PRODUCTS}/${props.id}/${type}`);
        toast.success(type === 'approve' ? 'Damage entry approved. Stock updated.' : 'Damage entry cancelled.');
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
    damageProduct.value?.items?.some(i => i.is_available === false) ?? false;

const pad = (n) => String(n).padStart(2, '0');

// Mirrors ViewStockTransfer.vue's print approach: clone a hidden on-page
// template into a fresh window and call print() - no extra backend call
// needed since load() already returns everything the note needs. Only one
// shop here (not a from/to route), so a single shop card replaces the
// transfer's two-card route.
const printDamage = () => {
    const el = document.getElementById('damage-product-print');
    if (!el) return;

    const css = `
        @page { size: A4 portrait; margin: 16mm 14mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Georgia', 'Times New Roman', serif; color: #1f2937; }
        .dpi-doc { font-family: Arial, Helvetica, sans-serif; font-size: 11.5px; color: #1f2937; line-height: 1.5; }
        .clearfix::after { content: ""; display: table; clear: both; }

        .dpi-masthead { border-bottom: 3px solid #92400e; padding-bottom: 14px; margin-bottom: 20px; }
        .dpi-kicker { font-family: 'Georgia', serif; font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: #92400e; font-weight: 700; margin-bottom: 6px; }
        .dpi-title-row { display: flex; justify-content: space-between; align-items: flex-end; }
        .dpi-ref { font-family: 'Georgia', serif; font-size: 26px; font-weight: 700; color: #0f172a; letter-spacing: 0.5px; }
        .dpi-meta { text-align: right; }
        .dpi-status { display: inline-block; padding: 4px 14px; border-radius: 3px; font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .dpi-date { font-size: 11.5px; color: #475569; }

        .dpi-shop-card { border: 1px solid #d6d3d1; border-radius: 4px; padding: 14px 16px; background: #fafaf9; -webkit-print-color-adjust: exact; print-color-adjust: exact; margin: 24px 0 26px 0; }
        .dpi-shop-label { font-size: 9.5px; letter-spacing: 2px; text-transform: uppercase; color: #92400e; font-weight: 700; margin-bottom: 6px; }
        .dpi-shop-name { font-size: 15px; font-weight: 700; color: #0f172a; }

        .dpi-items-wrap { border: 1px solid #333; border-radius: 8px; overflow: hidden; margin-bottom: 22px; }
        .dpi-items { width: 100%; border-collapse: collapse; }
        .dpi-items thead th { padding: 12px 14px; text-align: left; font-size: 9.5px; letter-spacing: 1.2px; text-transform: uppercase; font-weight: 700; color: #1f2937; border-bottom: 2px solid #333; }
        .dpi-items tbody td { padding: 13px 14px; border-bottom: 1px solid #ddd; vertical-align: middle; }
        .dpi-items tbody tr:last-child td { border-bottom: none; }
        .dpi-items tfoot td { padding: 11px 14px; border-top: 2px solid #333; font-weight: 700; font-size: 11px; color: #1f2937; letter-spacing: 0.4px; text-transform: uppercase; }
        .dpi-row-num { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; border: 1px solid #333; color: #1f2937; font-weight: 700; font-size: 10px; }
        .dpi-serial { font-family: 'Courier New', monospace; font-weight: 700; color: #1f2937; border: 1px solid #999; border-radius: 3px; padding: 3px 7px; font-size: 10.5px; letter-spacing: 0.3px; }
        .dpi-product { font-weight: 700; font-size: 12.5px; margin-bottom: 3px; color: #1f2937; }
        .dpi-badge { display: inline-block; padding: 2px 9px; border: 1px solid #999; border-radius: 10px; font-size: 9.5px; color: #444; }

        .dpi-note { font-size: 11px; color: #57534e; margin-bottom: 24px; }

        .dpi-signatures { width: 100%; display: table; margin-top: 46px; }
        .dpi-sig { display: table-cell; width: 33.33%; text-align: center; padding: 0 10px; }
        .dpi-sig-line { border-top: 1px solid #57534e; margin-bottom: 6px; padding-top: 6px; font-size: 11.5px; font-weight: 700; color: #0f172a; }
        .dpi-sig-role { font-size: 9.5px; letter-spacing: 1px; text-transform: uppercase; color: #92400e; }

        .dpi-footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e7e5e4; text-align: center; font-size: 10px; color: #a8a29e; font-style: italic; }
    `;

    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Damage Product ${damageProduct.value?.reference_no || ''}</title>
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
        <app-breadcrumb page-title="Damage Product Detail" />
        <app-loader v-if="loading" />

        <template v-else-if="damageProduct">
            <div class="card mb-3">
                <div class="card-body">

                    <!-- Top bar -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-light btn-sm d-flex align-items-center gap-1"
                                @click="router.push({ name: 'damageProducts' })">
                                <NavArrowLeft style="width:15px;height:15px;" /> Back
                            </button>
                            <div>
                                <span style="font-size:1rem;font-weight:700;color:#212529;">
                                    {{ damageProduct.reference_no }}
                                </span>
                                <span
                                    style="display:inline-block;margin-left:10px;padding:2px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;"
                                    :style="{ background: statusStyle(damageProduct.status).bg, color: statusStyle(damageProduct.status).color }">
                                    {{ damageProduct.status.charAt(0).toUpperCase() + damageProduct.status.slice(1) }}
                                </span>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="background:#fff7ed;border:1px solid #fed7aa;color:#92400e;font-weight:600;"
                                @click="printDamage">
                                <Printer style="width:15px;height:15px;" />
                                Print
                            </button>
                            <template v-if="damageProduct.status === 'draft' && (canAccess('approve_damage_products') || canAccess('cancel_damage_products'))">
                            <button v-if="canAccess('approve_damage_products')"
                                class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="background:#d3f9d8;border:1px solid #8ce99a;color:#2f9e44;font-weight:600;"
                                :disabled="actionLoading || hasUnavailable()"
                                :title="hasUnavailable() ? 'Some serials are no longer available' : ''"
                                @click="openConfirm('approve')">
                                <span v-if="actionLoading" class="spinner-border spinner-border-sm"></span>
                                <Check v-else style="width:15px;height:15px;" />
                                Approve
                            </button>
                            <button v-if="canAccess('cancel_damage_products')"
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
                    <div v-if="damageProduct.status === 'draft' && hasUnavailable()"
                        class="mb-3 px-3 py-2 rounded-2 d-flex align-items-center gap-2"
                        style="background:#fff5f5;border:1px solid #ffd0d0;font-size:0.83rem;color:#e03131;">
                        ⚠️ Some serials are no longer available in this shop. Approval is blocked.
                    </div>

                    <!-- Info cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">Shop</div>
                                <div class="info-value">{{ damageProduct.tenant?.name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">Damage Date</div>
                                <div class="info-value">{{ damageProduct.damage_date }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">Created By</div>
                                <div class="info-value">{{ damageProduct.created_by ?? '-' }}</div>
                            </div>
                        </div>
                        <div v-if="damageProduct.approved_by" class="col-6 col-md-2">
                            <div class="info-card info-card--approved">
                                <div class="info-label" style="color:#2f9e44;">Approved By</div>
                                <div class="info-value" style="color:#2f9e44;">{{ damageProduct.approved_by }}</div>
                            </div>
                        </div>
                        <div v-if="damageProduct.cancelled_by" class="col-6 col-md-2">
                            <div class="info-card info-card--cancelled">
                                <div class="info-label" style="color:#e03131;">Cancelled By</div>
                                <div class="info-value" style="color:#e03131;">{{ damageProduct.cancelled_by }}</div>
                            </div>
                        </div>
                        <div v-if="damageProduct.note" class="col-6 col-md-2">
                            <div class="info-card">
                                <div class="info-label">Note</div>
                                <div class="info-value" style="font-weight:400;font-size:0.85rem;">{{ damageProduct.note }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Items table -->
                    <div class="vdp-table-wrap">
                        <div class="vdp-table-header">
                            Damaged Serials ({{ damageProduct.items?.length ?? 0 }})
                        </div>

                        <!-- Desktop table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-sm mb-0 vdp-table">
                                <thead>
                                    <tr>
                                        <th class="vdp-th">#</th>
                                        <th class="vdp-th">Serial No.</th>
                                        <th class="vdp-th">Product</th>
                                        <th class="vdp-th">SKU</th>
                                        <th class="vdp-th">Variant</th>
                                        <th v-if="damageProduct.status === 'draft'" class="vdp-th text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in damageProduct.items" :key="item.id"
                                        class="vdp-tr"
                                        :class="{ 'vdp-tr--unavailable': item.is_available === false }">
                                        <td class="vdp-td vdp-muted">{{ idx + 1 }}</td>
                                        <td class="vdp-td">
                                            <span class="vdp-serial">{{ item.serial_number }}</span>
                                        </td>
                                        <td class="vdp-td vdp-bold">{{ item.product_name }}</td>
                                        <td class="vdp-td">{{ item.sku }}</td>
                                        <td class="vdp-td">
                                            <span v-if="item.attributes" class="vdp-badge">{{ item.attributes }}</span>
                                            <span v-else class="vdp-muted">-</span>
                                        </td>
                                        <td v-if="damageProduct.status === 'draft'" class="vdp-td text-center">
                                            <span v-if="item.is_available" class="vdp-status vdp-status--ok">Available</span>
                                            <span v-else class="vdp-status vdp-status--err">Not Available</span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="vdp-tfoot">
                                    <tr>
                                        <td colspan="5" class="vdp-td vdp-bold">
                                            Total: {{ damageProduct.items?.length ?? 0 }} serial{{ (damageProduct.items?.length ?? 0) !== 1 ? 's' : '' }}
                                        </td>
                                        <td v-if="damageProduct.status === 'draft'" class="vdp-td"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Mobile stacked cards -->
                        <div class="d-md-none">
                            <div v-for="(item, idx) in damageProduct.items" :key="item.id"
                                class="mobile-item-row"
                                :class="{ 'mobile-item-row--unavailable': item.is_available === false }">
                                <div class="mobile-item-field">
                                    <span class="mobile-item-label">#</span>
                                    <span class="mobile-item-value vdp-muted">{{ idx + 1 }}</span>
                                </div>
                                <div class="mobile-item-field">
                                    <span class="mobile-item-label">Serial No.</span>
                                    <span class="mobile-item-value vdp-serial">{{ item.serial_number }}</span>
                                </div>
                                <div class="mobile-item-field">
                                    <span class="mobile-item-label">Product</span>
                                    <span class="mobile-item-value vdp-bold">{{ item.product_name }}</span>
                                </div>
                                <div class="mobile-item-field">
                                    <span class="mobile-item-label">SKU</span>
                                    <span class="mobile-item-value">{{ item.sku }}</span>
                                </div>
                                <div v-if="item.attributes" class="mobile-item-field">
                                    <span class="mobile-item-label">Variant</span>
                                    <span class="mobile-item-value"><span class="vdp-badge">{{ item.attributes }}</span></span>
                                </div>
                                <div v-if="damageProduct.status === 'draft'" class="mobile-item-field">
                                    <span class="mobile-item-label">Status</span>
                                    <span class="mobile-item-value">
                                        <span v-if="item.is_available" class="vdp-status vdp-status--ok">Available</span>
                                        <span v-else class="vdp-status vdp-status--err">Not Available</span>
                                    </span>
                                </div>
                            </div>
                            <div class="vdp-tfoot-mobile">
                                Total: {{ damageProduct.items?.length ?? 0 }} serial{{ (damageProduct.items?.length ?? 0) !== 1 ? 's' : '' }}
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- Hidden premium print template - cloned into a fresh window by
                 printDamage() rather than rendered on screen. -->
            <div id="damage-product-print" style="display:none">
                <div class="dpi-doc">
                    <div class="dpi-masthead clearfix">
                        <div class="dpi-title-row">
                            <div>
                                <div class="dpi-kicker">Damage Product Note</div>
                                <div class="dpi-ref">{{ damageProduct.reference_no }}</div>
                            </div>
                            <div class="dpi-meta">
                                <div class="dpi-status"
                                    :style="{ background: statusStyle(damageProduct.status).bg, color: statusStyle(damageProduct.status).color }">
                                    {{ damageProduct.status.charAt(0).toUpperCase() + damageProduct.status.slice(1) }}
                                </div>
                                <div class="dpi-date">Damage Date: {{ damageProduct.damage_date }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="dpi-shop-card">
                        <div class="dpi-shop-label">Shop</div>
                        <div class="dpi-shop-name">{{ damageProduct.tenant?.name ?? '-' }}</div>
                    </div>

                    <div class="dpi-items-wrap">
                        <table class="dpi-items">
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
                                <tr v-for="(item, idx) in damageProduct.items" :key="item.id">
                                    <td><span class="dpi-row-num">{{ pad(idx + 1) }}</span></td>
                                    <td><span class="dpi-serial">{{ item.serial_number }}</span></td>
                                    <td class="dpi-product">{{ item.product_name }}</td>
                                    <td>{{ item.sku || '-' }}</td>
                                    <td>
                                        <span v-if="item.attributes" class="dpi-badge">{{ item.attributes }}</span>
                                        <span v-else>-</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">
                                        Total {{ damageProduct.items?.length ?? 0 }} Serial{{ (damageProduct.items?.length ?? 0) !== 1 ? 's' : '' }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div v-if="damageProduct.note" class="dpi-note"><strong>Note / Reason:</strong> {{ damageProduct.note }}</div>

                    <div class="dpi-signatures">
                        <div class="dpi-sig">
                            <div class="dpi-sig-line">{{ damageProduct.created_by ?? '-' }}</div>
                            <div class="dpi-sig-role">Prepared By</div>
                        </div>
                        <div class="dpi-sig">
                            <div class="dpi-sig-line">{{ damageProduct.approved_by ?? ' ' }}</div>
                            <div class="dpi-sig-role">Approved By</div>
                        </div>
                        <div class="dpi-sig">
                            <div class="dpi-sig-line">&nbsp;</div>
                            <div class="dpi-sig-role">Witnessed By</div>
                        </div>
                    </div>

                    <div class="dpi-footer">This is a system-generated damage product note.</div>
                </div>
            </div>
        </template>
    </div>

    <!-- Confirm Modal (Approve / Cancel) -->
    <div v-if="confirmModalActive" class="modal fade" id="dp-confirm-modal" tabindex="-1" aria-hidden="true">
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
.vdp-table-wrap {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}

.vdp-table-header {
    padding: 12px 16px;
    background: var(--base-color);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--table-th-font-color);
    border-bottom: 1px solid var(--border-color);
}

.vdp-table {
    font-size: 0.83rem;
    --bs-table-bg: var(--white);
    --bs-table-border-color: var(--border-color);
    --bs-table-color: var(--font-color);
    --bs-table-hover-bg: var(--base-color);
}

.vdp-th {
    padding: 10px 16px;
    color: var(--table-th-font-color);
    background-color: var(--base-color);
    font-size: 0.72rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.4px;
}

.vdp-tr { border-top: 1px solid var(--border-color); }
.vdp-tr:hover .vdp-td { background-color: var(--base-color) !important; }
.vdp-tr--unavailable .vdp-td { background-color: #fff5f5 !important; }

.vdp-td {
    padding: 12px 16px;
    color: var(--font-color);
    background-color: var(--white);
}

.vdp-tfoot { border-top: 2px solid var(--border-color); }
.vdp-tfoot .vdp-td { background-color: var(--base-color); }

.vdp-muted  { color: var(--table-th-font-color); }
.vdp-bold   { font-weight: 600; }
.vdp-serial { font-family: monospace; font-weight: 700; color: #1c7ed6; font-size: 0.9rem; }

.vdp-badge {
    padding: 2px 10px;
    border-radius: 12px;
    background: var(--base-color);
    color: var(--font-color);
    font-size: 0.75rem;
    border: 1px solid var(--border-color);
}

.vdp-status {
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.73rem;
    font-weight: 600;
}

.vdp-status--ok  { background: #d3f9d8; color: #2f9e44; }
.vdp-status--err { background: #fff0f0; color: #e03131; }

/* Mobile cards */
.mobile-item-row {
    padding: 12px 16px;
    background: var(--white);
    border-top: 1px solid var(--border-color);
}

.mobile-item-row--unavailable { background: #fff5f5 !important; }

.vdp-tfoot-mobile {
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
