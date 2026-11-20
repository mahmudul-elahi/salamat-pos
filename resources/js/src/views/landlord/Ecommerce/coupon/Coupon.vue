<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { COUPON, COUPON_STATUS_CHANGE } from "@/services/endpoints/Ecommerce.js";
import CouponModal from "@/views/landlord/Ecommerce/coupon/CouponModal.vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { numberWithCurrencySymbol, formatDateToLocal } from "@/utils/helper.js";
import { Edit, Trash, InfoCircle, CheckCircle, MinusCircle } from "@iconoir/vue";
import ConfirmationModal from "@/views/global/modal/ConfirmationModal.vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
const { t } = useI18n();
const preloader = ref(false)
const { canAccess } = usePermission();
const tableId = ref('coupon-table');
const statusChangeModal = ref(false);
const statusChangeData = ref(null);
const toast = useToast();
const emitter = useEmitter();
const options = ref({
    url: COUPON,
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
            title: t('code'),
            type: 'text',
            key: 'code',
        },
        {
            title: t('type'),
            type: 'text',
            key: 'type',
        },
        {
            title: t('start_date'),
            type: 'text',
            key: 'start_date',
            modifier: (start_date => formatDateToLocal(start_date))
        },
        {
            title: t('end_date'),
            type: 'text',
            key: 'end_date',
            modifier: (end_date => formatDateToLocal(end_date))
        },
        {
            title: t('discount_amount'),
            type: 'object',
            key: 'discount_value',
            modifier: (discount_value => numberWithCurrencySymbol(discount_value))
        },
        {
            title: 'Visibility',
            type: 'custom-html',
            key: 'is_public',
            modifier: (isPublic) => {
                if (isPublic) {
                    return `<span class="badge-square-light bg-success text-capitalize">Public</span>`
                }
                return `<span class="badge-square-light bg-warning text-capitalize">Private</span>`
            }
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (value) => {
                if (value == 1 || value === "1") {
                    return `<span class="badge-square-light bg-success text-capitalize">Active</span>`
                } else if (value == 0 || value === "0") {
                    return `<span class="badge-square-light bg-danger text-capitalize">Inactive</span>`
                } else {
                    return `<span class="badge-square-light bg-warning text-capitalize">Unknown</span>`
                }
            }
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_coupons") || canAccess("delete_coupons"),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_coupons'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_coupons'),
        },
        {
        title: t('activate'),
        icon: markRaw(CheckCircle),
        type: 'active',
        modifier: (row) => row.status == 0 && canAccess('status_update_coupons')
    },
    {
        title: t('inactive'),
        icon: markRaw(MinusCircle),
        type: 'inactive',
        modifier: (row) => row.status == 1 && canAccess('status_update_coupons')
    },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const manageStatus = (row, status) => {
    Axios.patch(`${COUPON_STATUS_CHANGE}/${row.id}`, {
        status: status
    }).then(({ data }) => {
        toast.success(data.message);
        emitter.emit('reload-' + tableId.value);
    }).catch(({ response }) => {
        if (response?.data?.message) toast.error(response.data.message);
    });
}

const openStatusChangeModal = (row, status) => {
    statusChangeData.value = { row, status };
    statusChangeModal.value = true;
};

const confirmStatusChange = () => {
    const { row, status } = statusChangeData.value;
    manageStatus(row, status);
    statusChangeModal.value = false;
};

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${COUPON}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${COUPON}/${row.id}`;
    } else if (action.type === 'status_toggle') {
        const newStatus = row.status == 1 ? 0 : 1;
        openStatusChangeModal(row, newStatus);
    }  else if (action.type === 'active') {
        statusChangeData.value = {row, status: action.type}
        statusChangeModal.value = true
        const newStatus = row.status == 1 ? 0 : 1;
        openStatusChangeModal(row, newStatus);
    }  else if (action.type === 'inactive') {
        const newStatus = row.status == 1 ? 0 : 1;
        openStatusChangeModal(row, newStatus);
    }
};
</script>


<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('coupon_list')" />
        <app-datatable
            :id="tableId"
            :options="options"
            :add-button-text="$t('add_coupon')"
            :button-visible="canAccess('create_coupons')"
            @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <coupon-modal
            v-if="isModalActive"
            :table-id="tableId"
            modal-id="coupon-modal"
            :selected-url="selectedData"
            @close="closeModal" />

        <app-delete-modal
            v-if="isDeleteModal"
            :table-id="tableId"
            :selected-url="deleteUrl"
            @cancelled="cancelled" />

        <confirmation-modal
            v-if="statusChangeModal"
            modal-id="couponStatusChange"
            modal-class="text-warning"
            :icon="InfoCircle"
            :title="$t('are_you_sure')"
            :message="statusChangeData?.status === 0 ? $t('do_you_want_to_inactive_this_coupon') : $t('do_you_want_to_active_this_coupon')"
            :table-id="tableId"
            :preloader="false"
            :first-button-name="$t('yes')"
            :second-button-name="$t('cancel')"
            @cancel="statusChangeModal = false"
            @confirm="confirmStatusChange" />
    </div>
</template>

<style scoped lang="scss"></style>