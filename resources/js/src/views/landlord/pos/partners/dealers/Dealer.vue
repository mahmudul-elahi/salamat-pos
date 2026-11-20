<script setup>
import {ref, markRaw, h} from "vue";
import {useI18n} from "vue-i18n";
import {useOpenModal} from "@/composable/modal/useOpenModal.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import { Edit, Trash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import {DEALERS} from "@/services/endpoints/landlord.js";
import DealerModal from "@/views/landlord/pos/partners/dealers/DealerModal.vue";
import AreaColumn from "@/views/landlord/common/component/AreaColumn.vue";

const {canAccess} = usePermission();

const {t} = useI18n();

const tableId = ref('dealer-table')

const options = ref({
    url: DEALERS,
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
            title: t('customer_name'),
            type: 'text',
            key: 'name',
        },
        {
            title: t('business_name'),
            type: 'text',
            key: 'business_name',
        },
        {
            title: t('primary_phone'),
            type: 'text',
            key: 'primary_phone',
        },
        {
            title: t('email'),
            type: 'object',
            key: 'email',
            modifier: (email => email ? email : '-')
        },
        {
            title: t('address'),
            type: 'component',
            key: 'full_address',
            componentName: h(AreaColumn)
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_dealers") || canAccess("delete_dealers")
        },
    ],
    filters: [],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_dealers'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_dealers'),
        }
    ],
})

const {isModalActive, selectedData, closeModal} = useOpenModal()
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${DEALERS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${DEALERS}/${row.id}`
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('dealer_list')">
        </app-breadcrumb>

        <app-datatable :id="tableId"
                       :options="options"
                       :add-button-text="$t('add_dealer')"
                       :button-visible="canAccess('create_dealers')"
                       @buttonAction="isModalActive = true"
                       @action="actionTiger"/>

        <dealer-modal v-if="isModalActive"
                         :table-id="tableId"
                         modal-id="dealer-modal"
                         :selected-url="selectedData"
                         @close="closeModal"/>

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled"/>

    </div>
</template>

<style scoped lang="scss">
</style>