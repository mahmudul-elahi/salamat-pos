<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {
    STOCK_TRANSFER_REPORTS,
    STOCK_TRANSFER_REPORT_PDF,
    SELECTED_SHOPS,
    SELECTED_EMPLOYEES,
} from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import StockTransferItemsCell from "@/views/landlord/pos/reports/StockTransferItemsCell.vue";

const {t} = useI18n();
const {canAccess, canFilterByShop} = usePermission();
const toast = useToast();

const tableId = ref('stock-transfer-report-table');
const currentFilters = ref({});

const statusBadgeClass = (status) => ({
    draft: 'bg-warning',
    approved: 'bg-success',
    cancelled: 'bg-danger',
}[status] || 'bg-secondary');

const options = ref({
    url: STOCK_TRANSFER_REPORTS,
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
            title: 'Ref #',
            type: 'text',
            key: 'reference_no',
        },
        {
            title: t('date'),
            type: 'text',
            key: 'transfer_date',
        },
        {
            title: 'From Warehouse',
            type: 'text',
            key: 'from_warehouse_name',
        },
        {
            title: 'To Warehouse',
            type: 'text',
            key: 'to_warehouse_name',
        },
        {
            title: t('total_item'),
            type: 'component',
            accountAble: true,
            isCount: true,
            key: 'total_items',
            componentName: markRaw(StockTransferItemsCell),
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => `<span class="badge-square-light ${statusBadgeClass(status)} text-capitalize">${status}</span>`,
        },
        {
            title: t('created_by'),
            type: 'text',
            key: 'created_by_name',
        },
        {
            title: 'Received By',
            type: 'text',
            key: 'received_by_name',
        },
    ],
    actions: [],
    filters: [
        {
            label: 'Warehouse',
            type: 'advance-search-select',
            key: 'warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
        },
        {
            label: 'From Warehouse',
            type: 'advance-search-select',
            key: 'from_warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
        },
        {
            label: 'To Warehouse',
            type: 'advance-search-select',
            key: 'to_warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
        },
        {
            label: t('status'),
            type: 'checkbox',
            key: 'status',
            listKeyField: 'id',
            listValueField: 'name',
            options: [
                {id: 'draft', name: 'Draft'},
                {id: 'approved', name: 'Approved'},
                {id: 'cancelled', name: 'Cancelled'},
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
            label: 'Received By',
            type: 'advance-search-select',
            key: 'received_by',
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
})

const onFilterChange = (filters) => {
    currentFilters.value = filters;
};

const preloader = ref(false)
const downloadReport = async () => {
    try {
        const payload = {
            warehouse: currentFilters.value.warehouse || '',
            from_warehouse: currentFilters.value.from_warehouse || '',
            to_warehouse: currentFilters.value.to_warehouse || '',
            status: currentFilters.value.status || '',
            created_by: currentFilters.value.created_by || '',
            received_by: currentFilters.value.received_by || '',
            date: currentFilters.value.date || '',
        };

        preloader.value = true
        const response = await Axios.post(STOCK_TRANSFER_REPORT_PDF, payload, {
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
        <app-breadcrumb :page-title="t('stock_transfer_reports')"/>
        <app-datatable :id="tableId"
                       :options="options"
                       :button-visible="canAccess('pdf_stock_transfer_reports')"
                       :add-button-text="preloader ? 'Loading...' : t('report_download')"
                       @buttonAction="downloadReport"
                       @filter-change="onFilterChange"
                       @action="actionTiger"/>
    </div>
</template>

<style scoped lang="scss">
</style>
