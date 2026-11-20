<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { SALES_RETURNS, SALE_RETURNS_PAYMENT, SELECTED_SHOPS, SELECTED_CUSTOMERS, SELECTED_BRAND } from "@/services/endpoints/landlord.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { Edit, Trash, Eye } from "@iconoir/vue";
import DuePaymentModal from "@/views/landlord/common/modal/DuePaymentModal.vue";
import router from "@/router/index.js";
import ContactProfile from "@/views/landlord/pos/HelperComponent/ContactProfile.vue";

const { t } = useI18n();
const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();


const tableId = ref('sale-table')

const options = ref({
    url: SALES_RETURNS,
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
            title: t('customer'),
            type: 'component',
            key: 'contact',
            componentName: h(ContactProfile),
        },
        {
            title: t('warehouse'),
            type: 'object',
            key: 'tenant',
            modifier: (tenant => tenant?.name ?? '-'),
            permission: isSupperAdmin()
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
            title: t('total_item'),
            type: 'text',
            key: 'total_items',
            isCount: true,
            accountAble: true,
        },

        {
            title: t('total_amount'),
            type: 'object',
            key: 'grand_total',
            accountAble: true,
            modifier: (grand_total => numberWithCurrencySymbol(grand_total)),
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
            permission: canAccess("download_sale_return_details") || canAccess("update_sale_returns") || canAccess("delete_sale_returns") || canAccess("create_sale_return_payment"),
        },
    ],
    filters: [
        {
            label: t('customer'),
            type: 'advance-search-select',
            key: 'customer',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_CUSTOMERS}`,
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
        //     modifier: (row) => (row.status === 'due' || row.status === 'partially_paid') && canAccess('create_sale_return_payment'),
        // },
        {
            title: t('view_details'),
            type: 'sale_return_details',
            icon: h(Eye),
            modifier: () => canAccess("download_sale_return_details"),
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: h(Edit),
            modifier: (row => row.status !== 'paid' && row.status !== 'partially_paid' && canAccess("update_sale_returns")),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: () => canAccess('delete_sale_returns'),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const saleReturnData = ref({})
const saleReturnPaymentUrl = ref('')
const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push(`/edit-sale-return/${row.id}`)

    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${SALES_RETURNS}/${row.id}`
    } else if (action.type === 'payment') {
        isModalActive.value = true
        saleReturnData.value = row
        saleReturnPaymentUrl.value = `${SALE_RETURNS_PAYMENT}/${row.id}`
    } else if (action.type === 'sale_return_details') {
        router.push(`/sale-return-details/${row.id}`)
    }
}
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('sales_return')" />

        <app-datatable :id="tableId" :options="options" :button-visible=false @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
        <due-payment-modal v-if="isModalActive" :table-id="tableId" modal-id="due-payment-modal"
            :row-data="saleReturnData" :payment-url="saleReturnPaymentUrl" @close="closeModal" />

    </div>
</template>
<style scoped lang="scss"></style>
