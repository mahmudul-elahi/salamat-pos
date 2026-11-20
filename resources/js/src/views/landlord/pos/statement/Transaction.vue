<script setup>
import {h, ref} from "vue";
import {useI18n} from "vue-i18n";
import {
    TRANSACTIONS,
    SELECTED_SHOPS,
    SELECTED_CONTACTS,
    SELECTED_PAYMENT_METHODS,
    SELECTED_SELLERS,
    SELECTED_DIRECTORS,
    TRANSACTION_REPORT_PDF
} from "@/services/endpoints/landlord.js";
import {formatDateToLocal, numberWithCurrencySymbol} from "@/utils/helper.js";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import usePermission from "@/composable/permission/usePermission.js";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import LedgerPaymentMethodColumn from "@/views/landlord/common/component/LedgerPaymentMethodColumn.vue";

const {canAccess, isSupperAdmin, canFilterByShop } = usePermission();

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

const {t} = useI18n();
const toast = useToast();

const tableId = ref('transaction-table')

const options = ref({
    url: TRANSACTIONS,
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
            key: 'serial',
        },
        {
            title: t('Particular'),
            type: 'text',
            key: 'type',
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'full_invoice_number',
        },
        {
            title: t('date'),
            type: 'object',
            key: 'date',
            modifier: (date => date ? formatDateToLocal(date) : '-'),
        },
        {
            title: t('party'),
            type: 'custom-html',
            key: 'party_name',
            modifier: (party_name, row) => {
                if (!party_name) return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(party_name, row.party_image, '#e0f2f1', '#00a09d');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${party_name}</span></div>`;
            },
        },
        {
            title: t('payment_method'),
            type: 'component',
            key: 'payment_method',
            componentName: h(LedgerPaymentMethodColumn),
        },
        {
            title: t('warehouse'),
            type: 'custom-html',
            key: 'warehouse_name',
            modifier: (warehouse_name) => {
                if (!warehouse_name) return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(warehouse_name, null, '#fef3e7', '#f36f21');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${warehouse_name}</span></div>`;
            },
            permission: canFilterByShop(),
        },
        {
            title: t('note'),
            type: 'component',
            key: 'note',
            componentName: h(NoteColumn),
        },
        {
            title: t('created_by'),
            type: 'object',
            key: 'created_by_name',
            modifier: (created_by_name => created_by_name ?? '-'),
        },
        {
            title: t('seller'),
            type: 'custom-html',
            key: 'seller_name',
            modifier: (seller_name, row) => {
                if (!seller_name) return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(seller_name, row.seller_image, '#e8eaf6', '#3949ab');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${seller_name}</span></div>`;
            },
        },
        {
            title: t('director'),
            type: 'custom-html',
            key: 'director_name',
            modifier: (director_name, row) => {
                if (!director_name) return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(director_name, row.director_image, '#fce4ec', '#c2185b');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${director_name}</span></div>`;
            },
        },

        {
            title: t('total'),
            type: 'object',
            key: 'total',
            accountAble: false,
            modifier: (total => numberWithCurrencySymbol(total)),
        },
        {
            title: t('debit'),
            type: 'object',
            key: 'debit',
            accountAble: true,
            modifier: (debit => numberWithCurrencySymbol(debit)),
        },
        {
            title: t('credit'),
            type: 'object',
            key: 'credit',
            accountAble: true,
            modifier: (credit => numberWithCurrencySymbol(credit)),
        },
        {
            title: t('balance'),
            type: 'object',
            key: 'balance',
            accountAble: true,
            modifier: (balance => numberWithCurrencySymbol(balance)),
        }

    ],
    actions: [],
    filters: [
        {
            label: t('warehouse'),
            type: 'advance-search-select',
            key: 'warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
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
            label: t('director'),
            type: 'advance-search-select',
            key: 'director',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_DIRECTORS}`,
            permission: true,
        },
        {
            label: t('date'),
            type: 'date-range',
            key: 'date',
            placeholder: t('select_a_range'),
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
const preloader = ref(false)
const printTransactionStatement = async () => {

    if (!currentFilters.value.date) {
        toast.error('Please select date range');
        return;
    }

    if (isSupperAdmin && !currentFilters.value.warehouse) {
        toast.error('Please select warehouse');
        return;
    }


    const filters = {
        warehouse: currentFilters.value.warehouse || '',
        date: currentFilters.value.date || '',
    };

    try {
        preloader.value = true
        const response = await Axios.post(TRANSACTION_REPORT_PDF, filters, {responseType: 'blob'});
        const file = new Blob([response.data], {type: 'application/pdf'});
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);
    } catch (error) {
        toast.error("Failed to generate transaction statement PDF");
    } finally {
        preloader.value = false
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Transaction Reports"/>

        <app-datatable :id="tableId" :options="options"
                       :button-visible="canAccess('download_transaction_report_pdf')"
                       :add-button-text="preloader ? 'Loading...' : 'Generate Print'"
                       @action="actionTiger"
                       @button-action="printTransactionStatement"
                       @filter-change="onFilterChange"/>

    </div>
</template>
<style scoped lang="scss"></style>
