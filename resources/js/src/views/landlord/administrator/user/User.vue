<script setup>
import { h, markRaw, ref } from "vue";
import { useI18n } from "vue-i18n";
import UserAvatarComponent from "@/views/common/avatar/UserAvatarComponent.vue";
import { LANDLORD_USERS } from "@/services/endpoints/landlord.js";
import UserModal from "@/views/landlord/administrator/user/UserModal.vue";
import ManageRoleModal from "@/views/landlord/administrator/user/ManageRoleModal.vue";
import ManageShopModal from "@/views/landlord/administrator/user/ManageShopModal.vue";
import MakeEmployeeModal from "@/views/landlord/administrator/user/MakeEmployeeModal.vue";
import ConfirmationModal from "@/views/global/modal/ConfirmationModal.vue";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import Axios from "@/services/axios/index.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import { useToast } from "vue-toastification";
import usePermission from "@/composable/permission/usePermission.js";
import { CheckCircle, InfoCircle, MinusCircle, Trash, List, Shop, UserBadgeCheck } from "@iconoir/vue";

const { canAccess } = usePermission();
const { t } = useI18n();
const tableId = ref('user-table')

const options = ref({
    url: LANDLORD_USERS,
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
            type: 'component',
            key: 'name',
            componentName: h(UserAvatarComponent)
        },
        {
            title: t('email'),
            type: 'text',
            key: 'email'
        },
        {
            title: t('phone'),
            type: 'text',
            key: 'phone'
        },
        {
            title: t('shop'),
            type: 'object',
            key: 'tenants',
            modifier: (tenants => tenants?.map(tenant => tenant?.name).join(', '))
        },
        {
            title: t('role'),
            type: 'object',
            key: 'roles',
            modifier: (roles => roles?.map(role => role?.name).join(', '))
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status => {
                if (status === 'active') {
                    return `<span class="badge-square-light bg-success">${status}</span>`
                } else if (status === 'inactive') {
                    return `<span class="badge-square-light bg-danger">${status}</span>`
                } else {
                    return `<span class="badge-square-light bg-info">${status}</span>`
                }

            })
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('delete_users'),
        },
    ],

    actions: [
        {
            title: t('manage_roles'),
            icon: markRaw(List),
            type: 'manage_roles',
            modifier: (row => !row.is_admin && canAccess('update_users')),
        },
        {
            title: t('manage_shop'),
            icon: markRaw(Shop),
            type: 'manage_shop',
            modifier: (row => !row.is_admin && canAccess('update_users')),
        },
        {
            title: t('deactivate'),
            icon: markRaw(MinusCircle),
            type: 'inactive',
            modifier: (row => !row.is_admin && row.status === 'active' && canAccess('update_users')),
        },
        {
            title: t('activate'),
            icon: markRaw(CheckCircle),
            type: 'active',
            modifier: (row => !row.is_admin && row.status !== 'active' && canAccess('update_users')),
        },
        {
            title: 'Make Employee',
            icon: markRaw(UserBadgeCheck),
            type: 'make_employee',
            modifier: (row => !row.is_admin && !row.is_employee && canAccess('create_users')),
        },
        {
            title: t('delete'),
            icon: markRaw(Trash),
            type: 'delete',
            modifier: (row => !row.is_admin && canAccess('delete_users'))
        }
    ],
})
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()
const isModalActive = ref(false)
const statusChangeModal = ref(false)
const statusChangeData = ref({})
const isManageRoleModalActive = ref(false);
const isManageShopModalActive = ref(false);
const isMakeEmployeeModalActive = ref(false);
const makeEmployeeUser = ref(null);
const selectedUrl = ref('');
const actionTiger = (row, action) => {

    if (action.type === 'active') {
        statusChangeData.value = { row, status: action.type }
        statusChangeModal.value = true
    } else if (action.type === 'inactive') {
        statusChangeData.value = { row, status: action.type }
        statusChangeModal.value = true
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${LANDLORD_USERS}/${row.id}`
    } else if (action.type === 'manage_roles') {
        isManageRoleModalActive.value = true;
        selectedUrl.value = `${LANDLORD_USERS}/${row.id}`;
    } else if (action.type === 'manage_shop') {
        isManageShopModalActive.value = true;
        selectedUrl.value = `${LANDLORD_USERS}/${row.id}`;
    } else if (action.type === 'make_employee') {
        makeEmployeeUser.value = row;
        isMakeEmployeeModalActive.value = true;
    }
}
const closeManageRoleModal = () => {
    isManageRoleModalActive.value = false;
    selectedUrl.value = '';
};
const closeManageShopModal = () => {
    isManageShopModalActive.value = false;
    selectedUrl.value = '';
};
const emitter = useEmitter()
const toast = useToast()
const userUpdateStatus = (row, status) => {
    Axios.patch(`${LANDLORD_USERS}/${row.id}`, {
        'status': status
    }).then((response) => {
        toast.success(response?.data?.message);
        emitter.emit('reload-' + tableId.value);
    }).catch(({ response }) => {
        if (response.status === 403) {
            toast.error(response?.data?.message);
        }
    })
}
const confirmStatusChange = () => {
    const { row, status } = statusChangeData.value;
    userUpdateStatus(row, status);
    statusChangeModal.value = false;
}
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('users')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_user')"
            :button-visible="canAccess('create_users')" @button-action="isModalActive = true" @action="actionTiger" />

        <user-modal :table-id="tableId" v-if="isModalActive" modal-id="user-modal" @close="isModalActive = false" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
        <confirmation-modal v-if="statusChangeModal" modal-id="customerStatusChange" modal-class="text-warning"
            :icon="InfoCircle" :title="$t('are_you_sure')" :message="statusChangeData?.status === 'active'
                ? $t('do_you_want_to_activate_this_customer')
                : $t('do_you_want_to_deactivate_this_customer')" :table-id="tableId" :preloader="preloader"
            :first-button-name="$t('yes')" :second-button-name="$t('cancel')" @cancel="statusChangeModal = false"
            @confirm="confirmStatusChange" />

        <manage-role-modal v-if="isManageRoleModalActive" modal-id="manage-role-modal" :table-id="tableId"
            :selected-url="selectedUrl" @close="closeManageRoleModal" />

        <manage-shop-modal v-if="isManageShopModalActive" modal-id="manage-shop-modal" :table-id="tableId"
            :selected-url="selectedUrl" @close="closeManageShopModal" />

        <make-employee-modal v-if="isMakeEmployeeModalActive" modal-id="make-employee-modal" :table-id="tableId"
            :user="makeEmployeeUser"
            @close="isMakeEmployeeModalActive = false; makeEmployeeUser = null"
            @success="isMakeEmployeeModalActive = false; makeEmployeeUser = null; emitter.emit('reload-' + tableId)" />
    </div>
</template>

<style scoped lang="scss"></style>
