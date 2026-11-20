<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import {
    WITHDRAWAL_STATEMENTS,
    WITHDRAWAL_STATEMENT_PDF,
    SELECTED_SHOPS, SELECTED_DIRECTORS,
    SELECTED_INCOME_CATEGORY, SELECTED_CONTACTS, SELECTED_EMPLOYEES
} from "@/services/endpoints/landlord.js";
import { formatDateToLocal, numberWithCurrencySymbol } from "@/utils/helper.js";
import { useToast } from "vue-toastification";
import Axios from "@/services/axios/index.js";
import usePermission from "@/composable/permission/usePermission.js";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import StatementPdfColumnsModal from "@/views/landlord/pos/statement/StatementPdfColumnsModal.vue";

const { canAccess, canFilterByShop } = usePermission();
const { t } = useI18n();

const toast = useToast();
const tableId = ref('withdrawal-statement-table')

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

const options = ref({
    url: WITHDRAWAL_STATEMENTS,
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
            modifier: (category => category?.name ?? '-'),
        },
        {
            title: t('party'),
            type: 'custom-html',
            key: 'contact',
            modifier: (contact) => {
                if (!contact) return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(contact.name, contact.profile_picture, '#e0f2f1', '#00a09d');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${contact.name}</span></div>`;
            },
        },
        {
            title: 'Director',
            type: 'custom-html',
            key: 'user',
            modifier: (user) => {
                if (!user) return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(user.name, user.profile_photo, '#e8eaf6', '#3949ab');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${user.name}</span></div>`;
            },
        },
        {
            title: t('amount'),
            type: 'object',
            accountAble: true,
            isCount: true,
            key: 'amount',
            modifier: (amount => numberWithCurrencySymbol(amount))
        },
        {
            title: t('narration'),
            type: 'text',
            key: 'narration',
        },
        {
            title: t('note'),
            type: 'component',
            key: 'note',
            componentName: h(NoteColumn)
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
            fetchUrl: `${SELECTED_INCOME_CATEGORY}`,
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
            label: 'Director',
            type: 'advance-search-select',
            key: 'director',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_DIRECTORS}`,
            permission: canFilterByShop(),
        },
        {
            label: t('party'),
            type: 'advance-search-select',
            key: 'party',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_CONTACTS}`,
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
    { key: 'user', label: 'Director' },
    { key: 'narration', label: t('payment_method') },
    { key: 'amount', label: t('amount') },
];

const isColumnsModalActive = ref(false);
const openColumnsModal = () => {
    isColumnsModalActive.value = true;
};

const generateReport = async (selectedColumns) => {
    try {
        const payload = {
            warehouse: currentFilters.value.warehouse || '',
            date: currentFilters.value.date || [],
            columns: selectedColumns.join(','),
        };
        const response = await Axios.post(WITHDRAWAL_STATEMENT_PDF, payload, { responseType: 'blob' });
        const file = new Blob([response.data], { type: 'application/pdf' });
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);
    } catch (error) {
        toast.error("Failed to generate withdrawal statement PDF");
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('withdrawal_statements')" />

        <app-datatable :id="tableId" :options="options" :button-visible="canAccess('generate_withdrawal_statement_pdf')"
            :add-button-text="t('report_download')" @buttonAction="openColumnsModal" @action="actionTiger"
            @filter-change="onFilterChange" />

        <statement-pdf-columns-modal v-if="isColumnsModalActive" modal-id="withdrawal-statement-pdf-columns-modal"
            :columns="pdfToggleableColumns"
            always-included-text="SL, Date &amp; Title are always included."
            @generate="generateReport" @close="isColumnsModalActive = false"/>
    </div>
</template>
<style scoped lang="scss"></style>
