<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import {
    PURCHASE_RETURNS,
    PURCHASE_RETURNS_PAYMENT, PURCHASE_RETURNS_TRANSACTION_DELETE,
    SELECTED_SHOPS,
    SELECTED_SUPPLIERS,
    SELECTED_BRAND
} from "@/services/endpoints/landlord.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Edit, Trash, HandCash, Eye } from "@iconoir/vue";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import DuePaymentModal from "@/views/landlord/common/modal/DuePaymentModal.vue";
import PurchaseSupplierAvatarComponent from "@/views/common/avatar/PurchaseSupplierAvatarComponent.vue";

import router from "@/router/index.js";

const { t } = useI18n();
const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();


const tableId = ref('purchase-return-table')

const options = ref({
    url: PURCHASE_RETURNS,
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
            key: 'supplier',
            componentName: h(PurchaseSupplierAvatarComponent),
        },
        {
            title: t('company_name'),
            type: 'object',
            key: 'contact',
            modifier: (contact => contact?.business_name)
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'full_invoice_number',
        },
        {
            title: t('total_item'),
            type: 'text',
            key: 'total_item',
        },
        {
            title: t('date'),
            type: 'text',
            key: 'date',
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
            key: 'due_amount',
            accountAble: true,
            modifier: (due_amount => numberWithCurrencySymbol(due_amount)),
        },
        {
            title: t('paid_amount'),
            type: 'object',
            key: 'paid_amount',
            accountAble: true,
            modifier: (paid_amount => numberWithCurrencySymbol(paid_amount)),
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
            type: 'object',
            key: 'created_by',
            modifier: (val) => val?.name ?? '-',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("download_purchase_return_details") || canAccess("update_purchase_returns") || canAccess("delete_purchase_returns") || canAccess("create_purchase_return_payment"),
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
            label: t('status'),
            type: 'search-select',
            key: 'status',
            options: [
                { id: 'paid', name: t('paid') },
                { id: 'partially_paid', name: t('partially_paid') }
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
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
            label: t('brand'),
            type: 'advance-search-select',
            key: 'brand',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_BRAND}`,
            permission: true,
        },
    ],
    actions: [
        // {
        //     title: t('payment'),
        //     type: 'payment',
        //     icon: hl(HandCash),
        //     modifier: (row) => (row.status === 'due' || row.status === 'partially_paid') && canAccess('create_purchase_return_payment'),
        // },
        // {
        //     title: t('delete_transaction'),
        //     type: 'delete_transaction',
        //     icon: h(Trash),
        //     modifier: (row => row.status !== 'due' && canAccess('delete_purchase_return_transactions')),
        // },
        {
            title: t('view_details'),
            type: 'purchase_return_details',
            icon: h(Eye),
            modifier: () => canAccess("download_purchase_return_details"),
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: h(Edit),
            modifier: (row => row.status !== 'paid' && row.status !== 'partially_paid' && canAccess("update_purchase_returns")),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: () => canAccess('delete_purchase_returns'),
        }
    ],
})

const { isModalActive, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()
const purchaseReturnData = ref({})
const purchaseReturnPaymentUrl = ref('')
const isTransactionDelete = ref(false)
const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push(`/edit-purchase-return/${row.id}`)

    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${PURCHASE_RETURNS}/${row.id}`
    } else if (action.type === 'payment') {
        isModalActive.value = true
        purchaseReturnData.value = row
        purchaseReturnPaymentUrl.value = `${PURCHASE_RETURNS_PAYMENT}/${row.id}`
    } else if (action.type === 'purchase_return_details') {
        router.push(`/purchase-return-details/${row.id}`)
    } else if (action.type === 'delete_transaction') {
        isTransactionDelete.value = true
        deleteUrl.value = `${PURCHASE_RETURNS_TRANSACTION_DELETE}/${row.id}`
    }
}

const transactionCancel = () => {
    isTransactionDelete.value = false
    deleteUrl.value = null
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('purchase_return')" />

        <app-datatable :id="tableId" :options="options" :button-visible=false @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <app-delete-modal v-if="isTransactionDelete" :table-id="tableId" :selected-url="deleteUrl"
            @cancelled="transactionCancel" />

        <due-payment-modal v-if="isModalActive" :table-id="tableId" modal-id="due-payment-modal"
            :row-data="purchaseReturnData" :payment-url="purchaseReturnPaymentUrl" @close="closeModal" />

    </div>
</template>
<style scoped lang="scss"></style>
