<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { CreditCard, Trash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { PAYMENT_HISTORY, SELECTED_SHOPS } from "@/services/endpoints/landlord.js";
import { formatDateToLocal, numberWithCurrencySymbol } from "@/utils/helper.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import ContactProfile from "@/views/landlord/pos/HelperComponent/ContactProfile.vue";

import router from "@/router/index.js";

const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();

const { t } = useI18n();

const tableId = ref('payments-table')

const options = ref({
    url: PAYMENT_HISTORY,
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
            title: t('contact'),
            type: 'component',
            key: 'contact',
            componentName: h(ContactProfile),
        },
        {
            title: t('business_name'),
            type: 'object',
            key: 'contact',
            modifier: (contact => contact?.business_name ?? '-'),
        },
        {
            title: t('business_logo'),
            type: 'custom-html',
            key: 'contact',
            modifier: (contact => contact?.business_logo
                ? `<img src="${contact.business_logo}" style="width:40px;height:40px;border-radius:6px;object-fit:cover;border:1px solid #e5e7eb;background:#f9fafb;" />`
                : '<span style="color:#9ca3af;">—</span>'),
        },
        {
            title: t('date'),
            type: 'object',
            key: 'date',
            modifier: (date => formatDateToLocal(date))
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'full_invoice_number',
        },
        {
            title: t('type'),
            type: 'object',
            key: 'type',
            modifier: (value) => {
                if (value) {
                    return value.replace(/_/g, ' ');
                }
                return value;
            }
        },
        {
            title: t('debit'),
            type: 'object',
            key: 'debit',
            modifier: (debit => debit ? numberWithCurrencySymbol(debit) : '')
        },
        {
            title: t('credit'),
            type: 'object',
            key: 'credit',
            modifier: (credit => credit ? numberWithCurrencySymbol(credit) : '')
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('delete_payment_history') || canAccess('view_debit_voucher') || canAccess('view_credit_voucher'),
        },
    ],
    filters: [
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
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: (row => canAccess('delete_payment_history')),
        },
        {
            title: t('debit_voucher'),
            type: 'debit_voucher',
            icon: markRaw(CreditCard),
            modifier: (row => row.debit > 0 && canAccess('view_debit_voucher')),
        },
        {
            title: t('credit_voucher'),
            type: 'credit_voucher',
            icon: markRaw(CreditCard),
            modifier: (row => row.credit > 0 && canAccess('view_credit_voucher')),
        },

    ],
})

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'debit_voucher') {
        router.push(`/debit-voucher/${row.id}`)
    } else if (action.type === 'credit_voucher') {
        router.push(`/credit-voucher/${row.id}`)
    } else if (action.type === 'delete') {
        deleteUrl.value = `${PAYMENT_HISTORY}/${row.id}`
        isDeleteModal.value = true
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Payment List" />

        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />


    </div>
</template>

<style scoped lang="scss"></style>
