<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { WARRANTIES } from "@/services/endpoints/Ecommerce.js";
import WarrantyDetailsModal from "@/views/landlord/Ecommerce/warranty/WarrantyDetailsModal.vue";
import { XmarkCircle, Check, Eye } from "@iconoir/vue";
import { formatDateToLocal } from "@/utils/helper.js";
import OrderStatusModal from "@/views/landlord/Ecommerce/Order/Component/OrderStatusModal.vue";
import useWarrantyStatus from "@/composable/ecommerce/useWarrantyStatus.js";
const { canAccess } = usePermission();

const { t } = useI18n();

const tableId = ref('warranty-table')

const options = ref({
    url: WARRANTIES,
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
            title: t('name'),
            type: 'text',
            key: 'name',
        },
        {
            title: t('phone'),
            type: 'text',
            key: 'phone',
        },
        {
            title: t('product_name'),
            type: 'object',
            key: 'product',
            modifier: (product) => product?.product_name ?? '-'
        },
        {
            title: t('created_at'),
            type: 'object',
            key: 'created_at',
            modifier: (created_at => created_at ? formatDateToLocal(created_at) : '-')
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
            permission: canAccess('view_warranties') || canAccess('status_update_warranties'),
        },
    ],
    filters: [],
    actions: [
        {
            title: t('warranty_details'),
            type: 'warranty_details',
            icon: markRaw(Eye),
            modifier: () => canAccess('view_warranties'),
        },
        {
            title: t('approved'),
            type: 'approved',
            icon: markRaw(Check),
            modifier: (row => canAccess('status_update_warranties') && (row.status && row.status.name === 'Rejected' || row.status.name === 'Active')),
        },
        {
            title: t('rejected'),
            type: 'rejected',
            icon: markRaw(XmarkCircle),
            modifier:  (row => canAccess('status_update_warranties') && (row.status && row.status.name === 'Approved' || row.status.name === 'Active')),
        },
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const warrantyData = ref({})
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()
// 🔹 Use the composable
const {
    showStatusModal,
    modalData,
    preloader,
    openStatusModal,
    updateWarrantyStatus,
    closeStatusModal,
} = useWarrantyStatus(tableId);
const actionTiger = (row, action) => {
    switch (action.type) {
        case "warranty_details":
            isModalActive.value = true
        warrantyData.value = row
            break;
        case "approved":
            openStatusModal("approved", row);
            break;

        case "rejected":
            openStatusModal("rejected", row);
            break;
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('warranty_claim_list')">
        </app-breadcrumb>

        <app-datatable :id="tableId"
            :options="options"
            @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <warranty-details-modal v-if="isModalActive"
                               :table-id="tableId"
                               modal-id="warranty-details-modal"
                               :row-data="warrantyData"
                               :show-footer="false"
                               @close="closeModal"/>
        <order-status-modal
            v-if="showStatusModal"
            :modal-id="`order-${modalData.type}-modal`"
            :modal-class="modalData.type === 'approved' ? 'text-success' : 'text-danger'"
            :icon="modalData.icon"
            :title="modalData.title"
            :message="modalData.message"
            :table-id="tableId"
            :preloader="preloader"
            @confirm="updateWarrantyStatus"
            @cancel="closeStatusModal" />
    </div>
</template>

<style scoped lang="scss"></style>