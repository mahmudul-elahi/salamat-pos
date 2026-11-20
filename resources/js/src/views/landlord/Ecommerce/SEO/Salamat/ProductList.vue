<script setup>
import { markRaw, ref } from "vue";
import { useI18n } from "vue-i18n";
import {
    PRODUCTS, SELECTED_CATEGORY, SELECTED_BRAND, PRODUCTS_STATUS, SEO_MANAGE,
} from "@/services/endpoints/landlord.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { formatDateToLocal } from "@/utils/helper.js"
import usePermission from "@/composable/permission/usePermission.js";
import { Settings } from "@iconoir/vue";
import router from "@/router/index.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import ProductAttachment from "@/views/landlord/pos/product/ProductAttachment.vue";
import SeoManageModal from "@/views/landlord/Ecommerce/SEO/Salamat/SeoManageModal.vue";


const { canAccess } = usePermission();

const { t } = useI18n();

const tableId = ref('product-table')

const options = ref({
    url: PRODUCTS,
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
    showFilter: true,
    columns: [
        {
            title: t('name'),
            type: 'component',
            key: 'product_name',
            componentName: markRaw(ProductAttachment),
        },
        {
            title: t('category'),
            type: 'object',
            key: 'category',
            modifier: (category => category?.name)
        },
        {
            title: t('brand'),
            type: 'object',
            key: 'brand',
            modifier: (brand => brand?.name)
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
            permission: canAccess("update_product_seo")
        },
    ],
    filters: [
        {
            label: t('category'),
            type: 'advance-search-select',
            key: 'category',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_CATEGORY}`,
            permission: true,
        },
        {
            label: t('brand'),
            type: 'advance-search-select',
            key: 'brand',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_BRAND}`,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('manage_seo'),
            type: 'manage_seo',
            icon: markRaw(Settings),
            modifier: () => canAccess("update_product_seo"),
        },
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const toast = useToast()
const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push({ name: 'editProduct', params: { id: row.id } })
    } else if (action.type === 'inactive' || action.type === 'active') {
        manageStatus(row, action)
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${PRODUCTS}/${row.id}`
    } else if (action.type === 'manage_seo') {
        isModalActive.value = true
        selectedData.value = `${SEO_MANAGE}/product/${row.id}?tenant_id=3`
    }
}
const emitter = useEmitter();
const manageStatus = (row, action) => {
    Axios.post(`${PRODUCTS_STATUS}/${row.id}`, {
        status: action.key
    }).then((response) => {
        toast.success(response.data.message)
        emitter.emit("reload-" + tableId.value);
    }).catch((error) => {
        toast.error(error.response.data.message)
    })
}

</script>

<template>
    <div>
        <app-datatable :id="tableId" :options="options" :button-visible="false" @action="actionTiger" />


        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <seo-manage-modal v-if="isModalActive" :table-id="tableId" modal-id="product-seo-modal"
            :selected-url="selectedData" @close="closeModal" />

    </div>
</template>
<style scoped lang="scss"></style>
