<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {
    PURCHASE_RETURN_STATEMENT_PDF,
    PURCHASE_RETURN_STATEMENTS,
    SELECTED_SHOPS,
    SELECTED_SUPPLIERS,
    SELECTED_PAYMENT_METHODS,
    SELECTED_BRAND
} from "@/services/endpoints/landlord.js";
import {formatDateToLocal, numberWithCurrencySymbol} from "@/utils/helper.js";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import usePermission from "@/composable/permission/usePermission.js";
import PurchaseReturnedItemsCell from "@/views/landlord/pos/reports/PurchaseReturnedItemsCell.vue";
import StatementPdfColumnsModal from "@/views/landlord/pos/statement/StatementPdfColumnsModal.vue";

const {canAccess, canFilterByShop} = usePermission();

const {t} = useI18n();
const toast = useToast();

const tableId = ref('purchase-return-statement-table')

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
    url: PURCHASE_RETURN_STATEMENTS,
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
            title: t('supplier'),
            type: 'custom-html',
            key: 'supplier_name',
            modifier: (name, row) => {
                if (!name || name === '-') return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(name, row.supplier_image, '#e0f2f1', '#00a09d');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${name}</span></div>`;
            },
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'invoice_full_number',
        },
        {
            title: t('total_item'),
            type: 'component',
            key: 'total_items',
            accountAble: true,
            isCount: true,
            componentName: markRaw(PurchaseReturnedItemsCell),
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
            key: 'grand_total',
            accountAble: true,
            modifier: (grand_total => numberWithCurrencySymbol(grand_total)),
        },
        {
            title: t('paid_amount'),
            type: 'object',
            key: 'paid_amount',
            accountAble: true,
            modifier: (paid_amount => numberWithCurrencySymbol(paid_amount)),
        },
        {
            title: t('due_amount'),
            type: 'object',
            key: 'due_amount',
            accountAble: true,
            modifier: (due_amount => numberWithCurrencySymbol(due_amount)),
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
                else
                    return `<div class="mb-2"><span class="badge-square-light bg-success text-capitalize">${status}</span></div>`;
            }
        },

    ],
    actions: [],
    filters: [
        {
            label: t('supplier'),
            type: 'advance-search-select',
            key: 'supplier',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SUPPLIERS}`,
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
            supplier: currentFilters.value.supplier || '',
            warehouse: currentFilters.value.warehouse || '',
            date: currentFilters.value.date || [],
            columns: selectedColumns.join(','),
        };
        const response = await Axios.post(PURCHASE_RETURN_STATEMENT_PDF, payload, {responseType: 'blob'});
        const file = new Blob([response.data], {type: 'application/pdf'});
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);
    } catch (error) {
        toast.error("Failed to generate purchase return statement PDF");
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('purchase_return_statements')"/>

        <app-datatable :id="tableId"
                       :options="options"
                       :button-visible="true"
                       :add-button-text="t('generate_print')"
                       @buttonAction="openColumnsModal"
                       @action="actionTiger"
                       @filter-change="onFilterChange"/>

        <statement-pdf-columns-modal v-if="isColumnsModalActive" modal-id="purchase-return-statement-pdf-columns-modal"
            :columns="pdfToggleableColumns"
            always-included-text="SL, Date, Supplier Name &amp; Invoice are always included."
            @generate="generateReport" @close="isColumnsModalActive = false"/>

    </div>
</template>
<style scoped lang="scss"></style>
