<script setup>
import {markRaw, ref} from "vue";
import {useI18n} from "vue-i18n";
import router from "@/router/index.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import ManageUserModal from "@/views/landlord/administrator/role/ManageUserModal.vue";
import {LANDLORD_ROLES} from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import {Edit, List, Trash} from "@iconoir/vue";

const {canAccess} = usePermission();

const tableId = ref("role-table");
const {t} = useI18n();
const options = ref({
    url: LANDLORD_ROLES,
    pageSize: 5,
    rowLimit: 10,
    orderBy: "desc",
    actionType: "dropdown",
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: false,
    columns: [
        {
            title: t("name"),
            type: "text",
            key: "name",
        },
        {
            title: t("number_of_user"),
            type: "text",
            key: "total_users",
        },
        {
            title: t("total_permissions"),
            type: "text",
            key: "total_permissions",
        },

        {
            title: t("action"),
            type: "action",
            align: "right",
            permission: canAccess('update_roles') || canAccess('delete_roles')
        },
    ],

    filters: [],
    actions: [
        {
            title: t('edit'),
            icon: markRaw(Edit),
            type: 'edit',
            modifier: (row) => !row.is_admin && !row.is_default && canAccess('update_roles'),
        },
        // {
        //     title: t('manage_users'),
        //     icon: markRaw(List),
        //     type: 'manage_users',
        //     modifier: (row) =>  canAccess('landlord_role_attach_user') || canAccess('landlord_role_detach_user'),
        // },
        {
            title: t('delete'),
            icon: markRaw(Trash),
            type: 'delete',
            modifier: (row) => !row.is_admin && !row.is_default && canAccess('delete_roles'),
        },
    ],
});

const isManageUserModalActive = ref(false);
const manageUserInfo = ref([]);

const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push({name: 'editRole', params: {id: row.id}});
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${LANDLORD_ROLES}/${row.id}`
    } else if (action.type === 'manage_users') {
        isManageUserModalActive.value = true;
        manageUserInfo.value = row;
    }
}

const closeManageUserModal = () => {
    isManageUserModalActive.value = false;
    manageUserInfo.value = [];
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('roles')"/>

        <app-datatable :id="tableId"
                       :options="options"
                       :add-button-text="$t('add_role')"
                       :button-visible="canAccess('create_roles')"
                       @buttonAction="router.push({name: 'createRole'})"
                       @action="actionTiger"/>

        <app-delete-modal v-if="isDeleteModal"
                          :table-id="tableId"
                          :selected-url="deleteUrl"
                          @cancelled="cancelled"/>

        <manage-user-modal
            v-if="isManageUserModalActive"
            modal-id="manage-user-modal"
            :table-id="tableId"
            :manage-user="manageUserInfo"
            @close="closeManageUserModal"
        />

    </div>
</template>

<style scoped lang="scss">
</style>
