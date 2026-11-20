<script setup>
import {ref, markRaw, h} from "vue";
import {useI18n} from "vue-i18n";
import {useOpenModal} from "@/composable/modal/useOpenModal.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import { Edit, Trash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import {PARTNERS} from "@/services/endpoints/landlord.js";
import PartnerModal from "@/views/landlord/pos/partners/PartnerModal.vue";
import AreaColumn from "@/views/landlord/common/component/AreaColumn.vue";

const {canAccess} = usePermission();

const {t} = useI18n();

const tableId = ref('customer-table')

const options = ref({
    url: PARTNERS,
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
            permission: canAccess("update_partners") || canAccess("delete_partners")
        },
    ],
    filters: [],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_partners'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_partners'),
        }
    ],
})

const {isModalActive, selectedData, closeModal} = useOpenModal()
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${PARTNERS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${PARTNERS}/${row.id}`
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('corporate_application')">
        </app-breadcrumb>

        <app-datatable :id="tableId"
                       :options="options"
                       :add-button-text="$t('add_corporate_application')"
                       :button-visible="canAccess('create_partners')"
                       @buttonAction="isModalActive = true"
                       @action="actionTiger"/>

        <partner-modal v-if="isModalActive"
                         :table-id="tableId"
                         modal-id="partner-modal"
                         :selected-url="selectedData"
                         @close="closeModal"/>

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled"/>

    </div>
</template>

<style scoped lang="scss">
</style>