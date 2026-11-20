<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import {
    SELECTED_SHOPS, DAILY_CLOSING_BALANCE,
    DAILY_CLOSING_BALANCE_PDF_DOWNLOAD,
    SELECTED_CONTACTS, SELECTED_SELLERS, SELECTED_PAYMENT_METHODS
} from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import DailyClosingInvoiceCell from "@/views/landlord/pos/statement/DailyClosingInvoiceCell.vue";

const { t } = useI18n();
const toast = useToast();


const tableId = ref('daily-closing-table')

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

const partyHtml = (name, row) => {
    if (!name) return '<span style="color:#9ca3af;">—</span>';
    const avatar = avatarHtml(name, row.contact_image, '#e0f2f1', '#00a09d');
    return `<div style="display:flex;align-items:center;">${avatar}<span>${name}</span></div>`;
};

const sellerHtml = (name, row) => {
    if (!name) return '<span style="color:#9ca3af;">—</span>';
    const avatar = avatarHtml(name, row.seller_image, '#e8eaf6', '#3949ab');
    return `<div style="display:flex;align-items:center;">${avatar}<span>${name}</span></div>`;
};

const paymentMethodHtml = (method, row) => {
    if (!method) return '<span style="color:#9ca3af;">—</span>';
    const logo = row.payment_method_logo
        ? `<img src="${row.payment_method_logo}" style="width:20px;height:20px;object-fit:contain;border-radius:4px;margin-right:6px;vertical-align:middle;flex-shrink:0;" />`
        : '';
    return `<div style="display:flex;align-items:center;">${logo}<span>${method}</span></div>`;
};

const statusHtml = (status) => {
    if (!status) return '<span style="color:#9ca3af;">—</span>';
    if (status === 'due')
        return `<span class="badge-square-light bg-danger text-capitalize">${status}</span>`;
    if (status === 'partially_paid')
        return `<span class="badge-square-light bg-info text-capitalize">${status}</span>`;
    if (status === 'return')
        return `<span class="badge-square-light bg-warning text-capitalize">${status}</span>`;
    return `<span class="badge-square-light bg-success text-capitalize">${status}</span>`;
};

const options = ref({
    url: DAILY_CLOSING_BALANCE,
    pageSize: 5,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    hideTotal: true,
    showSearch: true,
    showFilter: isSupperAdmin(),
    columns: [
        {
            title: t('sl'),
            type: 'text',
            key: 'sl',
        },
        {
            title: t('particular'),
            type: 'text',
            key: 'type',
        },
        {
            title: t('invoice_number'),
            type: 'component',
            key: 'full_invoice_number',
            componentName: markRaw(DailyClosingInvoiceCell),
        },
        {
            title: t('party'),
            type: 'custom-html',
            key: 'contact_name',
            modifier: partyHtml,
        },
        {
            title: t('seller'),
            type: 'custom-html',
            key: 'seller_name',
            modifier: sellerHtml,
        },
        {
            title: t('payment_method'),
            type: 'custom-html',
            key: 'payment_method',
            modifier: paymentMethodHtml,
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: statusHtml,
        },
        {
            title: t('created_by'),
            type: 'text',
            key: 'created_by_name',
        },
        {
            title: t('warehouse'),
            type: 'text',
            key: 'tenant_name',
            permission: canFilterByShop(),
        },
        {
            title: t('total'),
            type: 'object',
            key: 'total',
            modifier: (total => numberWithCurrencySymbol(total))
        },
        {
            title: t('debit'),
            type: 'object',
            key: 'debit',
            modifier: (debit => numberWithCurrencySymbol(debit))
        },
        {
            title: t('credit'),
            type: 'object',
            key: 'credit',
            modifier: (credit => numberWithCurrencySymbol(credit))
        },
        {
            title: t('balance'),
            type: 'object',
            key: 'balance',
            accountAble: true,
            modifier: (balance => numberWithCurrencySymbol(balance))
        },

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
    ]
})

const actionTiger = (row, action) => { }

const currentFilters = ref({});
const onFilterChange = (filters) => {
    currentFilters.value = filters;
};
const preloader = ref(false);
const buttonAction = () => {
    preloader.value = true;
    const filters = {
        warehouse: currentFilters.value.warehouse || '',
        date: currentFilters.value.date || '',
        party: currentFilters.value.party || '',
        seller: currentFilters.value.seller || '',
        payment_method: currentFilters.value.payment_method || '',
        status: currentFilters.value.status || '',
    };

    const queryString = new URLSearchParams(filters).toString();

    Axios.get(`${DAILY_CLOSING_BALANCE_PDF_DOWNLOAD}?${queryString}`, { responseType: 'blob' })
        .then(response => {
            const file = new Blob([response.data], { type: 'application/pdf' });
            const fileURL = URL.createObjectURL(file);
            window.open(fileURL);
        })
        .catch(error => {
            if (error.response && error.response.data instanceof Blob) {
                const reader = new FileReader();
                reader.onload = () => {
                    try {
                        const errorData = JSON.parse(reader.result);
                        toast.error(errorData.message || t('something_went_wrong'));
                    } catch {
                        toast.error(t('something_went_wrong'));
                    }
                };
                reader.readAsText(error.response.data);
            } else {
                toast.error(error?.response?.data?.message || t('something_went_wrong'));
            }
        })
        .finally(() => {
            preloader.value = false;
        });
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('daily_closing_balance')" />

        <app-datatable :id="tableId" :options="options" :button-visible="canAccess('download_daily_closing_balance')"
            :add-button-text="preloader ? 'Loading...' : 'Daily Closing PDF Report'" @action="actionTiger"
            @button-action="buttonAction" @filter-change="onFilterChange" />

        <!-- <app-datatable :id="tableId" :options="options" @action="actionTiger" @button-action="buttonAction" /> -->
    </div>
</template>
<style scoped lang="scss"></style>
