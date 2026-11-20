<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import {
    EXPENSE_STATEMENTS,
    EXPENSE_STATEMENT_PDF,
    SELECTED_EXPENSE_CATEGORY,
    SELECTED_SHOPS,
    SELECTED_PAYMENT_METHODS,
    SELECTED_EMPLOYEES
} from "@/services/endpoints/landlord.js";
import {formatDateToLocal, numberWithCurrencySymbol} from "@/utils/helper.js";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import usePermission from "@/composable/permission/usePermission.js";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import StatementPdfColumnsModal from "@/views/landlord/pos/statement/StatementPdfColumnsModal.vue";

const {canAccess, canFilterByShop} = usePermission();
const { t } = useI18n();
const toast = useToast();

const tableId = ref('expense-statement-table')

const options = ref({
    url: EXPENSE_STATEMENTS,
    pageSize: 5,
    rowLimit: 15,
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
            title: t('date'),
            type: 'object',
            key: 'date',
            modifier: (date => date ? formatDateToLocal(date) : '-'),
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'full_invoice_number',
        },
        {
            title: t('title'),
            type: 'text',
            key: 'title',
        },
        {
            title: t('category'),
            type: 'object',
            key: 'category',
            modifier: (category => category ? category.name : '-')
        },
        {
            title: t('note'),
            type: 'component',
            key: 'note',
            componentName: h(NoteColumn)
        },
        {
            title: t('payment_method'),
            type: 'custom-html',
            key: 'payment_method',
            modifier: (pm) => {
                if (!pm) return '<span style="color:#9ca3af;">—</span>';
                const logo = pm.logo
                    ? `<img src="${pm.logo}" style="width:22px;height:22px;border-radius:50%;object-fit:contain;border:1px solid #e5e7eb;background:#f9fafb;margin-right:6px;vertical-align:middle;flex-shrink:0;" />`
                    : '';
                return `<div style="display:flex;align-items:center;">${logo}<span>${pm.name}</span></div>`;
            }
        },
        {
            title: t('amount'),
            type: 'object',
            key: 'grand_total',
            accountAble: true,
            isCount: true,
            modifier: (amount => numberWithCurrencySymbol(amount)),
        },
        {
            title: t('paid_amount'),
            type: 'object',
            key: 'paid_amount',
            accountAble: true,
            isCount: true,
            modifier: (paid => numberWithCurrencySymbol(paid)),
        },
        {
            title: t('due_amount'),
            type: 'object',
            key: 'due_amount',
            accountAble: true,
            isCount: true,
            modifier: (due => numberWithCurrencySymbol(due)),
        },
        {
            title: t('warehouse'),
            type: 'text',
            key: 'warehouse_name',
            permission: canFilterByShop(),
        },
        {
            title: 'Created By',
            type: 'text',
            key: 'created_by_name',
        },
    ],
    actions: [],
    filters: [
        {
            label: t('category'),
            type: 'advance-search-select',
            key: 'category',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_EXPENSE_CATEGORY}`,
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
            label: t('created_by'),
            type: 'advance-search-select',
            key: 'created_by',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_EMPLOYEES}`,
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
    { key: 'category', label: t('category') },
    { key: 'payment_method', label: t('payment_method') },
    { key: 'amount', label: t('amount') },
    { key: 'paid_amount', label: t('paid_amount') },
    { key: 'due_amount', label: t('due_amount') },
];

const isColumnsModalActive = ref(false);
const openColumnsModal = () => {
    isColumnsModalActive.value = true;
};

const generateReport = async (selectedColumns) => {
    try {
        const payload = {
            category: currentFilters.value.category || '',
            warehouse: currentFilters.value.warehouse || '',
            date: currentFilters.value.date || [],
            columns: selectedColumns.join(','),
        };
        const response = await Axios.post(EXPENSE_STATEMENT_PDF, payload, { responseType: 'blob' });
        const file = new Blob([response.data], { type: 'application/pdf' });
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);
    } catch (error) {
        toast.error("Failed to generate expense statement PDF");
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('expense_statements')"/>

        <app-datatable :id="tableId"
                       :options="options"
                       :button-visible="true"
                       :add-button-text="t('report_download')"
                       @buttonAction="openColumnsModal"
                       @action="actionTiger"
                       @filter-change="onFilterChange" />

        <statement-pdf-columns-modal v-if="isColumnsModalActive" modal-id="expense-statement-pdf-columns-modal"
            :columns="pdfToggleableColumns"
            always-included-text="SL, Date &amp; Title are always included."
            @generate="generateReport" @close="isColumnsModalActive = false"/>
    </div>
</template>
<style scoped lang="scss"></style>
