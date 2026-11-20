<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { INCOME_CATEGORY } from "@/services/endpoints/landlord.js";
import IncomeCategoryModal from "@/views/landlord/pos/income/category/IncomeCategoryModal.vue";
import WareHouseProfile from "@/views/landlord/pos/HelperComponent/WareHouseProfile.vue";
import DirectorCategoryProfile from "@/views/landlord/pos/HelperComponent/DirectorCategoryProfile.vue";
import { Edit, Trash } from "@iconoir/vue";
import router from "@/router/index.js";
const { canAccess } = usePermission();

const { t } = useI18n();

const tableId = ref('income-category-table')

const options = ref({
    url: INCOME_CATEGORY,
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
            title: t('Shop'),
            type: 'component',
            key: 'tenant',
            componentName: h(WareHouseProfile),
        },
        {
            title: t('name'),
            type: 'component',
            key: 'director',
            componentName: h(DirectorCategoryProfile),
        },
        {
            title: t('total_income_count'),
            type: 'text',
            key: 'total_income_count',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_income_categories") || canAccess("delete_income_categories")
        },
    ],
    filters: [],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: h(Edit),
            modifier: () => canAccess("update_income_categories"),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: () => canAccess("delete_income_categories"),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push(`/edit-income-category/${row.id}`)
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${INCOME_CATEGORY}/${row.id}`
    }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('director')">
        </app-breadcrumb>

        <app-datatable :id="tableId" :options="options" @buttonAction="isModalActive = true" @action="actionTiger" />

        <income-category-modal v-if="isModalActive" :table-id="tableId" modal-id="income-category-modal"
            :selected-url="selectedData" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

    </div>
</template>

<style scoped lang="scss"></style>
