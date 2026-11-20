<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { REDX_ORDERS } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";

const { t } = useI18n();

const tableId = ref('redx-orders-table');

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const options = ref({
    url: REDX_ORDERS,
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
            title: 'Tracking ID',
            // REDX's tracking id is the only handle on a parcel, so it doubles
            // as the link to their public tracking page.
            type: 'custom-html',
            key: 'tracking_code',
            modifier: (code, row) => code
                ? `<a href="${escapeHtml(row.track_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(code)}</a>`
                : '<span class="text-muted">-</span>',
        },
        {
            title: 'Recipient',
            type: 'custom-html',
            key: 'recipient_name',
            modifier: (name, row) =>
                `<div><div>${escapeHtml(name)}</div><div class="text-muted fs-6">${escapeHtml(row.recipient_phone)}</div></div>`,
        },
        {
            title: t('address'),
            type: 'component',
            key: 'address',
            componentName: h(AddressColumn),
        },
        {
            title: 'Delivery Area',
            type: 'object',
            key: 'recipient_area_name',
            modifier: (area) => area || '-',
        },
        {
            title: 'COD Amount',
            type: 'object',
            key: 'amount_to_collect',
            accountAble: true,
            modifier: (amount) => numberWithCurrencySymbol(amount),
        },
        {
            title: 'Weight (KG)',
            type: 'text',
            key: 'item_weight',
        },
        {
            title: 'Status',
            type: 'custom-html',
            key: 'status',
            modifier: (status) => {
                if (!status) return '<span class="text-muted">-</span>';
                const normalized = status.toLowerCase();
                let className = 'bg-info';
                if (normalized.includes('delivered')) className = 'bg-success';
                else if (normalized.includes('cancel') || normalized.includes('return')) className = 'bg-danger';
                else if (normalized.includes('pending') || normalized.includes('hold')) className = 'bg-warning';
                return `<span class="badge-square-light ${className} text-capitalize">${escapeHtml(status)}</span>`;
            },
        },
        {
            title: t('created_by'),
            type: 'text',
            key: 'created_by_name',
        },
    ],
    actions: [],
    filters: [],
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="REDX Orders" />

        <app-datatable :id="tableId" :options="options" :button-visible="false" />
    </div>
</template>

<style scoped lang="scss"></style>
