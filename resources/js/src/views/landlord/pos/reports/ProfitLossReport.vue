<script setup>
import { h, ref } from "vue";
import { useI18n } from "vue-i18n";
import {
    WITHDRAWAL_REPORTS,
    SELECTED_SHOPS,
    WITHDRAWAL_REPORT_PDF, SELECTED_INVESTMENT_CATEGORY, SELECTED_INCOME_CATEGORY, PROFIT_LOSS_REPORTS,
    SELECTED_PRODUCT, SELECTED_SELLERS, SELECTED_CATEGORY, SELECTED_BRAND, SELECTED_PAYMENT_METHODS,
    PROFIT_LOSS_PDF, PROFIT_LOSS_PDF_STATUS, PROFIT_LOSS_PDF_CLEANUP
} from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import ProfitLossProductColumn from "./ProfitLossProductColumn.vue";
import ProfitLossSellerColumn from "./ProfitLossSellerColumn.vue";
import ProfitLossCreatedByColumn from "./ProfitLossCreatedByColumn.vue";
import ProfitLossPdfColumnsModal from "./ProfitLossPdfColumnsModal.vue";

const { t } = useI18n();
const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();
const toast = useToast();

const paymentMethodInitials = (name) => {
    const trimmed = (name || '').trim();
    if (!trimmed) return 'NA';
    const first = trimmed[0];
    const last = trimmed[trimmed.length - 1];
    return `${first}${last}`.toUpperCase();
};

const paymentMethodLogoHtml = (pm) => {
    if (pm.logo) {
        return `<img src="${pm.logo}" style="width:22px;height:22px;border-radius:50%;object-fit:contain;border:1px solid #e5e7eb;background:#f9fafb;margin-right:5px;vertical-align:middle;flex-shrink:0;" />`;
    }
    return `<span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#e0f2f1;color:#00a09d;font-size:10px;font-weight:600;margin-right:5px;vertical-align:middle;flex-shrink:0;">${paymentMethodInitials(pm.name)}</span>`;
};

const tableId = ref('profit-loss-report-table');
const currentFilters = ref({});

