<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { EXPENSE_CATEGORY } from "@/services/endpoints/landlord.js";
import ExpenseCategoryModal from "@/views/landlord/pos/expenses/categories/ExpenseCategoryModal.vue";
import { Edit, Trash } from "@iconoir/vue";
import ExpenseModal from "@/views/landlord/pos/expenses/ExpenseModal.vue";
import router from "@/router/index.js";

const { canAccess } = usePermission();

const { t } = useI18n();

const tableId = ref('expense-category-table')

const options = ref({
    url: EXPENSE_CATEGORY,
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
            title: t('logo'),
            type: 'custom-html',
            key: 'logo',
            modifier: (logo) => logo
                ? `<img src="${logo}" style="width:36px;height:36px;object-fit:contain;border-radius:6px;border:1px solid #e5e7eb;background:#f9fafb;padding:2px;" />`
                : '<span style="color:#9ca3af;">—</span>',
        },
        {
            title: t('total_expense_count'),
            type: 'text',
            key: 'total_expense_count',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_expense_categories") || canAccess("delete_expense_categories")
        },
    ],
    filters: [],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess("update_expense_categories"),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess("delete_expense_categories"),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push(`/edit-expense-category/${row.id}`)
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${EXPENSE_CATEGORY}/${row.id}`
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('expense_category_list')">
        </app-breadcrumb>

        <app-datatable :id="tableId" :options="options" @buttonAction="isModalActive = true" @action="actionTiger" />

        <expense-category-modal v-if="isModalActive" :table-id="tableId" modal-id="expense-category-modal"
            :selected-url="selectedData" :tenant="profileData?.tenant" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

    </div>
</template>

<style scoped lang="scss"></style>