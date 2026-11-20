<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {useOpenModal} from "@/composable/modal/useOpenModal.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import {SPECIFICATION_GROUPS} from "@/services/endpoints/landlord.js";
import SpecificationGroupModal from "@/views/landlord/pos/product/specificationGroup/SpecificationGroupModal.vue";
import {Edit, Trash} from "@iconoir/vue";
import {formatDateToLocal} from "@/utils/helper.js";

const {canAccess} = usePermission();

const {t} = useI18n();

const tableId = ref('specification-group-table')

const options = ref({
    url: SPECIFICATION_GROUPS,
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
            title: t('sub_specification_groups'),
            type: 'custom-html',
            key: 'sub_specification_groups',
            modifier: (values) => {
                if (!values?.length) return '';

                return `
        <div class="d-flex flex-wrap" style="width: 60%">
            ${values.map(v =>
                    `<span class="badge-square-light text-capitalize bg-primary me-1 mb-1 d-inline-block" style="min-width:80px; text-align:center;">
                        ${v.name}</span>`
                ).join('')}
        </div>
    `;
            }
        },
        {
            title: t('created_at'),
            type: 'object',
            key: 'created_at',
            modifier: (created_at => created_at ? formatDateToLocal(created_at) : '-')
        },

        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_specification_groups") || canAccess("delete_specification_groups")
        },
    ],
    filters: [],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess("update_specification_groups"),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess("delete_specification_groups"),
        }
    ],
})

const {isModalActive, selectedData, closeModal} = useOpenModal()
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${SPECIFICATION_GROUPS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${SPECIFICATION_GROUPS}/${row.id}`
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('specification_group_list')">
        </app-breadcrumb>

        <app-datatable :id="tableId"
                       :options="options"
                       :add-button-text="$t('add_specification_groups')"
                       :button-visible="canAccess('create_specification_groups')"
                       @buttonAction="isModalActive = true"
                       @action="actionTiger"/>

        <specification-group-modal v-if="isModalActive"
                                   :table-id="tableId"
                                   modal-id="specification-groups-modal"
                                   :selected-url="selectedData"
                                   @close="closeModal"/>

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled"/>

    </div>
</template>

<style scoped lang="scss"></style>