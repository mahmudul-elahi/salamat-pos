<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { ATTRIBUTES } from "@/services/endpoints/landlord.js";
import AttributeModal from "@/views/landlord/pos/product/attributes/AttributeModal.vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { formatDateToLocal } from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Edit, Trash } from "@iconoir/vue";
const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('attribute-table')

const options = ref({
    url: ATTRIBUTES,
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
            title: t('value'),
            type: 'custom-html',
            key: 'values',
            modifier: (values) => {
                if (!values?.length) return '';

                return `
        <div class="d-flex flex-wrap" style="width: 60%">
            ${values.map(v =>
                        `<span class="badge-square-light text-capitalize bg-primary me-1 mb-1 d-inline-block" style="min-width:80px; text-align:center;">
                        ${v.value}</span>`
                        ).join('')}
        </div>
    `;
            }
        },

        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_attributes") || canAccess("delete_attributes")
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_attributes'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess("delete_attributes"),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()



const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${ATTRIBUTES}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${ATTRIBUTES}/${row.id}`
    }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('attributes')" />

        <app-datatable :id="tableId"
            :options="options"
            :add-button-text="$t('add_attribute')"
            :button-visible="canAccess('create_attributes')"
            @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <attribute-modal v-if="isModalActive"
            :table-id="tableId"
            modal-id="attribute-modal"
            :selected-url="selectedData"
            @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

    </div>
</template>
<style scoped lang="scss"></style>