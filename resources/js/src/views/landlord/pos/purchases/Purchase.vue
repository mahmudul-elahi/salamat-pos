<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import {
    PURCHASES,
    PURCHASES_PAYMENT,
    SELECTED_SHOPS,
    SELECTED_SUPPLIERS,
    SELECTED_BRAND
} from "@/services/endpoints/landlord.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Edit, Eye, Trash } from "@iconoir/vue";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import DuePaymentModal from "@/views/landlord/common/modal/DuePaymentModal.vue";
import PurchaseSupplierAvatarComponent from "@/views/common/avatar/PurchaseSupplierAvatarComponent.vue";
import router from "@/router/index.js";

const { t } = useI18n();
const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();


const tableId = ref('purchase-table')

const options = ref({
    url: PURCHASES,
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
            componentName: markRaw(PurchaseSupplierAvatarComponent),
        },
        {
            title: t('warehouse'),
            type: 'object',
            key: 'tenant',
            modifier: (tenant) => tenant?.name
        },
        {
            title: t('date'),
            type: 'text',
            key: 'date',
        },
        {
            title: t('invoice_number'),
            type: 'custom-html',
            key: 'total_invoice',
            accountAble: true,
            isCount: true,
            modifier: ((row, value) => {
                return `${value.full_invoice_number}`
            })
        },
        {
            title: t('total_item'),
            type: 'object',
            key: 'total_item',
            accountAble: true,
            isCount: true,
            modifier: ((row, value) => {
                return `${value.total_item}`
            })
        },


        {
            title: t('total_amount'),
            type: 'object',
            key: 'grand_total',
            accountAble: true,
            modifier: (grand_total => numberWithCurrencySymbol(grand_total)),
        },
        // {
        //     title: t('due_amount'),
        //     type: 'object',
        //     key: 'due_amount',
        //     accountAble: true,
        //     modifier: (due_amount => numberWithCurrencySymbol(due_amount)),
        // },
        // {
        //     title: t('paid_amount'),
        //     type: 'object',
        //     key: 'paid_amount',
        //     accountAble: true,
        //     modifier: (paid_amount => numberWithCurrencySymbol(paid_amount)),
        // },
        // {
        //     title: t('status'),
        //     type: 'custom-html',
        //     key: 'status',
        //     modifier: (status) => {
        //         if (status === 'due')
        //             return `<div class="mb-2"><span class="badge-square-light bg-danger text-capitalize">${status}</span></div>`;
        //         else if (status === 'partially_paid')
        //             return `<div class="mb-2"><span class="badge-square-light bg-info text-capitalize">${status}</span></div>`;
        //         else
        //             return `<div class="mb-2"><span class="badge-square-light bg-success text-capitalize">${status}</span></div>`;
        //     }
        // },

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
            permission: canAccess("download_purchase_details") || canAccess("update_purchases") || canAccess("delete_purchases") || canAccess("create_purchase_payment"),
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
        //     icon: markRaw(HandCash),
        //     modifier: (row) => (row.status === 'due' || row.status === 'partially_paid') && canAccess('create_purchase_payment'),
        // },
        {
            title: t('view_details'),
            type: 'purchase_details',
            icon: markRaw(Eye),
            modifier: () => canAccess("download_purchase_details"),
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: (row => row.status !== 'paid' && row.status !== 'partially_paid' && canAccess("update_purchases") && !isSupperAdmin()),
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: (row => isSupperAdmin()),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_purchases'),
        },
        // {
        //     title: t('transactions'),
        //     type: 'transaction_list',
        //     icon: markRaw(Eye),
        //     modifier: () => true,
        // }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()


const purchasesData = ref({})
const purchasePaymentUrl = ref('')
const transactionModalActive = ref(false)
const transactionModalPurchaseId = ref(null)

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push(`/edit-purchase/${row.id}`)
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${PURCHASES}/${row.id}`
    } else if (action.type === 'payment') {
        isModalActive.value = true
        purchasesData.value = row
        purchasePaymentUrl.value = `${PURCHASES_PAYMENT}/${row.id}`
    } else if (action.type === 'purchase_details') {
        router.push(`/purchase-details/${row.id}`)
    }
    else if (action.type === 'transaction_list') {
        transactionModalPurchaseId.value = row.id
        transactionModalActive.value = true
    }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('purchase_list')" />

        <app-datatable :id="tableId" :options="options" :button-visible=false @action="actionTiger" />

        <due-payment-modal v-if="isModalActive" :table-id="tableId" modal-id="due-payment-modal"
            :row-data="purchasesData" :payment-url="purchasePaymentUrl" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <!-- <purchase-transaction-list-modal v-if="transactionModalActive" :modal-id="'purchase-transaction-list-modal'"
            :purchase-id="transactionModalPurchaseId" @close="transactionModalActive = false" /> -->

    </div>
</template>
<style scoped lang="scss"></style>
