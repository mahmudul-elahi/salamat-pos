<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import EcommerceModal from "@/views/landlord/setting/ecommerce/EcommerceModal.vue";
import useEmitter from "@/composable/emitter/useEmitter.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import ConfirmationModal from "@/views/global/modal/ConfirmationModal.vue";
import { LANDLORD_ECOMMERCES, LANDLORD_SHOPS_STATUS } from "@/services/endpoints/landlord.js";
import { Edit, Trash, CheckCircle, MinusCircle } from "@iconoir/vue";
import { useToast } from "vue-toastification";
import { formatDateToLocal } from "@/utils/helper.js";
import EcommerceComponent from "@/views/landlord/setting/ecommerce/EcommerceComponent.vue";
import Axios from "@/services/axios/index.js";

const { canAccess } = usePermission();

const emitter = useEmitter()

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();
const toast = useToast()
const preloader = ref(false)
const statusChangeModal = ref(false)
const statusChangeData = ref({})

const tableId = ref('payment-method-table')
const { t } = useI18n();
const options = ref({
    url: LANDLORD_ECOMMERCES,
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
    boxShadow: false,
    columns: [
        {
            title: t('name'),
            type: 'component',
            key: 'name',
            componentName: h(EcommerceComponent)
        },
        {
            title: t('created_at'),
            type: 'object',
            key: 'created_at',
            modifier: (createdAt => formatDateToLocal(createdAt))
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (value) => {
                if (value === 'active') {
                    return `<span class="badge-square-light text-capitalize bg-success">${value}</span>`
                } else if (value === 'inactive') {
                    return `<span class="badge-square-light text-capitalize bg-danger">${value}</span>`
                } else {
                    return `<span class="badge-square-light text-capitalize bg-warning">${value}</span>`
                }
            }
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_ecommerces") || canAccess("delete_ecommerces") || canAccess("ecommerce_update_status")
        },
    ],
    actions: [
        {
            title: t('active'),
            icon: markRaw(CheckCircle),
            type: 'active',
            modifier: (row) => row.status !== "active" && canAccess('ecommerce_update_status'),
        },
        {
            title: t('inactive'),
            icon: markRaw(MinusCircle),
            type: 'inactive',
            modifier: (row) => row.status !== "inactive" && canAccess('ecommerce_update_status'),
        },
        {
            title: t('edit'),
            icon: markRaw(Edit),
            type: 'edit',
            modifier: () => canAccess("update_ecommerces")
        },
        {
            title: t('delete'),
            icon: markRaw(Trash),
            type: 'delete',
            modifier: () => canAccess("delete_ecommerces")
        }
    ],
})
const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${LANDLORD_ECOMMERCES}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${LANDLORD_ECOMMERCES}/${row.id}`
    } else if (action.type === 'active') {
        statusChangeData.value = { row, status: action.type }
        statusChangeModal.value = true
    } else if (action.type === 'inactive') {
        statusChangeData.value = { row, status: action.type }
        statusChangeModal.value = true
    }
}
const shopUpdateStatus = (row, status) => {
    preloader.value = true
    Axios.patch(`${LANDLORD_SHOPS_STATUS}/${row.id}`, {
        'status': status
    }).then((response) => {
        toast.success(response?.data?.message);
        emitter.emit('reload-' + tableId.value);
    }).catch(({ response }) => {
        if (response.status === 403) {
            toast.error(response?.data?.message);
        }
    }).finally(() => {
        preloader.value = false
    })
}

const confirmStatusChange = () => {
    const { row, status } = statusChangeData.value;
    shopUpdateStatus(row, status);
    statusChangeModal.value = false;
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('ecommerce')" />
        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_ecommerce')"
            :button-visible="canAccess('create_ecommerces')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <ecommerce-modal v-if="isModalActive" modal-id="ecommerce-modal" :table-id="tableId"
            :selected-url="selectedData" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :selected-url="deleteUrl" :table-id="tableId" @cancelled="cancelled" />

        <confirmation-modal v-if="statusChangeModal" modal-id="customerStatusChange" modal-class="text-warning"
            :icon="InfoCircle" :title="$t('are_you_sure')" :message="statusChangeData?.status === 'active'
                ? $t('do_you_want_to_activate_this_shop')
                : $t('do_you_want_to_deactivate_this_shop')" :table-id="tableId" :preloader="preloader"
            :first-button-name="$t('yes')" :second-button-name="$t('cancel')" @cancel="statusChangeModal = false"
            @confirm="confirmStatusChange" />
    </div>
</template>

<style scoped lang="scss"></style>
