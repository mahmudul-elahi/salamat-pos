<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Edit } from "@iconoir/vue";
import { ALL_PAGES, PAGES } from "@/services/endpoints/Ecommerce.js";
import PageModal from "@/views/landlord/Ecommerce/page/PageModal.vue";
import PageProfile from "@/views/landlord/pos/HelperComponent/PageProfile.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('all-pages-table')

const options = ref({
    url: ALL_PAGES,
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
            title: t('title'),
            type: 'component',
            key: 'title',
            componentName: h(PageProfile),
        },
        {
            title: t('slug'),
            type: 'text',
            key: 'slug',
        },
        {
            title: t('shop'),
            type: 'object',
            key: 'tenant',
            modifier: (tenant => tenant?.name)
        },
        {
            title: t('shop_banner'),
            type: 'custom-html',
            key: 'shop_banner',
            modifier: (shop_banner => shop_banner
                ? `<img src="${shop_banner}" style="width:64px;height:40px;border-radius:6px;object-fit:cover;border:1px solid #e5e7eb;background:#f9fafb;" />`
                : '<span style="color:#9ca3af;">—</span>'),
        },
        {
            title: t('info_banner'),
            type: 'custom-html',
            key: 'info_banner',
            modifier: (info_banner => info_banner
                ? `<img src="${info_banner}" style="width:64px;height:40px;border-radius:6px;object-fit:cover;border:1px solid #e5e7eb;background:#f9fafb;" />`
                : '<span style="color:#9ca3af;">—</span>'),
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_all_pages"),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_all_pages'),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const rowData = ref({})
const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${PAGES}/${row.id}`
        rowData.value = row
    }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('pages')" />

        <app-datatable :id="tableId" :options="options" @buttonAction="isModalActive = true" @action="actionTiger" />

        <page-modal v-if="isModalActive" :table-id="tableId" modal-id="page-modal" :selected-url="selectedData"
            :rowData="rowData" @close="closeModal" />


    </div>
</template>
<style scoped lang="scss"></style>
