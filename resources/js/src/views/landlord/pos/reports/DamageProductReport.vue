<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {
    DAMAGE_PRODUCT_REPORTS,
    DAMAGE_PRODUCT_REPORT_PDF,
    SELECTED_SHOPS,
    SELECTED_EMPLOYEES,
} from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import DamageProductItemsCell from "@/views/landlord/pos/reports/DamageProductItemsCell.vue";

const {t} = useI18n();
const {canAccess, canFilterByShop} = usePermission();
const toast = useToast();

const tableId = ref('damage-product-report-table');
const currentFilters = ref({});

const statusBadgeClass = (status) => ({
    draft: 'bg-warning',
    approved: 'bg-success',
    cancelled: 'bg-danger',
}[status] || 'bg-secondary');

const options = ref({
    url: DAMAGE_PRODUCT_REPORTS,
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
            key: 'damage_date',
        },
        {
            title: 'Warehouse',
            type: 'text',
            key: 'warehouse_name',
        },
        {
            title: t('total_item'),
            type: 'component',
            accountAble: true,
            isCount: true,
            key: 'total_items',
            componentName: markRaw(DamageProductItemsCell),
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
            title: 'Approved By',
            type: 'text',
            key: 'approved_by_name',
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
            label: 'Approved By',
            type: 'advance-search-select',
            key: 'approved_by',
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
            status: currentFilters.value.status || '',
            created_by: currentFilters.value.created_by || '',
            approved_by: currentFilters.value.approved_by || '',
            date: currentFilters.value.date || '',
        };

        preloader.value = true
        const response = await Axios.post(DAMAGE_PRODUCT_REPORT_PDF, payload, {
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
        <app-breadcrumb :page-title="t('damage_product_reports')"/>
        <app-datatable :id="tableId"
                       :options="options"
                       :button-visible="canAccess('pdf_damage_product_reports')"
                       :add-button-text="preloader ? 'Loading...' : t('report_download')"
                       @buttonAction="downloadReport"
                       @filter-change="onFilterChange"
                       @action="actionTiger"/>
    </div>
</template>

<style scoped lang="scss">
</style>
