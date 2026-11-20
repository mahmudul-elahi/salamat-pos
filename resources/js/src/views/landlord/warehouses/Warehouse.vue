<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {useOpenModal} from "@/composable/modal/useOpenModal.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
// import {usePermission} from '@/composable/permission/usePermission.js';
import {WAREHOUSES} from "@/services/endpoints/landlord.js";
import WarehouseModal from "@/views/landlord/warehouses/WarehouseModal.vue";
import { Edit, Trash } from "@iconoir/vue";
// const {canAccess} = usePermission();

const {t} = useI18n();

const tableId = ref('warehouse-table')

const options = ref({
    url: WAREHOUSES,
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
            title: t('phone_number'),
            type: 'text',
            key: 'phone_number',
        },
        
        {
            title: t('email'),
            type: 'text',
            key: 'email',
        },
        {
            title: t('address'),
            type: 'text',
            key: 'address',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right'
        },
    ],
    filters: [],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => true,
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => true,
        }
    ],
})

const {isModalActive, selectedData, closeModal} = useOpenModal()
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${WAREHOUSES}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${WAREHOUSES}/${row.id}`
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('warehouse_list')">
        </app-breadcrumb>
        <app-datatable :id="tableId"
                               :options="options"
                               :add-button-text="$t('add_warehouse')"
                               @buttonAction="isModalActive = true"
                               @action="actionTiger"/>
        
                <warehouse-modal v-if="isModalActive"
                                 :table-id="tableId"
                                 modal-id="warehouse-modal"
                                 :selected-url="selectedData"
                                 @close="closeModal"/>
        
                <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled"/>
    </div>
</template>

<style scoped lang="scss">
</style>