const options = ref({
    url: PROFIT_LOSS_REPORTS,
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
            title: t('sl'),
            type: 'text',
            key: 'sl',
        },
        {
            title: t('date'),
            type: 'text',
            key: 'date',
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'invoice_number',
        },
        {
            title: t('product'),
            type: 'component',
            key: 'product',
            componentName: h(ProfitLossProductColumn),
        },
        {
            title: t('seller'),
            type: 'component',
            key: 'seller',
            componentName: h(ProfitLossSellerColumn),
        },
        {
            title: t('created_by'),
            type: 'component',
            key: 'created_by',
            componentName: h(ProfitLossCreatedByColumn),
        },
        {
            title: t('payment_method'),
            type: 'custom-html',
            key: 'payment_method',
            modifier: (methods) => {
                if (!methods || !methods.length) return '-';
                const rows = methods.map(pm => {
                    const logo = paymentMethodLogoHtml(pm);
                    return `<div style="display:flex;align-items:center;">${logo}<span>${pm.name}</span></div>`;
                }).join('');
                return `<div style="display:flex;flex-direction:column;gap:4px;">${rows}</div>`;
            },
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => {
                if (!status) return '-';
                if (status === 'due')
                    return `<span class="badge-square-light bg-danger text-capitalize">${status}</span>`;
                else if (status === 'partially_paid')
                    return `<span class="badge-square-light bg-info text-capitalize">${status}</span>`;
                else
                    return `<span class="badge-square-light bg-success text-capitalize">${status}</span>`;
            }
        },
        {
            title: 'Total Sale',
            type: 'object',
            accountAble: true,
            key: 'total_sale_price',
            modifier: (total_sale_price => numberWithCurrencySymbol(total_sale_price))
        },
        {
            title: t('total_purchase'),
            type: 'object',
            accountAble: true,
            key: 'total_purchase_price',
            modifier: (total_purchase_price => numberWithCurrencySymbol(total_purchase_price))
        },
        {
            title: t('profit_loss'),
            type: 'object',
            accountAble: true,
            key: 'profit_loss',
            modifier: (profit_loss => numberWithCurrencySymbol(profit_loss))
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
            label: t('product'),
            type: 'advance-search-select',
            key: 'product',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_PRODUCT}`,
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
            label: t('category'),
            type: 'advance-search-select',
            key: 'category',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_CATEGORY}`,
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
            fetchUrl: `${SELECTED_SELLERS}`,
            permission: true,
        },
        {
            label: t('status'),
            type: 'search-select',
            key: 'status',
            options: [
                { id: 'paid', name: t('paid') },
                { id: 'partially_paid', name: t('partially_paid') },
                { id: 'due', name: t('due') },
                { id: 'return', name: t('return') },
            ],
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

const onFilterChange = (filters) => {
    currentFilters.value = filters;
};

const preloader = ref(false)
const preloaderText = ref('');


const actionTiger = (row, action) => {
    // No actions for now
}

const POLL_INTERVAL_MS = 2000;
const MAX_POLL_ATTEMPTS = 150; // ~5 minutes

const pollPdfStatus = (token, attempt = 0) => {
    if (attempt >= MAX_POLL_ATTEMPTS) {
        toast.error('Report generation is taking too long. Please try again later.');
        preloader.value = false;
        return;
    }

    setTimeout(async () => {
        try {
            const { data } = await Axios.get(`${PROFIT_LOSS_PDF_STATUS}/${token}`);

            if (data.status === 'completed') {
                const fileResponse = await Axios.get(data.url, { responseType: 'blob' });
                const blobUrl = URL.createObjectURL(new Blob([fileResponse.data], { type: 'application/pdf' }));
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = 'profit-loss-report.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(blobUrl);
                preloader.value = false;

                Axios.delete(`${PROFIT_LOSS_PDF_CLEANUP}/${token}`).catch(() => { });
            } else if (data.status === 'failed') {
                toast.error(data.message || 'Something went wrong');
                preloader.value = false;
            } else {
                pollPdfStatus(token, attempt + 1);
            }
        } catch (error) {
            toast.error(error?.response?.data?.message || 'Something went wrong');
            preloader.value = false;
        }
    }, POLL_INTERVAL_MS);
};

const isColumnsModalActive = ref(false);

const printProfitLossReport = () => {
    const hasAnyFilter = currentFilters.value.warehouse || currentFilters.value.product
        || currentFilters.value.seller || currentFilters.value.category
        || currentFilters.value.brand || currentFilters.value.payment_method
        || currentFilters.value.created_by || currentFilters.value.status
        || currentFilters.value.date;

    if (!hasAnyFilter) {
        toast.error('Please select at least one filter');
        return;
    }

    isColumnsModalActive.value = true;
};

const generateReport = async (selectedColumns) => {
    try {
        const payload = {
            warehouse: currentFilters.value.warehouse || '',
            product: currentFilters.value.product || '',
            seller: currentFilters.value.seller || '',
            category: currentFilters.value.category || '',
            brand: currentFilters.value.brand || '',
            payment_method: currentFilters.value.payment_method || '',
            created_by: currentFilters.value.created_by || '',
            status: currentFilters.value.status || '',
            date: currentFilters.value.date || '',
            columns: selectedColumns.join(','),
        };

        preloader.value = true;
        preloaderText.value = 'Generating...';

        const { data } = await Axios.post(PROFIT_LOSS_PDF, payload);

        pollPdfStatus(data.token);
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Something went wrong');
        preloader.value = false;
    }
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Profit Loss Report" />

        <app-datatable :id="tableId" :options="options" :button-visible="canAccess('generate_profit_loss_pdf')"
            :add-button-text="preloader ? preloaderText : $t('generate_print')" @buttonAction="printProfitLossReport"
            @filter-change="onFilterChange" @action="actionTiger" />

        <profit-loss-pdf-columns-modal v-if="isColumnsModalActive" modal-id="profit-loss-pdf-columns-modal"
            @generate="generateReport" @close="isColumnsModalActive = false" />
    </div>
</template>

<style scoped lang="scss"></style>
