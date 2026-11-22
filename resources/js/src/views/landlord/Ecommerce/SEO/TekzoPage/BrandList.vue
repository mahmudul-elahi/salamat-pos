<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { BRANDS, SEO_MANAGE } from "@/services/endpoints/landlord.js";
import BrandModal from "@/views/landlord/pos/product/brand/BrandModal.vue";
import SeoManageModal from "@/views/landlord/Ecommerce/SEO/TekzoPage/SeoManageModal.vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { formatDateToLocal } from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Settings } from "@iconoir/vue";
const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('brand-table')

const options = ref({
    url: BRANDS,
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
            title: t('name'),
            type: 'text',
            key: 'name',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_brand_seo"),
        },
    ],
    actions: [
        {
            title: t('manage_seo'),
            type: 'manage_seo',
            icon: markRaw(Settings),
            modifier: () => canAccess('update_brand_seo'),
        },
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()



const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${BRANDS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${BRANDS}/${row.id}`
    } else if (action.type === 'manage_seo') {
        isModalActive.value = true
        selectedData.value = `${SEO_MANAGE}/brand/${row.id}`
    }
}

</script>

<template>
    <div>
        <app-datatable :id="tableId" :options="options" :button-visible="false" @action="actionTiger" />

        <brand-modal v-if="isModalActive && false" :table-id="tableId" modal-id="brand-modal"
            :selected-url="selectedData" @close="closeModal" />

        <seo-manage-modal v-if="isModalActive" :table-id="tableId" modal-id="brand-seo-modal"
            :selected-url="selectedData" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

    </div>
</template>
<style scoped lang="scss"></style>
