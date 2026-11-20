<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { Edit, Trash } from "@iconoir/vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { ORDER_CONFIRMATION_OPTIONS } from "@/services/endpoints/landlord.js";
import OrderConfirmationOptionModal from "@/views/landlord/pos/orderConfirmation/OrderConfirmationOptionModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();

const tableId = ref('order-confirmation-option-table');

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const typeLabels = {
    advance_media: t('advance_media'),
    delivery_status: t('delivery_status'),
    delivery_by: t('delivery_by'),
};

const options = ref({
    url: ORDER_CONFIRMATION_OPTIONS,
    pageSize: 25,
    rowLimit: 25,
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
            title: t('type'),
            type: 'object',
            key: 'type',
            modifier: (type) => typeLabels[type] ?? type,
        },
        {
            title: t('name'),
            type: 'custom-html',
            key: 'name',
            modifier: (name, row) =>
                `<span class="badge-square-light bg-${escapeHtml(row.color || 'secondary')}">${escapeHtml(name)}</span>`,
        },
        { title: t('sort_order'), type: 'text', key: 'sort_order' },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => status
                ? `<span class="badge-square-light bg-success">${t('active')}</span>`
                : `<span class="badge-square-light bg-secondary">${t('inactive')}</span>`,
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_order_confirmation_options') || canAccess('delete_order_confirmation_options'),
        },
    ],
    filters: [
        {
            label: t('type'),
            type: 'search-select',
            key: 'type',
            options: [
                { id: 'advance_media', name: t('advance_media') },
                { id: 'delivery_status', name: t('delivery_status') },
                { id: 'delivery_by', name: t('delivery_by') },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_order_confirmation_options'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_order_confirmation_options'),
        },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${ORDER_CONFIRMATION_OPTIONS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${ORDER_CONFIRMATION_OPTIONS}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('order_confirmation_options')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_option')"
            :button-visible="canAccess('create_order_confirmation_options')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <order-confirmation-option-modal v-if="isModalActive" :table-id="tableId"
            modal-id="order-confirmation-option-modal" :selected-url="selectedData" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss"></style>
