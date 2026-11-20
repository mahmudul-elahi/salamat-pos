<script setup>
import {h, ref} from "vue";
import {useI18n} from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import {
    SELECTED_EXPENSE_CATEGORY,
    SELECTED_SHOPS,
    SELECTED_PAYMENT_METHODS,
    SELECTED_EMPLOYEES,
    EXPENSE_REPORTS, EXPENSE_REPORT_PDF, EXPENSE_REPORT_PDF_STATUS, EXPENSE_REPORT_PDF_CLEANUP
} from "@/services/endpoints/landlord.js";
import {formatDateToLocal, numberWithCurrencySymbol} from "@/utils/helper.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import {useToast} from "vue-toastification"; // or your toast library
import Axios from "@/services/axios/index.js";

const {canAccess, isSupperAdmin, canFilterByShop } = usePermission();
const {t} = useI18n();
const toast = useToast();

const tableId = ref('expense-table');

// ── Track active filters from the datatable ──────────────────────────────────
const currentFilters = ref({
    category: '',
    shop: '',
    date: '',
});

const options = ref({
    url: EXPENSE_REPORTS,
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
            title: t('date'),
            type: 'text',
            key: 'date',
            modifier: (date => formatDateToLocal(date))
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
            modifier: (category => category?.name)
        },
        {
            title: t('note'),
            type: 'component',
            key: 'note',
            componentName: h(NoteColumn)
        },
        {
            title: t('amount'),
            type: 'object',
            key: 'amount',
            accountAble: true,
            modifier: (amount => numberWithCurrencySymbol(amount))
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
            title: t('warehouse'),
            type: 'object',
            key: 'tenant',
            modifier: (tenant => tenant?.name ?? '-'),
            permission: canFilterByShop(),
        },
        {
            title: 'Created By',
            type: 'object',
            key: 'created_by',
            modifier: (val) => val?.name ?? '-',
        },
    ],
    filters: [
        {
            type: 'advance-search-select',
            key: 'category',
            label: t('category'),
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_EXPENSE_CATEGORY}`,
            permission: true,
        },
        {
            label: t('warehouse_or_store_name'),
            type: 'advance-search-select',
            key: 'shop',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
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
        {
            label: t('date'),
            type: 'date-range',
            key: 'date',
            placeholder: t('select_a_range'),
            permission: true,
        },
    ],
});

const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal();

const onFilterChange = (filters) => {
    currentFilters.value = filters;
};

// ── PDF download ─────────────────────────────────────────────────────────────
// The report can span thousands of rows, so generation runs in a queued job
// (mirrors StockReport.vue): dispatch hands back a token, then this polls
// expense-report-pdf-status/{token} until it's completed/failed and downloads
// the file directly from its public disk URL.
const preloader = ref(false);
const preloaderText = ref('');

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
            const {data} = await Axios.get(`${EXPENSE_REPORT_PDF_STATUS}/${token}`);

            if (data.status === 'completed') {
                // A plain <a download> only forces a save-as dialog when the
                // browser is sure of that intent — fetching the file as a
                // blob first and downloading from an object URL guarantees
                // an actual download instead of the PDF just opening inline.
                const fileResponse = await Axios.get(data.url, {responseType: 'blob'});
                const blobUrl = URL.createObjectURL(new Blob([fileResponse.data], {type: 'application/pdf'}));
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = 'expense-report.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(blobUrl);
                preloader.value = false;

                Axios.delete(`${EXPENSE_REPORT_PDF_CLEANUP}/${token}`).catch(() => {
                });
            } else if (data.status === 'failed') {
                toast.error(data.message || t('something_went_wrong'));
                preloader.value = false;
            } else {
                pollPdfStatus(token, attempt + 1);
            }
        } catch (error) {
            toast.error(error?.response?.data?.message || t('something_went_wrong'));
            preloader.value = false;
        }
    }, POLL_INTERVAL_MS);
};

const downloadReport = async () => {
    if (isSupperAdmin() === true && !currentFilters.value.shop) {
        toast.error('Please select warehouse');
        return;
    }

    const hasAnyFilter = currentFilters.value.category || currentFilters.value.shop
        || currentFilters.value.payment_method || currentFilters.value.created_by
        || currentFilters.value.date;

    if (!hasAnyFilter) {
        toast.error('Please select at least one filter');
        return;
    }

    try {
        preloader.value = true;
        preloaderText.value = 'Generating...';

        const payload = {
            shop: currentFilters.value.shop || '',
            category: currentFilters.value.category || '',
            payment_method: currentFilters.value.payment_method || '',
            created_by: currentFilters.value.created_by || '',
            date: currentFilters.value.date || '',
        };

        const {data} = await Axios.post(EXPENSE_REPORT_PDF, payload);

        pollPdfStatus(data.token);
    } catch (error) {
        toast.error(error?.response?.data?.message || t('something_went_wrong'));
        preloader.value = false;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('expense_report')"/>

        <app-datatable
            :id="tableId"
            :options="options"
            :button-visible="canAccess('download_expense_report_pdf')"
            :add-button-text="preloader ? preloaderText : t('report_download')"
            @buttonAction="downloadReport"
            @filter-change="onFilterChange"
        />

        <app-delete-modal
            v-if="isDeleteModal"
            :table-id="tableId"
            :selected-url="deleteUrl"
            @cancelled="cancelled"
        />
    </div>
</template>
