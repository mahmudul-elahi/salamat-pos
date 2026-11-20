<script setup>
import { ref, h, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { SELL_DEVICE_REQUESTS } from "@/services/endpoints/Ecommerce.js";
import usePermission from "@/composable/permission/usePermission.js";
import { CheckCircle, XmarkCircle, Trash, Eye } from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import DeviceColumn from "./DeviceColumn.vue";
import SellDeviceRequestViewModal from "./SellDeviceRequestViewModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('sell-device-request-table');
const toast = useToast();
const emitter = useEmitter();

const isViewModal = ref(false);
const selectedUrl = ref(null);

const options = ref({
    url: SELL_DEVICE_REQUESTS,
    pageSize: 10,
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
            title: 'Device',
            type: 'component',
            key: 'device',
            componentName: h(DeviceColumn),
        },
        {
            title: t('customer'),
            type: 'object',
            key: 'customer',
            modifier: (customer) => customer ?? '-',
        },
        {
            title: t('phone_number'),
            type: 'text',
            key: 'phone_number',
        },
        {
            title: 'Condition',
            type: 'object',
            key: 'condition',
            modifier: (condition) => condition ? condition.replaceAll('_', ' ') : '-',
        },
        {
            title: 'Location',
            type: 'object',
            key: 'district',
            modifier: (district) => district ?? '-',
        },
        {
            title: 'Asking Price',
            type: 'custom-html',
            key: 'asking_price',
            modifier: (price, row) => {
                const negotiable = row.is_negotiable ? ' <span class="text-muted small">(Negotiable)</span>' : '';
                return `৳${price}${negotiable}`;
            },
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status_label',
            modifier: (value) => {
                if (value === 'Approved') {
                    return `<span class="badge-square-light bg-success text-capitalize">Approved</span>`
                } else if (value === 'Rejected') {
                    return `<span class="badge-square-light bg-danger text-capitalize">Rejected</span>`
                }
                return `<span class="badge-square-light bg-warning text-capitalize">Pending</span>`
            }
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("view_sell_device_requests") || canAccess("update_sell_device_requests") || canAccess("delete_sell_device_requests"),
        },
    ],
    actions: [
        {
            title: t('view'),
            type: 'view',
            icon: markRaw(Eye),
            modifier: (() => canAccess('view_sell_device_requests')),
        },
        {
            title: t('approved'),
            type: 'approved',
            icon: markRaw(CheckCircle),
            modifier: (row => row.status_label !== 'Approved' && canAccess('update_sell_device_requests')),
        },
        {
            title: t('rejected'),
            type: 'rejected',
            icon: markRaw(XmarkCircle),
            modifier: (row => row.status_label !== 'Rejected' && canAccess('update_sell_device_requests')),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: (() => canAccess('delete_sell_device_requests')),
        },
    ],
});

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const manageStatus = (row, status) => {
    Axios.patch(`${SELL_DEVICE_REQUESTS}/${row.id}`, { status: status })
        .then(({ data }) => {
            toast.success(data.message);
            emitter.emit('reload-' + tableId.value);
        })
        .catch(({ response }) => {
            if (response?.data?.message) toast.error(response.data.message);
        });
}

const actionTiger = (row, action) => {
    if (action.type === 'view') {
        selectedUrl.value = `${SELL_DEVICE_REQUESTS}/${row.id}`;
        isViewModal.value = true;
    } else if (action.type === 'approved') {
        manageStatus(row, 4); // StatusEnum::Approved
    } else if (action.type === 'rejected') {
        manageStatus(row, 3); // StatusEnum::Rejected
    } else if (action.type === 'delete') {
        deleteUrl.value = `${SELL_DEVICE_REQUESTS}/${row.id}`;
        isDeleteModal.value = true;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="'Sell Device Requests'" />
        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <sell-device-request-view-modal v-if="isViewModal" modal-id="sell-device-request-view-modal"
            :selected-url="selectedUrl" @close="isViewModal = false; selectedUrl = null" />
    </div>
</template>

<style scoped lang="scss"></style>
