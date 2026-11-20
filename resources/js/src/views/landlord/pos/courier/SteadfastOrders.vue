<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { STEADFAST_ORDERS, STEADFAST_ORDERS_IMPORT } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import usePermission from "@/composable/permission/usePermission.js";

const { t } = useI18n();
const toast = useToast();
const emitter = useEmitter();
const { canAccess } = usePermission();

const tableId = ref('steadfast-orders-table');

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

// Links the code to the courier's own tracking page, same as the REDX list.
const trackLink = (code, row) => code
    ? `<a href="${escapeHtml(row.track_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(code)}</a>`
    : '<span class="text-muted">-</span>';

const isImportModalActive = ref(false);
const importFile = ref(null);
const importing = ref(false);

const onFileChange = (event) => {
    importFile.value = event.target.files[0] || null;
};

const importOrders = () => {
    if (!importFile.value) {
        toast.error('Please choose a file first');
        return;
    }

    const formData = new FormData();
    formData.append('file', importFile.value);

    importing.value = true;
    Axios.post(STEADFAST_ORDERS_IMPORT, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
        .then(({ data }) => {
            toast.success(data.message || 'Imported successfully');
            emitter.emit('reload-' + tableId.value);
            isImportModalActive.value = false;
            importFile.value = null;
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message || 'Failed to import file');
        })
        .finally(() => {
            importing.value = false;
        });
};

const options = ref({
    url: STEADFAST_ORDERS,
    pageSize: 10,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: false,
    columns: [
        {
            title: t('date'),
            type: 'text',
            key: 'created_at',
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'invoice_number',
        },
        {
            title: 'Consignment ID',
            type: 'text',
            key: 'consignment_id',
        },
        {
            title: 'Tracking Code',
            type: 'custom-html',
            key: 'tracking_code',
            modifier: trackLink,
        },
        {
            title: 'Recipient',
            type: 'custom-html',
            key: 'recipient_name',
            modifier: (name, row) => {
                return `<div><div>${name}</div><div class="text-muted fs-6">${row.recipient_phone}</div></div>`;
            },
        },
        {
            title: t('address'),
            type: 'component',
            key: 'address',
            componentName: h(AddressColumn),
        },
        {
            title: 'COD Amount',
            type: 'object',
            key: 'amount_to_collect',
            accountAble: true,
            modifier: (amount => numberWithCurrencySymbol(amount)),
        },
        {
            title: 'Delivery Type',
            type: 'text',
            key: 'delivery_type',
        },
        {
            title: 'Weight (KG)',
            type: 'text',
            key: 'item_weight',
        },
        {
            title: 'Exchange',
            type: 'custom-html',
            key: 'is_exchange',
            modifier: (isExchange) => isExchange
                ? '<span class="badge-square-light bg-info">Yes</span>'
                : '<span class="text-muted">-</span>',
        },
        {
            title: 'Status',
            type: 'custom-html',
            key: 'status',
            modifier: (status) => {
                const normalized = (status || '').toLowerCase();
                let className = 'bg-info';
                if (normalized.includes('delivered')) className = 'bg-success';
                else if (normalized.includes('cancel')) className = 'bg-danger';
                else if (normalized.includes('review') || normalized.includes('pending') || normalized.includes('hold')) className = 'bg-warning';
                return `<div class="mb-2"><span class="badge-square-light ${className} text-capitalize">${status}</span></div>`;
            }
        },
        {
            title: 'Payment',
            type: 'custom-html',
            key: 'payment_status',
            modifier: (paymentStatus) => {
                if (!paymentStatus) return '<span class="text-muted">-</span>';
                const normalized = paymentStatus.toLowerCase();
                const className = normalized.includes('unpaid') ? 'bg-danger' : 'bg-success';
                return `<div class="mb-2"><span class="badge-square-light ${className} text-capitalize">${paymentStatus}</span></div>`;
            }
        },
        {
            title: t('created_by'),
            type: 'text',
            key: 'created_by_name',
        },
    ],
    actions: [],
    filters: [],
})

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Steadfast Orders" />

        <app-datatable :id="tableId" :options="options" :button-visible="canAccess('import_steadfast_orders')"
            add-button-text="Import from Excel" @buttonAction="isImportModalActive = true" />

        <app-modal v-if="isImportModalActive" modal-id="steadfast-import-modal" title="Import Orders from Excel"
            save-button-label="Import" :preloader="importing" @submit="importOrders"
            @close="isImportModalActive = false">
            <template #body>
                <label class="form-label">Steadfast Orders File (.xlsx, .xls, .csv)</label>
                <input type="file" class="form-control" accept=".xlsx,.xls,.csv" @change="onFileChange" />
                <p class="text-muted fs-6 mt-2">
                    Export your orders from the Steadfast merchant dashboard and upload that file here. Orders
                    already in the list will be updated, not duplicated.
                </p>
            </template>
        </app-modal>
    </div>
</template>

<style scoped lang="scss"></style>
