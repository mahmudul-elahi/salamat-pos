<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { INVESTMENT_CATEGORY } from "@/services/endpoints/landlord.js";
import { Edit, Trash } from "@iconoir/vue";
import router from "@/router/index.js";
import DirectorCategoryProfile from "@/views/landlord/pos/HelperComponent/DirectorCategoryProfile.vue";
import WareHouseProfile from "@/views/landlord/pos/HelperComponent/WareHouseProfile.vue";

const { canAccess } = usePermission();

const { t } = useI18n();

const tableId = ref('investment-category-table')

const options = ref({
    url: INVESTMENT_CATEGORY,
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
            title: 'Total Deposit Count',
            type: 'text',
            key: 'investments_count',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_investment_categories") || canAccess("delete_investment_categories")
        },
    ],
    filters: [],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: h(Edit),
            modifier: () => canAccess("update_investment_categories"),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: () => canAccess("delete_investment_categories"),
        }
    ],
})

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push(`/edit-investment-category/${row.id}`)
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${INVESTMENT_CATEGORY}/${row.id}`
    }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('director')" />

        <app-datatable :id="tableId" :options="options" @buttonAction="isModalActive = true" @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

    </div>
</template>

<style scoped lang="scss"></style>
