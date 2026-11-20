<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {
    SALE_STATEMENTS,
    SALE_STATEMENT_PDF,
    SELECTED_CUSTOMERS,
    SELECTED_SHOPS,
    SELECTED_SELLERS,
    SELECTED_PAYMENT_METHODS,
    SELECTED_BRAND
} from "@/services/endpoints/landlord.js";
import {formatDateToLocal, numberWithCurrencySymbol} from "@/utils/helper.js";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import usePermission from "@/composable/permission/usePermission.js";
import SoldItemsCell from "@/views/landlord/pos/reports/SoldItemsCell.vue";
import StatementPdfColumnsModal from "@/views/landlord/pos/statement/StatementPdfColumnsModal.vue";

const {canAccess, canFilterByShop} = usePermission();
const {t} = useI18n();

const toast = useToast();
const tableId = ref('stock-statement-table')

const getInitials = (name) => {
    const parts = (name || '').trim().split(' ').filter(Boolean);
    const first = parts[0] ? parts[0][0] : '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return `${first}${last}`.toUpperCase() || 'NA';
};

const avatarHtml = (name, image, bg, color) => {
    if (image) {
        return `<img src="${image}" style="width:24px;height:24px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;margin-right:6px;vertical-align:middle;flex-shrink:0;" />`;
    }
    return `<span style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:${bg};color:${color};font-size:10px;font-weight:600;margin-right:6px;vertical-align:middle;flex-shrink:0;">${getInitials(name)}</span>`;
};

const paymentMethodHtml = (payment) => {
    const method = payment && payment.payment_method;
    if (!method) return '<span style="color:#9ca3af;">—</span>';
    const logo = method.logo
        ? `<img src="${method.logo}" style="width:20px;height:20px;object-fit:contain;border-radius:4px;margin-right:6px;vertical-align:middle;flex-shrink:0;" />`
        : '';
    return `<div style="display:flex;align-items:center;">${logo}<span>${method.name}</span></div>`;
};

const options = ref({
    url: SALE_STATEMENTS,
    pageSize: 5,
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
            title: t('sl'),
            type: 'text',
            key: 'sl',
        },
        {
            title: t('customer'),
            type: 'custom-html',
            key: 'customer_name',
            modifier: (name, row) => {
                if (!name || name === '-') return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(name, row.customer_image, '#e0f2f1', '#00a09d');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${name}</span></div>`;
            },
        },
        {
            title: t('seller'),
            type: 'custom-html',
            key: 'seller_name',
            modifier: (name, row) => {
                if (!name || name === '-') return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(name, row.seller_image, '#e8eaf6', '#3949ab');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${name}</span></div>`;
            },
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'full_invoice_number',
        },
        {
            title: t('total_item'),
            type: 'component',
            accountAble: true,
            isCount: true,
            key: 'total_items',
            componentName: markRaw(SoldItemsCell),
        },
        {
            title: t('date'),
            type: 'object',
            key: 'date',
            modifier: (date => date ? formatDateToLocal(date) : '-'),
        },
        {
            title: t('discount'),
            type: 'object',
            key: 'discount_amount',
            modifier: (discount_amount => numberWithCurrencySymbol(discount_amount)),
        },
        {
            title: t('payment_method'),
            type: 'custom-html',
            key: 'payment',
            modifier: paymentMethodHtml,
        },
        {
            title: t('total'),
            type: 'object',
            accountAble: true,
            isCount: true,
            key: 'grand_total',
            modifier: (grand_total => numberWithCurrencySymbol(grand_total))
        },
        {
            title: t('paid_amount'),
            type: 'object',
            accountAble: true,
            isCount: true,
            key: 'paid_amount',
            modifier: (paid => numberWithCurrencySymbol(paid))
        },
        {
            title: t('due_amount'),
            type: 'object',
            accountAble: true,
            isCount: true,
            key: 'due_amount',
            modifier: (due => numberWithCurrencySymbol(due))
        },
        {
            title: t('warehouse'),
            type: 'text',
            key: 'warehouse_name',
            permission: canFilterByShop(),
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => {
                if (status === 'due')
                    return `<div class="mb-2"><span class="badge-square-light bg-danger text-capitalize">${status}</span></div>`;
                else if (status === 'partially_paid')
                    return `<div class="mb-2"><span class="badge-square-light bg-info text-capitalize">${status}</span></div>`;
                else if (status === 'return')
                    return `<div class="mb-2"><span class="badge-square-light bg-warning text-capitalize">${status}</span></div>`;
                else
                    return `<div class="mb-2"><span class="badge-square-light bg-success text-capitalize">${status}</span></div>`;
            }
        },
    ],
    actions: [],
    filters: [
        {
            label: t('customer'),
            type: 'advance-search-select',
            key: 'customer',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_CUSTOMERS}`,
            permission: true,
        },
        {
            label: t('warehouse'),
            type: 'advance-search-select',
            key: 'warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: true,
        },
        {
            label: t('seller'),
            type: 'advance-search-select',
            key: 'seller',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SELLERS}`,
            permission: true,
        },
        {
            label: t('payment_method'),
            type: 'advance-search-select',
            key: 'payment_method',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_PAYMENT_METHODS}`,
            permission: true,
        },
        {
            label: t('status'),
            type: 'checkbox',
            key: 'status',
            listKeyField: 'id',
            listValueField: 'name',
            options: [
                { id: 'paid', name: 'Paid' },
                { id: 'partially_paid', name: 'Partially Paid' },
                { id: 'due', name: 'Due' },
                { id: 'return', name: 'Return' },
            ],
            permission: true,
        },
        {
            label: t('brand'),
            type: 'advance-search-select',
            key: 'brand',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_BRAND}`,
            permission: true,
        },
    ]
})

const actionTiger = (row, action) => {

}
const currentFilters = ref({});
const onFilterChange = (filters) => {
    currentFilters.value = filters;
};

const pdfToggleableColumns = [
    { key: 'transaction_mode', label: t('transaction_mode') },
    { key: 'narration', label: t('payment_method') },
    { key: 'total_item', label: t('total_item') },
    { key: 'total_amount', label: t('total') },
    { key: 'due_amount', label: t('due_amount') },
    { key: 'received_amount', label: t('paid_amount') },
];

const isColumnsModalActive = ref(false);
const openColumnsModal = () => {
    isColumnsModalActive.value = true;
};

const generateReport = async (selectedColumns) => {
    try {
        const payload = {
            customer: currentFilters.value.customer || '',
            warehouse: currentFilters.value.warehouse || '',
            date: currentFilters.value.date || [],
            columns: selectedColumns.join(','),
        };
        const response = await Axios.post(SALE_STATEMENT_PDF, payload, {responseType: 'blob'});
        const file = new Blob([response.data], {type: 'application/pdf'});
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);
    } catch (error) {
        toast.error("Failed to generate sale statement PDF");
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('sale_statements')"/>

        <app-datatable :id="tableId"
                       :options="options"
                       :button-visible="true"
                       :add-button-text="t('report_download')"
                       @buttonAction="openColumnsModal"
                       @action="actionTiger"
                       @filter-change="onFilterChange"/>

        <statement-pdf-columns-modal v-if="isColumnsModalActive" modal-id="sale-statement-pdf-columns-modal"
            :columns="pdfToggleableColumns"
            always-included-text="SL, Date, Customer Name &amp; Invoice are always included."
            @generate="generateReport" @close="isColumnsModalActive = false"/>
    </div>
</template>
<style scoped lang="scss"></style>
