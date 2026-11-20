<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { formatDateToLocal, numberWithCurrencySymbol } from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Check, Circle, DeliveryTruck, DoubleCheck, Eye, Trash, UndoCircle, XmarkCircle, Edit, CreditCard, Refresh, ShieldCheck, Pause, RefreshCircle } from "@iconoir/vue";
import { ORDERS } from "@/services/endpoints/Ecommerce.js";
import { SELECTED_ECOMMERCES } from "@/services/endpoints/landlord.js";
import CustomerProfile from "@/views/landlord/Ecommerce/Order/Component/CustomerProfile.vue";
import OrderStatusModal from "@/views/landlord/Ecommerce/Order/Component/OrderStatusModal.vue";
import OrderSerialNumberModal from "@/views/landlord/Ecommerce/Order/Component/OrderSerialNumberModal.vue";
import ReceivedAmountModal from "@/views/landlord/Ecommerce/Order/Component/ReceivedAmountModal.vue";
import UpdateOrderItemModal from "@/views/landlord/Ecommerce/Order/Component/UpdateOrderItemModal.vue";
import SslCommerzStatusModal from "@/views/landlord/Ecommerce/Order/Component/SslCommerzStatusModal.vue";
import router from "@/router/index.js";
import useOrderStatus from "@/composable/ecommerce/useOrderStatus.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import CustomerNote from "./Component/CustomerNote.vue";
import FraudCheckColumn from "@/views/landlord/common/component/FraudCheckColumn.vue";


const { t } = useI18n();
const { canAccess } = usePermission();
const emitter = useEmitter();

const tableId = ref('all-orders-table')
const showSerialModal = ref(false);
const selectedOrder = ref(null);
const showReceivedAmountModal = ref(false);
const selectedOrderForAmount = ref(null);
const showUpdateOrderItemModal = ref(false);
const selectedOrderForVariant = ref(null);
const showSslCommerzStatusModal = ref(false);
const selectedOrderForSslCommerz = ref(null);

const openSerialInputModal = (order) => {
    selectedOrder.value = order;
    showSerialModal.value = true;
};

const closeSerialModal = () => {
    showSerialModal.value = false;
};
const options = ref({
    url: ORDERS,
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
            key: 'id',
            componentName: h(CustomerProfile),
        },
        {
            title: t('order_number'),
            type: 'text',
            key: 'invoice_number',
        },
        {
            title: 'Warehouse',
            type: 'text',
            key: 'warehouse_name',
        },
        {
            title: t('pm'),
            type: 'text',
            key: 'payment_method',
        },
        {
            title: 'D.Amount',
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
            title: 'P.Status',
            type: 'custom-html',
            key: 'payment_status',
            modifier: (status => {
                return `<span class="badge-square-light bg-${status.color}">${status.name}</span>`
            })
        },
        {
            title: 'C.Note',
            type: 'component',
            key: 'customer_note',
            componentName: h(CustomerNote)
        },
        {
            title: 'Fraud Check',
            type: 'component',
            key: 'id',
            componentName: h(FraudCheckColumn),
            permission: canAccess('view_bdcourier_check_fraud') || canAccess('view_bdcourier_check_fraud_api'),
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('view_order_details') || canAccess('update_order_status') || canAccess('approved_order_status') || canAccess('delete_orders'),
        },
    ],
    filters: [
        {
            label: 'Warehouse',
            type: 'advance-search-select',
            key: 'warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_ECOMMERCES}`,
            permission: true,
        },
        {
            label: t('status'),
            type: 'search-select',
            key: 'status',
            options: [
                { id: 'pending', name: 'Pending' },
                { id: 'hold', name: 'Hold' },
                { id: 'confirmed', name: 'Confirmed' },
                { id: 'approved', name: 'Approved' },
                { id: 'processing', name: 'Processing' },
                { id: 'onway', name: 'OnWay' },
                { id: 'return', name: 'Return' },
                { id: 'complete', name: 'Complete' },
                { id: 'rejected', name: 'Rejected' },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('order_details'),
            type: 'order_details',
            icon: h(Eye),
            modifier: () => canAccess('view_order_details'),
        },
        // {
        //     title: t('edit'),
        //     type: 'edit',
        //     icon: h(Edit),
        //     modifier: (row => row.status !== 'paid' && row.status !== 'partially_paid' && canAccess("update_purchases")),
        // },
        {
            title: t('receive_amount'),
            type: 'receive_amount',
            icon: h(CreditCard),
            modifier: (row => canAccess('received_order_amount')),
        },
        {
            title: t('update_order_item'),
            type: 'update_order_item',
            icon: h(Refresh),
            modifier: (row => canAccess('update_order_item_variant') && canAccess('update_order_gift_item_variant') && row.status
                && ['Pending'].includes(row.status.name)),
        },
        {
            title: 'Confirm',
            type: 'confirm',
            icon: h(Check),
            modifier: (row => canAccess('update_order_status') && row.status && row.status.name === 'Pending'),
        },
        {
            title: t('hold'),
            type: 'hold',
            icon: h(Pause),
            modifier: (row => canAccess('update_order_status') && row.status && row.status.name === 'Pending'),
        },
        {
            title: t('resume'),
            type: 'resume',
            icon: h(RefreshCircle),
            modifier: (row => canAccess('update_order_status') && row.status && row.status.name === 'Hold'),
        },
        {
            title: t('approved'),
            type: 'approved',
            icon: h(Check),
            modifier: (row => canAccess('approved_order_status') && row.status && row.status.name === 'Confirmed'),
        },
        {
            title: t('cancel'),
            type: 'cancel',
            icon: h(XmarkCircle),
            modifier: (row => canAccess('update_order_status') && row.status && ['Pending', 'Confirmed', 'Hold'].includes(row.status.name)),
        },
        {
            title: t('processing'),
            type: 'processing',
            icon: h(Circle),
            modifier: (row => canAccess('update_order_status') && row.status && row.status.name === 'Approved'),
        },
        {
            title: t('onway'),
            type: 'onway',
            icon: h(DeliveryTruck),
            modifier: (row => canAccess('update_order_status') && row.status && row.status.name === 'Processing'),
        },
        {
            title: t('return'),
            type: 'return',
            icon: h(UndoCircle),
            modifier: (row => canAccess('update_order_status') && row.status && row.status.name === 'OnWay'),
        },
        {
            title: t('complete'),
            type: 'complete',
            icon: h(DoubleCheck),
            modifier: (row => canAccess('update_order_status') && (row.status && row.status.name === 'Approved' || row.status.name === 'Processing' || row.status.name === 'OnWay')),
        },
        {
            title: t('change_amount'),
            type: 'changeamount',
            icon: h(DoubleCheck),
            modifier: (row => canAccess('update_order_status') && row.status && row.status.name === 'Complete' && row.due_amount !== 0),
        },
        {
            title: 'Check Payment Status',
            type: 'check_sslcommerz_status',
            icon: h(ShieldCheck),
            modifier: (row => canAccess('status_orders_check_sslcommerz') && row.payment_method === 'online' && !!row.transaction_id && row?.payment_status?.name !== 'Successful'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: () => canAccess('delete_orders'),
        }

    ],
})

