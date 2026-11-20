<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { PAGES } from "@/services/endpoints/Ecommerce.js";
import PageModal from "@/views/landlord/Ecommerce/SEO/Salamat/PageModal.vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import DescriptionColumn from "@/views/landlord/common/component/DescriptionColumn.vue";
import TagsColumn from "@/views/landlord/common/component/TagsColumn.vue";
import { Edit, Trash } from "@iconoir/vue";
import { textTruncate } from "@/utils/helper.js";
const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('page-table')

const options = ref({
    url: PAGES,
    pageSize: 5,
    rowLimit: 10,
    orderBy: 'desc',
    boxShadow: false,
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: false,
    columns: [
        {
            title: t('title'),
            type: 'text',
            key: 'title',
        },
        {
            title: t('slug'),
            type: 'text',
            key: 'slug',
        },
        {
            title: t('description'),
            type: 'component',
            key: 'description',
            componentName: h(DescriptionColumn)
        },
        {
            title: t('tags'),
            type: 'component',
            key: 'tags',
            componentName: h(TagsColumn)
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_pages") || canAccess("delete_pages"),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_pages'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_pages'),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()


const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${PAGES}/${row.id}?tenant_id=3`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${PAGES}/${row.id}`
    }
}

</script>

<template>
    <div>
        <app-datatable :id="tableId"
            :options="options"
            :add-button-text="$t('add_page')"
            :button-visible="canAccess('create_pages')"
            @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <page-modal v-if="isModalActive"
            :table-id="tableId"
            modal-id="page-modal"
            :selected-url="selectedData"
            @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

    </div>
</template>
<style scoped lang="scss"></style>
