<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {Eye} from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import {SUPPLIER_REPORTS, SELECTED_SUPPLIERS, SELECTED_SHOPS} from "@/services/endpoints/landlord.js";
import {numberWithCurrencySymbol} from "@/utils/helper";
import router from "@/router/index.js";
import SupplierAvatarComponent from "@/views/common/avatar/SupplierAvatarComponent.vue";

const {canAccess, isSupperAdmin, canFilterByShop } = usePermission();

const {t} = useI18n();

const tableId = ref('supplier-reports-table')

const options = ref({
    url: SUPPLIER_REPORTS,
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
            title: t('supplier'),
            type: 'component',
            key: 'id',
            componentName: markRaw(SupplierAvatarComponent),
        },
        {
            title: t('total_purchase'),
            type: 'text',
            key: 'total_purchase',
        },
        {
            title: t('total_amount'),
            type: 'object',
            key: 'total_amount',
            accountAble: true,
            modifier: (total_amount => numberWithCurrencySymbol(total_amount))
        },
        {
            title: t('paid'),
            type: 'object',
            key: 'purchase_paid_amount',
            accountAble: true,
            modifier: (purchase_paid_amount => numberWithCurrencySymbol(purchase_paid_amount))
        },
        {
            title: t('due'),
            type: 'object',
            key: 'purchase_due_amount',
            accountAble: true,
            modifier: (purchase_due_amount => numberWithCurrencySymbol(purchase_due_amount))
        },
        {
            title: t('total_purchase_return_due'),
            type: 'object',
            key: 'total_purchase_return_due_amount',
            accountAble: true,
            modifier: (total_purchase_return_due_amount => numberWithCurrencySymbol(total_purchase_return_due_amount))
        },
        {
            title: t('total_purchase_return_paid_amount'),
            type: 'object',
            key: 'total_purchase_return_paid_amount',
            accountAble: true,
            modifier: (total_purchase_return_paid_amount => numberWithCurrencySymbol(total_purchase_return_paid_amount))
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("view_supplier_purchase_reports") || canAccess("view_supplier_purchase_return_reports") || canAccess("view_supplier_purchase_transaction_reports") || canAccess("view_supplier_purchase_return_transaction_reports") || canAccess("view_supplier_ledger_reports")
        },
    ],
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
            label: t('warehouse_or_store_name'),
            type: 'advance-search-select',
            key: 'shop',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
        },
    ],
    actions: [
        {
            title: t('view_details'),
            type: 'supplier_report_details',
            icon: markRaw(Eye),
            modifier: () => canAccess("view_supplier_purchase_reports") || canAccess("view_supplier_purchase_return_reports") || canAccess("view_supplier_purchase_transaction_reports") || canAccess("view_supplier_purchase_return_transaction_reports") || canAccess("view_supplier_ledger_reports"),
        },
    ],
})


const actionTiger = (row, action) => {
    if (action.type === 'supplier_report_details') {
        router.push(`/supplier-report-details/${row.id}`)
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('supplier_reports')">
        </app-breadcrumb>

        <app-datatable :id="tableId"
                       :options="options"
                       @action="actionTiger"/>


    </div>
</template>

<style scoped lang="scss"></style>
