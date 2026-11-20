<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { COURIER_ORDERS } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";

const { t } = useI18n();

const tableId = ref('courier-orders-table');

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

// Providers identify a parcel differently — Pathao by consignment id, REDX and
// Steadfast by tracking code — so show whichever this row has, linked to that
// courier's own tracking page.
const trackLink = (value, row) => {
    const label = row.consignment_id || row.tracking_code;

    if (!label) return '<span class="text-muted">-</span>';
    if (!row.track_url) return escapeHtml(label);

    return `<a href="${escapeHtml(row.track_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(label)}</a>`;
};

const options = ref({
    url: COURIER_ORDERS,
    pageSize: 10,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: true,
    columns: [
        {
            title: t('date'),
            type: 'text',
            key: 'created_at',
        },
        {
            title: 'Provider',
            type: 'custom-html',
            key: 'provider',
            modifier: (provider) => {
                return `<span class="badge-square-light bg-info text-capitalize">${provider}</span>`;
            },
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'invoice_number',
        },
        {
            title: 'Tracking',
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
        // {
        //     title: 'Payment',
        //     type: 'custom-html',
        //     key: 'payment_status',
        //     modifier: (paymentStatus) => {
        //         if (!paymentStatus) return '<span class="text-muted">-</span>';
        //         const normalized = paymentStatus.toLowerCase();
        //         const className = normalized.includes('unpaid') ? 'bg-danger' : 'bg-success';
        //         return `<div class="mb-2"><span class="badge-square-light ${className} text-capitalize">${paymentStatus}</span></div>`;
        //     }
        // },
        {
            title: t('created_by'),
            type: 'text',
            key: 'created_by_name',
        },
    ],
    actions: [],
    filters: [
        {
            label: 'Provider',
            type: 'search-select',
            key: 'provider',
            options: [
                { id: 'pathao', name: 'Pathao' },
                { id: 'steadfast', name: 'Steadfast' },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
    ],
})
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="All Courier Orders" />

        <app-datatable :id="tableId" :options="options" :button-visible="false" />
    </div>
</template>

<style scoped lang="scss"></style>
