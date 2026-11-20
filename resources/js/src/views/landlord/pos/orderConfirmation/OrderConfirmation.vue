<script setup>
import { ref, markRaw, h, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { Edit, Trash, Eye, CheckCircle, DeliveryTruck } from "@iconoir/vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import useEmitter from "@/composable/emitter/useEmitter";
import Axios from "@/services/axios";
import {
    ORDER_CONFIRMATIONS,
    SELECTED_SELLERS,
    SELECTED_ADVANCE_MEDIAS,
    SELECTED_DELIVERY_STATUSES,
    SELECTED_DELIVERY_BYS,
} from "@/services/endpoints/landlord.js";
import OrderConfirmationModal from "@/views/landlord/pos/orderConfirmation/OrderConfirmationModal.vue";
import OrderConfirmationViewModal from "@/views/landlord/pos/orderConfirmation/OrderConfirmationViewModal.vue";
import OrderDeliveryModal from "@/views/landlord/pos/orderConfirmation/OrderDeliveryModal.vue";
import CustomerColumn from "@/views/landlord/pos/orderConfirmation/CustomerColumn.vue";
import ProductColumn from "@/views/landlord/pos/orderConfirmation/ProductColumn.vue";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const toast = useToast();
const emitter = useEmitter();

const tableId = ref('order-confirmation-table');

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const badge = (option) => option
    ? `<span class="badge-square-light bg-${escapeHtml(option.color || 'secondary')}">${escapeHtml(option.name)}</span>`
    : '-';

const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

const options = ref({
    url: ORDER_CONFIRMATIONS,
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
            title: t('customer'),
            type: 'component',
            key: 'customer_name',
            componentName: h(CustomerColumn),
        },
        { title: t('date'), type: 'text', key: 'date' },
        {
            title: t('confirmation_date'),
            type: 'custom-html',
            key: 'confirmation_date',
            // The confirmation call is what this list tracks, so an unconfirmed
            // row has to be obvious at a glance rather than just blank.
            modifier: (date) => date
                ? `<span class="text-nowrap">${escapeHtml(date)}</span>`
                : `<span class="badge-square-light bg-warning">${t('not_confirmed')}</span>`,
        },

        {
            title: t('product'),
            type: 'component',
            key: 'product_name',
            componentName: h(ProductColumn),
        },
        { title: t('address'), type: 'component', key: 'address', componentName: h(AddressColumn) },
        {
            title: t('source_media'),
            type: 'custom-html',
            key: 'source_media',
            modifier: (media) => media
                ? `<span class="badge-square-light bg-info">${escapeHtml(media)}</span>`
                : '-',
        },
        {
            title: t('online_offline'),
            type: 'custom-html',
            key: 'order_type',
            modifier: (type) => type === 'offline'
                ? `<span class="badge-square-light bg-danger">${t('offline')}</span>`
                : `<span class="badge-square-light bg-success">${t('online')}</span>`,
        },
        { title: t('order_person'), type: 'object', key: 'order_person', modifier: (name) => name || '-' },
        { title: t('advance_media'), type: 'custom-html', key: 'advance_media', modifier: badge },
        { title: t('advance_amount'), type: 'object', key: 'advance_amount', modifier: money },
        { title: t('selling_price'), type: 'object', key: 'selling_price', modifier: money },
        { title: t('cod'), type: 'object', key: 'cod', modifier: money },
        { title: t('note'), type: 'component', key: 'note', componentName: h(NoteColumn) },
        { title: t('delivery_status'), type: 'custom-html', key: 'delivery_status', modifier: badge },
        { title: t('invoice_no'), type: 'object', key: 'invoice_no', modifier: (no) => no || '-' },
        { title: t('delivery_by'), type: 'custom-html', key: 'delivery_by', modifier: badge },
        {
            title: t('track'),
            type: 'custom-html',
            key: 'track_url',
            modifier: (url) => url
                ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer">${t('track')}</a>`
                : '-',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('view_order_confirmations') || canAccess('update_order_confirmations') || canAccess('delete_order_confirmations'),
        },
    ],
    filters: [
        { label: t('date'), type: 'date-range', key: 'date', permission: true },
        {
            label: t('confirmed'),
            type: 'search-select',
            key: 'confirmed',
            options: [
                { id: 'yes', name: t('confirmed') },
                { id: 'no', name: t('not_confirmed') },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
        {
            label: t('online_offline'),
            type: 'search-select',
            key: 'order_type',
            options: [
                { id: 'online', name: t('online') },
                { id: 'offline', name: t('offline') },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
        {
            label: t('source_media'),
            type: 'search-select',
            key: 'source_media',
            options: [
                { id: 'Facebook', name: 'Facebook' },
                { id: 'WhatsApp', name: 'WhatsApp' },
                { id: 'TikTok', name: 'TikTok' },
                { id: 'Instagram', name: 'Instagram' },
                { id: 'Messenger', name: 'Messenger' },
                { id: 'Website', name: 'Website' },
                { id: 'Phone Call', name: 'Phone Call' },
                { id: 'Other', name: 'Other' },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
        {
            label: t('order_person'),
            type: 'advance-search-select',
            key: 'order_person',
            fetchUrl: SELECTED_SELLERS,
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            permission: true,
        },
        {
            label: t('advance_media'),
            type: 'advance-search-select',
            key: 'advance_media',
            fetchUrl: SELECTED_ADVANCE_MEDIAS,
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 100,
            permission: true,
        },
        {
            label: t('delivery_status'),
            type: 'advance-search-select',
            key: 'delivery_status',
            fetchUrl: SELECTED_DELIVERY_STATUSES,
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 100,
            permission: true,
        },
        {
            label: t('delivery_by'),
            type: 'advance-search-select',
            key: 'delivery_by',
            fetchUrl: SELECTED_DELIVERY_BYS,
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 100,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('view_details'),
            type: 'view_details',
            icon: markRaw(Eye),
            modifier: () => canAccess('view_order_confirmations'),
        },
        {
            title: t('mark_confirmed'),
            type: 'confirm',
            icon: markRaw(CheckCircle),
            // Nothing to do on a row that already carries a confirmation date.
            modifier: (row) => canAccess('update_order_confirmations') && !row.is_confirmed,
        },
        {
            title: t('delivery'),
            type: 'delivery',
            icon: markRaw(DeliveryTruck),
            modifier: () => canAccess('update_order_confirmation_deliveries'),
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_order_confirmations'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_order_confirmations'),
        },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const isViewModal = ref(false);
const viewUrl = ref(null);

const openView = (id) => {
    viewUrl.value = `${ORDER_CONFIRMATIONS}/${id}`;
    isViewModal.value = true;
};

// The eye in the Product column can't call this directly — component columns
// are rendered by the datatable, not here — so it signals through the emitter.
const viewEvent = `view-order-${tableId.value}`;

onMounted(() => emitter.on(viewEvent, openView));
onUnmounted(() => emitter.off(viewEvent, openView));

const isDeliveryModal = ref(false);
const selectedForDelivery = ref(null);

const confirmOrder = (row) => {
    Axios.patch(`${ORDER_CONFIRMATIONS}/${row.id}/confirm`, {})
        .then(({ data }) => {
            toast.success(data?.message || 'Order confirmed successfully');
            emitter.emit('reload-' + tableId.value);
        })
        .catch(({ response }) => toast.error(response?.data?.message || 'Failed to confirm the order'));
};

const actionTiger = (row, action) => {
    if (action.type === 'view_details') {
        openView(row.id);
    } else if (action.type === 'confirm') {
        confirmOrder(row);
    } else if (action.type === 'delivery') {
        selectedForDelivery.value = row;
        isDeliveryModal.value = true;
    } else if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${ORDER_CONFIRMATIONS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${ORDER_CONFIRMATIONS}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('order_confirmations')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_order')"
            :button-visible="canAccess('create_order_confirmations')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <order-confirmation-modal v-if="isModalActive" :table-id="tableId" modal-id="order-confirmation-modal"
            :selected-url="selectedData" @close="closeModal" />

        <order-confirmation-view-modal v-if="isViewModal" :table-id="tableId" modal-id="order-confirmation-view-modal"
            :selected-url="viewUrl" @close="isViewModal = false; viewUrl = null" />

        <order-delivery-modal v-if="isDeliveryModal" :table-id="tableId" modal-id="order-delivery-modal"
            :order="selectedForDelivery" :selected-url="`${ORDER_CONFIRMATIONS}/${selectedForDelivery.id}/delivery`"
            @close="isDeliveryModal = false; selectedForDelivery = null" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss"></style>
