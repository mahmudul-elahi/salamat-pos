<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {
    PURCHASE_REPORTS,
    SELECTED_SUPPLIERS,
    SELECTED_SHOPS,
    SELECTED_PAYMENT_METHODS,
    SELECTED_EMPLOYEES,
    SELECTED_BRAND,
    PURCHASE_REPORT_PDF
} from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import {numberWithCurrencySymbol} from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import PurchasedItemsCell from "@/views/landlord/pos/reports/PurchasedItemsCell.vue";

const {t} = useI18n();
const {canAccess, isSupperAdmin, canFilterByShop } = usePermission();
const toast = useToast();

const tableId = ref('purchase-report-table');
const currentFilters = ref({});

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
    url: PURCHASE_REPORTS,
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
            title: t('date'),
            type: 'text',
            key: 'date',
        },
        {
            title: t('payment_method'),
            type: 'custom-html',
            key: 'payment_methods',
            modifier: (pms) => {
                if (!pms || !pms.length) return '<span style="color:#9ca3af;">—</span>';
                return pms.map(pm => {
                    const logo = pm.logo
                        ? `<img src="${pm.logo}" style="width:22px;height:22px;border-radius:50%;object-fit:contain;border:1px solid #e5e7eb;background:#f9fafb;margin-right:5px;vertical-align:middle;flex-shrink:0;" />`
                        : '';
                    return `<span style="display:inline-flex;align-items:center;margin-right:8px;">${logo}<span>${pm.name}</span></span>`;
                }).join('');
            }
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
            key: 'total_item',
            componentName: markRaw(PurchasedItemsCell),
        },
        {
            title: t('total_amount'),
            type: 'object',
            accountAble: true,
            key: 'grand_total',
            modifier: (grand_total => numberWithCurrencySymbol(grand_total))
        },
        {
            title: t('due_amount'),
            type: 'object',
            accountAble: true,
            key: 'due_amount',
            modifier: (due => numberWithCurrencySymbol(due))
        },
        {
            title: t('received_amount'),
            type: 'object',
            accountAble: true,
            key: 'received_amount',
            modifier: (received => numberWithCurrencySymbol(received))
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
        {
            title: 'Created By',
            type: 'text',
            key: 'created_by_name',
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
const downloadReport = async () => {
    // validation
    if (!currentFilters.value.warehouse) {
        toast.error(t('please_select_warehouse'));
        return;
    }

    try {
        const payload = {
            supplier: currentFilters.value.supplier || '',
            warehouse: currentFilters.value.warehouse || '',
            date: currentFilters.value.date || '',
        };

        preloader.value = true
        const response = await Axios.post(PURCHASE_REPORT_PDF, payload, {
            responseType: "blob"
        });

        const file = new Blob([response.data], {type: "application/pdf"});
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);

    } catch (error) {
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
    } finally {
        preloader.value = false
    }
};

const actionTiger = (row, action) => {
    // No actions for now
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="t('purchase_reports')"/>
        <app-datatable :id="tableId"
                       :options="options"
                       :button-visible="canAccess('download_purchase_report_pdf')"
                       :add-button-text="preloader ? 'Loading...' : t('report_download')"
                       @buttonAction="downloadReport"
                       @filter-change="onFilterChange"
                       @action="actionTiger"/>
    </div>
</template>

<style scoped lang="scss">
</style>
