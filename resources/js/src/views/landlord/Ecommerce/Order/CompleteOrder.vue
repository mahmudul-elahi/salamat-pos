<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { formatDateToLocal, numberWithCurrencySymbol } from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Eye, DoubleCheck } from "@iconoir/vue";
import { ORDERS } from "@/services/endpoints/Ecommerce.js";
import CustomerProfile from "@/views/landlord/Ecommerce/Order/Component/CustomerProfile.vue";
import router from "@/router/index.js";
import useOrderStatus from "@/composable/ecommerce/useOrderStatus.js";
import OrderStatusModal from "@/views/landlord/Ecommerce/Order/Component/OrderStatusModal.vue";
const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('complete-orders-table')

const options = ref({
    url: `${ORDERS}?status=complete`,
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
            title: t('customer'),
            type: 'component',
            key: 'id',
            componentName: markRaw(CustomerProfile),
        },
        

        {
            title: t('pm'),
            type: 'text',
            key: 'payment_method',
        },
        {
            title: t('discount_amount'),
            type: 'object',
            key: 'items',
            modifier: (items) => {
                if (!items || items.length === 0) return '-';

                // Sum up discount_amounts; convert strings to numbers
                const totalDiscount = items.reduce((sum, item) => {
                    return sum + parseFloat(item.discount_amount || 0);
                }, 0);

                return totalDiscount > 0 ? numberWithCurrencySymbol(totalDiscount) : '-';
            }
        },
        {
            title: t('grand_total'),
            type: 'object',
            key: 'grand_total',
            modifier: (amount => amount ? numberWithCurrencySymbol(amount) : '-')
        },
        {
            title: t('payable_amount'),
            type: 'object',
            key: 'payable_amount',
            modifier: (amount => amount ? numberWithCurrencySymbol(amount) : '-')
        },
        {
            title: t('due'),
            type: 'object',
            key: 'due_amount',
            modifier: (amount => amount !== null && amount !== undefined ? numberWithCurrencySymbol(amount) : '-')
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status => {
                return `<span class="badge-square-light bg-${status.color}">${status.name}</span>`
            })
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('view_order_details'),
        },
    ],
    actions: [
        {
            title: t('order_details'),
            type: 'order_details',
            icon: markRaw(Eye),
            modifier: () => canAccess('view_order_details'),
        },
        {
            title: t('change_amount'),
            type: 'changeamount',
            icon: markRaw(DoubleCheck),
            modifier: (row => canAccess('update_order_status') && row.status && row.status.name === 'Complete' && row.due_amount !== 0),
        },
    ],
})

const {
    showStatusModal,
    modalData,
    preloader,
    openStatusModal,
    updateOrderStatus,
    closeStatusModal,
} = useOrderStatus(tableId);
const actionTiger = (row, action) => {
    switch (action.type) {
        case "order_details":
            router.push(`/order-details/${row.id}`);
            break;
        case "changeamount":
            openStatusModal("changeamount", row);
            break;
    }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('complete_orders')" />

        <app-datatable :id="tableId"
            :options="options"
            @action="actionTiger" />

        <order-status-modal
            v-if="showStatusModal"
            :modal-id="`order-${modalData.type}-modal`"
            :modal-class="['changeamount'].includes(modalData.type) ? 'text-success' : 'text-danger'"
            :icon="modalData.icon"
            :title="modalData.title"
            :message="modalData.message"
            :table-id="tableId"
            :preloader="preloader"
            @confirm="updateOrderStatus"
            @cancel="closeStatusModal" />    


    </div>
</template>
<style scoped lang="scss"></style>