// 🔹 Use the composable
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
        case "edit":
            router.push(`/edit-order/${row.id}`);
            break;
        case "confirm":
            openStatusModal("confirm", row);
            break;

        case "hold":
            openStatusModal("hold", row);
            break;

        case "resume":
            openStatusModal("resume", row);
            break;

        case "approved":
            openSerialInputModal(row);
            break;

        case "cancel":
            openStatusModal("cancel", row);
            break;
        case "processing":
            openStatusModal("processing", row);
            break;

        case "onway":
            openStatusModal("onway", row);
            break;

        case "return":
            openStatusModal("return", row);
            break;

        case "complete":
            openStatusModal("complete", row);
            break;

        case "receive_amount":
            selectedOrderForAmount.value = row;
            showReceivedAmountModal.value = true;
            break;

        case "update_order_item":
            selectedOrderForVariant.value = row;
            showUpdateOrderItemModal.value = true;
            break;

        case "changeamount":
            openStatusModal("changeamount", row);
            break;

        case "delete":
            deleteUrl.value = `${ORDERS}/${row.id}`;
            isDeleteModal.value = true;
            break;

        case "check_sslcommerz_status":
            selectedOrderForSslCommerz.value = row;
            showSslCommerzStatusModal.value = true;
            break;
    }
}

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const onSerialSubmitted = () => {
    emitter.emit("reload-" + tableId.value);
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('all_orders')" />

        <app-datatable :id="tableId" :options="options" @action="actionTiger" />


        <order-status-modal v-if="showStatusModal" :modal-id="`order-${modalData.type}-modal`"
            :modal-class="['confirm', 'resume', 'approved', 'processing', 'onway', 'complete', 'changeamount'].includes(modalData.type) ? 'text-success' : 'text-danger'"
            :icon="modalData.icon" :title="modalData.title" :message="modalData.message" :table-id="tableId"
            :preloader="preloader" @confirm="updateOrderStatus" @cancel="closeStatusModal" />

        <order-serial-number-modal v-if="showSerialModal" :modal-id="'serial-number-modal'" :order="selectedOrder"
            @close="closeSerialModal" @submitted="onSerialSubmitted" />




        <received-amount-modal v-if="showReceivedAmountModal" modal-id="received-amount-modal"
            :order="selectedOrderForAmount" :table-id="tableId" @close="showReceivedAmountModal = false"
            @submitted="() => { showReceivedAmountModal = false; emitter.emit('reload-' + tableId) }" />

        <update-order-item-modal v-if="showUpdateOrderItemModal" modal-id="update-order-item-modal"
            :order="selectedOrderForVariant" :table-id="tableId" @close="showUpdateOrderItemModal = false"
            @submitted="() => { showUpdateOrderItemModal = false; emitter.emit('reload-' + tableId) }" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <ssl-commerz-status-modal v-if="showSslCommerzStatusModal" modal-id="sslcommerz-status-modal"
            :order="selectedOrderForSslCommerz" :table-id="tableId"
            @close="showSslCommerzStatusModal = false; selectedOrderForSslCommerz = null" />

    </div>
</template>
<style scoped lang="scss"></style>
