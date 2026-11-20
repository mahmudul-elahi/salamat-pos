<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import {
    SUPPLIER_PURCHASE_RETURN_TRANSACTION_REPORTS,
    SELECTED_PAYMENT_METHODS,
    SELECTED_SHOPS
} from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper";

const { isSupperAdmin, canFilterByShop } = usePermission();

const { t } = useI18n();
const props = defineProps({
  supplierId: {
    type: [String, Number],
    required: true
  }
})
const tableId = ref('supplier-purchase-return-payment-reports-table')

const options = ref({
    url: `${SUPPLIER_PURCHASE_RETURN_TRANSACTION_REPORTS}/${props.supplierId}`,
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
    boxShadow: false,
    columns: [
        {
            title: t('supplier_name'),
            type: 'text',
            key: 'supplier_name',
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
        },
        {
            title: t('payment_method'),
            type: 'text',
            key: 'payment_method'
        },

        {
            title: t('total_amount'),
            type: 'object',
            key: 'grand_total',
            accountAble: true,
            modifier: (grand_total => numberWithCurrencySymbol(grand_total)),
        },
        {
            title: t('due_amount'),
            type: 'object',
            key: 'due',
            accountAble: true,
            modifier: (due => numberWithCurrencySymbol(due)),
        },
        {
            title: t('paid_amount'),
            type: 'object',
            key: 'paid',
            accountAble: true,
            modifier: (paid => numberWithCurrencySymbol(paid)),
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
    ],
    filters: [
        {
            type: 'advance-search-select',
            key: 'payment_method',
            label: t('payment_method'),
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_PAYMENT_METHODS}`,
            permission: true,
        },
        {
            label: t('date'),
            type: 'date-range',
            key: 'date',
            placeholder: t('select_a_range'),
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

    ],
})


const actionTiger = (row, action) => {

}

</script>

<template>
    <app-breadcrumb :page-title="$t('supplier_purchase_return_payment_report')"/>

    <app-datatable :id="tableId" :options="options" @action="actionTiger" />
</template>

<style scoped lang="scss"></style>
