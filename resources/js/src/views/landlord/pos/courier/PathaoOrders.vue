<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { PATHAO_ORDERS, PATHAO_ORDERS_SYNC, PATHAO_STORES } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";
import usePermission from "@/composable/permission/usePermission.js";

const { t } = useI18n();
const toast = useToast();
const emitter = useEmitter();
const { canAccess } = usePermission();

const tableId = ref('pathao-orders-table');

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

// Links the code to the courier's own tracking page, same as the REDX list.
const trackLink = (code, row) => code
    ? `<a href="${escapeHtml(row.track_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(code)}</a>`
    : '<span class="text-muted">-</span>';

const isSyncModalActive = ref(false);
const stores = ref([]);
const syncStoreId = ref(null);
const syncing = ref(false);

const loadStores = async () => {
    try {
        const { data } = await Axios.get(PATHAO_STORES);
        stores.value = data.data || [];
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Failed to load Pathao stores');
    }
};

const openSyncModal = () => {
    isSyncModalActive.value = true;
    if (!stores.value.length) loadStores();
};

const syncOrders = () => {
    if (!syncStoreId.value) {
        toast.error('Please choose a store');
        return;
    }

    syncing.value = true;
    Axios.post(PATHAO_ORDERS_SYNC, { store_id: syncStoreId.value })
        .then(({ data }) => {
            toast.success(data.message || 'Synced successfully');
            emitter.emit('reload-' + tableId.value);
            isSyncModalActive.value = false;
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message || 'Failed to sync Pathao orders');
        })
        .finally(() => {
            syncing.value = false;
        });
};

const options = ref({
    url: PATHAO_ORDERS,
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
            type: 'custom-html',
            key: 'consignment_id',
            modifier: trackLink,
        },
        {
            title: 'Store',
            type: 'text',
            key: 'store_name',
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
            title: 'Item Type',
            type: 'text',
            key: 'item_type',
        },
        {
            title: 'Delivery Type',
            type: 'text',
            key: 'delivery_type',
        },
        {
            title: 'Amount to Collect',
            type: 'object',
            key: 'amount_to_collect',
            accountAble: true,
            modifier: (amount => numberWithCurrencySymbol(amount)),
        },
        {
            title: 'Discount',
            type: 'object',
            key: 'discount',
            accountAble: true,
            modifier: (discount => numberWithCurrencySymbol(discount)),
        },
        {
            title: 'Status',
            type: 'custom-html',
            key: 'status',
            modifier: (status) => {
                const normalized = (status || '').toLowerCase();
                let className = 'bg-info';
                if (normalized.includes('deliver')) className = 'bg-success';
                else if (normalized.includes('cancel') || normalized.includes('fail')) className = 'bg-danger';
                else if (normalized.includes('pending')) className = 'bg-warning';
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
    filters: [

    ],
})

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Pathao Orders" />

        <app-datatable :id="tableId" :options="options" :button-visible="canAccess('sync_pathao_orders')"
            add-button-text="Sync from Pathao" @buttonAction="openSyncModal" />

        <app-modal v-if="isSyncModalActive" modal-id="pathao-sync-modal" title="Sync Orders from Pathao"
            save-button-label="Sync" :preloader="syncing" @submit="syncOrders" @close="isSyncModalActive = false">
            <template #body>
                <app-input type="select" id="sync_store_id" label="Store" label-required list-key-field="store_id"
                    list-value-field="store_name" v-model="syncStoreId" :options="stores"
                    choose-label="Choose a store" />
                <p class="text-muted fs-6 mt-2">
                    This will pull all orders placed under the selected store from Pathao into this list.
                </p>
            </template>
        </app-modal>
    </div>
</template>

<style scoped lang="scss"></style>
