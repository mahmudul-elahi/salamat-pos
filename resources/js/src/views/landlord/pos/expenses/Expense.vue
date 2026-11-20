<script setup>
import { h, ref } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import {
    EXPENSES, SELECTED_EXPENSE_CATEGORY, EXPENSE_PAYMENT, SELECTED_SHOPS,
    EXPENSE_REPORT_PDF, EXPENSE_REPORT_PDF_STATUS, EXPENSE_REPORT_PDF_CLEANUP
} from "@/services/endpoints/landlord.js";
import { formatDateToLocal, numberWithCurrencySymbol } from "@/utils/helper.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import AttachmentsColumn from "@/views/landlord/common/component/AttachmentsColumn.vue";
import { Edit, Trash, Printer } from "@iconoir/vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import router from "@/router/index.js";
import WareHouseProfile from "@/views/landlord/pos/HelperComponent/WareHouseProfile.vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";

const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();

const { t } = useI18n();
const toast = useToast();

const tableId = ref('expense-table')
const store = useProfileStore();
const { profileData } = storeToRefs(store);
const currentFilters = ref({});

const options = ref({
    url: EXPENSES,
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
            title: t('shop_name'),
            type: 'component',
            key: 'tenant',
            componentName: h(WareHouseProfile),
        },
        {
            title: t('title'),
            type: 'text',
            key: 'title',
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'invoice_number',
        },
        {
            title: t('date'),
            type: 'text',
            key: 'date',
            modifier: (date => formatDateToLocal(date))
        },
        {
            title: t('category'),
            type: 'custom-html',
            key: 'category',
            modifier: (category) => {
                if (!category) return '<span style="color:#9ca3af;">—</span>';
                const logo = category.logo
                    ? `<img src="${category.logo}" style="width:22px;height:22px;border-radius:50%;object-fit:contain;border:1px solid #e5e7eb;background:#f9fafb;margin-right:6px;vertical-align:middle;flex-shrink:0;" />`
                    : '';
                return `<div style="display:flex;align-items:center;">${logo}<span>${category.name}</span></div>`;
            }
        },
        {
            title: t('attachments'),
            type: 'component',
            key: 'attachments',
            componentName: h(AttachmentsColumn)
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
                    ? `<img src="${pm.logo}" style="width:26px;height:26px;border-radius:50%;object-fit:contain;border:1px solid #e5e7eb;background:#f9fafb;margin-right:7px;vertical-align:middle;flex-shrink:0;" />`
                    : '';
                return `<div style="display:flex;align-items:center;">${logo}<span>${pm.name}</span></div>`;
            }
        },
        {
            title: t('amount'),
            type: 'object',
            key: 'amount',
            modifier: (amount => numberWithCurrencySymbol(amount))
        },
        {
            title: 'Created By',
            type: 'object',
            key: 'created_by',
            modifier: (val) => val?.name ?? '-',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_expenses") || canAccess("delete_expenses"),
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
            label: t('date'),
            type: 'date-range',
            key: 'date',
            placeholder: t('select_a_range'),
            permission: true,
        },

    ],
    actions: [
        {
            title: t('print'),
            type: 'print',
            icon: h(Printer),
            modifier: () => canAccess("view_expense_voucher"),
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: h(Edit),
            modifier: () => canAccess("update_expenses"),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: () => canAccess("delete_expenses"),
        }
    ],
})

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()
const isDueModalActive = ref(false)
const expensePaymentUrl = ref('')
const expenseData = ref({})
const actionTiger = (row, action) => {
    if (action.type === 'print') {
        router.push(`/expense-voucher/${row.id}`)

    } else if (action.type === 'edit') {
        router.push(`/edit-expenses/${row.id}`)

    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${EXPENSES}/${row.id}`
    } else if (action.type === 'payment') {
        isDueModalActive.value = true
        expenseData.value = row
        expensePaymentUrl.value = `${EXPENSE_PAYMENT}/${row.id}`
    }
}

const onFilterChange = (filters) => {
    currentFilters.value = filters;
};

// ── PDF download ─────────────────────────────────────────────────────────────
// Reuses the same queued job as the Expense Report page (GenerateExpenseReportPdfJob):
// dispatch hands back a token, then this polls expense-report-pdf-status/{token}
// until it's completed/failed and downloads the file directly from its public disk URL.
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
            const { data } = await Axios.get(`${EXPENSE_REPORT_PDF_STATUS}/${token}`);

            if (data.status === 'completed') {
                const fileResponse = await Axios.get(data.url, { responseType: 'blob' });
                const blobUrl = URL.createObjectURL(new Blob([fileResponse.data], { type: 'application/pdf' }));
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = 'expense-report.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(blobUrl);
                preloader.value = false;

                Axios.delete(`${EXPENSE_REPORT_PDF_CLEANUP}/${token}`).catch(() => {});
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

    const hasAnyFilter = currentFilters.value.category || currentFilters.value.shop || currentFilters.value.date;

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
            date: currentFilters.value.date || '',
        };

        const { data } = await Axios.post(EXPENSE_REPORT_PDF, payload);

        pollPdfStatus(data.token);
    } catch (error) {
        toast.error(error?.response?.data?.message || t('something_went_wrong'));
        preloader.value = false;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('expenses')" />

        <app-datatable :id="tableId" :options="options"
            :button-visible="canAccess('download_expense_report_pdf')"
            :add-button-text="preloader ? preloaderText : t('report_download')"
            @buttonAction="downloadReport" @filter-change="onFilterChange" @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />


    </div>
</template>
