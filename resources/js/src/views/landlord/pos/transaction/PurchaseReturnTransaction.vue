<script setup>
import {ref} from "vue";
import {useI18n} from "vue-i18n";
import {PURCHASE_RETURN_TRANSACTIONS} from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import {numberWithCurrencySymbol} from "@/utils/helper.js";

const {t} = useI18n();
const {canAccess} = usePermission();


const tableId = ref('purchase-table')

const options = ref({
    url: PURCHASE_RETURN_TRANSACTIONS,
    pageSize: 5,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: false,
    columns: [
        {
            title: t('supplier'),
            type: 'object',
            key: 'supplier',
            modifier: (supplier) => supplier?.name
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'full_invoice_number',
        },
        {
            title: t('date'),
            type: 'text',
            key: 'date',
        },
        {
            title: t('payment_method'),
            type: 'object',
            key: 'payment_method',
            modifier: (payment_method) => payment_method?.name
        },

        {
            title: t('total_amount'),
            type: 'object',
            key: 'grand_total',
            modifier: (grand_total => numberWithCurrencySymbol(grand_total)),
        },
        {
            title: t('due_amount'),
            type: 'object',
            key: 'due_amount',
            modifier: (due_amount => numberWithCurrencySymbol(due_amount)),
        },
        {
            title: t('refund_received'),
            type: 'object',
            key: 'amount',
            modifier: (amount => numberWithCurrencySymbol(amount)),
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
    actions: [],
})

const actionTiger = (row, action) => {

}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('purchase_return_transactions')"/>

        <app-datatable :id="tableId"
                       :options="options"
                       :button-visible=false
                       @action="actionTiger"/>

    </div>
</template>
<style scoped lang="scss"></style>
