<script setup>
import { markRaw, ref } from "vue";
import { useI18n } from "vue-i18n";
import {
    PRODUCTS, SELECTED_CATEGORY, SELECTED_BRAND, PRODUCTS_STATUS, PRODUCTS_WARRANTY, PRODUCTS_ECOMMERCE,
    PRODUCTS_CHINESE, PRODUCTS_UNOFFICIAL, PRODUCTS_REFURBISHED,
} from "@/services/endpoints/landlord.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { formatDateToLocal } from "@/utils/helper.js"
import usePermission from "@/composable/permission/usePermission.js";
import {
    CheckCircle,
    Edit,
    Trash,
    XmarkCircle,
    Plus,
    Running,
    BubbleIncome,
    ShieldPlusIn,
    MinusCircle,
    Heart,
    ShieldCheck,
    ShieldXmark,
    Shop,
    DatabaseScript,
    Globe,
    WarningTriangle,
    Xmark,
    RefreshCircle,
} from "@iconoir/vue";
import router from "@/router/index.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import ProductAttachment from "@/views/landlord/pos/product/ProductAttachment.vue";
import VariantModal from "@/views/landlord/pos/product/VariantModal.vue";
import CareModal from "@/views/landlord/pos/product/CareModal.vue";
import ProductSpecScrapeModal from "@/views/landlord/pos/product/ProductSpecScrapeModal.vue";
import ProductSpecManageModal from "@/views/landlord/pos/product/ProductSpecManageModal.vue";


const { canAccess } = usePermission();

const { t } = useI18n();

const tableId = ref('product-table')

const options = ref({
    url: PRODUCTS,
    pageSize: 5,
    rowLimit: 10,
    orderBy: 'desc',
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
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status => {
                return `<span class="badge-square-light bg-${status.color}">${status.name}</span>`
            })
        },
        {
            title: t('type_status'),
            type: 'custom-html',
            key: 'type_status',
            modifier: (status => {
                if (status.name === 'Active') return '-';
                return `<span class="badge-square-light bg-${status.color}">${status.name}</span>`
            })
        },
        {
            title: t('ecommerce'),
            type: 'custom-html',
            key: 'is_ecommerce',
            modifier: (is_ecommerce => {
                return is_ecommerce
                    ? `<span class="badge-square-light bg-success">${t('enabled')}</span>`
                    : `<span class="badge-square-light bg-warning">${t('disabled')}</span>`
            })
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
            permission: canAccess("update_products") || canAccess('update_product_status') || canAccess("delete_products") || canAccess("view_products_care") || canAccess("update_product_ecommerce")
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
        {
            label: t('status'),
            type: 'checkbox',
            key: 'status',
            listKeyField: 'id',
            listValueField: 'name',
            options: [
                { id: 1, name: 'Active' },
                { id: 0, name: 'Inactive' },
                { id: 6, name: 'Arrival' },
                { id: 7, name: 'Comming' },
                { id: 8, name: 'Featured' },
            ],
            permission: true,
        },
        {
            label: t('ecommerce'),
            type: 'search-select',
            key: 'is_ecommerce',
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            options: [
                { id: 'true', name: t('enabled') },
                { id: 'false', name: t('disabled') },
            ],
            permission: true,
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_products'),
        },
        {
            title: t('variants'),
            type: 'variant',
            icon: markRaw(Plus),
            modifier: () => canAccess('update_products_variant'),
        },
        {
            title: t('active'),
            type: 'active',
            key: 1,
            icon: markRaw(CheckCircle),
            modifier: (row => canAccess('update_product_status') && (
                row.status?.name === "Inactive"
            )),
        },
        {
            title: t('inactive'),
            type: 'inactive',
            key: 0,
            icon: markRaw(XmarkCircle),
            modifier: (row => canAccess('update_product_status') && (
                row.status?.name === "Active"
            )),
        },
        {
            title: 'Mark as Featured',
            type: 'feature',
            key: 8,
            icon: markRaw(Running),
            modifier: (row => canAccess('update_product_status') && (
                row.type_status?.name === "Active" ||
                row.type_status?.name === "Comming" ||
                row.type_status?.name === "Arrival"
            )),
        },
        {
            title: 'Remove Featured',
            type: 'unfeature',
            key: 1,
            icon: markRaw(MinusCircle),
            modifier: (row => canAccess('update_product_status') && row.type_status?.name === "Featured"),
        },
        {
            title: 'Mark as Coming Soon',
            type: 'coming_soon',
            key: 7,
            icon: markRaw(BubbleIncome),
            modifier: (row => canAccess('update_product_status') && (
                row.type_status?.name === "Active" ||
                row.type_status?.name === "Featured" ||
                row.type_status?.name === "Arrival"
            )),
        },
        {
            title: 'Remove Coming Soon',
            type: 'remove_coming_soon',
            key: 1,
            icon: markRaw(MinusCircle),
            modifier: (row => canAccess('update_product_status') && row.type_status?.name === "Comming"),
        },
        {
            title: 'Mark as Arrival',
            type: 'for_arrival',
            key: 6,
            icon: markRaw(ShieldPlusIn),
            modifier: (row => canAccess('update_product_status') && (
                row.type_status?.name === "Active" ||
                row.type_status?.name === "Featured" ||
                row.type_status?.name === "Comming"
            )),
        },
        {
            title: 'Remove Arrival',
            type: 'remove_arrival',
            key: 1,
            icon: markRaw(MinusCircle),
            modifier: (row => canAccess('update_product_status') && row.type_status?.name === "Arrival"),
        },
        {
            title: 'Specifications',
            type: 'manage_specs',
            icon: markRaw(DatabaseScript),
            modifier: () => canAccess('view_product_specifications'),
        },
        {
            title: 'Scrape Specifications',
            type: 'scrape_specs',
            icon: markRaw(DatabaseScript),
            modifier: () => canAccess('scrape_product_specifications') && canAccess('create_scrape_product_specifications'),
        },
        {
            title: 'Add Official Warranty',
            type: 'warranty_add',
            icon: markRaw(ShieldCheck),
            modifier: (row => canAccess('update_product_warranty') && !row.has_official_warranty),
        },
        {
            title: 'Remove Official Warranty',
            type: 'warranty_remove',
            icon: markRaw(ShieldXmark),
            modifier: (row => canAccess('update_product_warranty') && row.has_official_warranty),
        },
        {
            title: 'Enable Ecommerce',
            type: 'ecommerce_enable',
            icon: markRaw(Shop),
            modifier: (row => canAccess('update_product_ecommerce') && !row.is_ecommerce),
        },
        {
            title: 'Disable Ecommerce',
            type: 'ecommerce_disable',
            icon: markRaw(MinusCircle),
            modifier: (row => canAccess('update_product_ecommerce') && row.is_ecommerce),
        },
        {
            title: 'Add Chinese Badge',
            type: 'chinese_add',
            icon: markRaw(Globe),
            modifier: (row => canAccess('update_product_chinese') && !row.is_chinese),
        },
        {
            title: 'Remove Chinese Badge',
            type: 'chinese_remove',
            icon: markRaw(Xmark),
            modifier: (row => canAccess('update_product_chinese') && row.is_chinese),
        },
        {
            title: 'Add Unofficial Badge',
            type: 'unofficial_add',
            icon: markRaw(WarningTriangle),
            modifier: (row => canAccess('update_product_unofficial') && !row.is_unofficial),
        },
        {
            title: 'Remove Unofficial Badge',
            type: 'unofficial_remove',
            icon: markRaw(CheckCircle),
            modifier: (row => canAccess('update_product_unofficial') && row.is_unofficial),
        },
        {
            title: 'Add Refurbished Badge',
            type: 'refurbished_add',
            icon: markRaw(RefreshCircle),
            modifier: (row => canAccess('update_product_refurbished') && !row.is_refurbished),
        },
        {
            title: 'Remove Refurbished Badge',
            type: 'refurbished_remove',
            icon: markRaw(MinusCircle),
            modifier: (row => canAccess('update_product_refurbished') && row.is_refurbished),
        },
        {
            title: t('care'),
            type: 'care',
            icon: markRaw(Heart),
            modifier: () => canAccess('view_products_care'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_products'),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const isVariantModal = ref(false)
const closeVariantModal = () => {
    isVariantModal.value = false
    selectedData.value = null
}
const isScrapeModal = ref(false)
const closeScrapeModal = () => {
    isScrapeModal.value = false
    selectedData.value = null
}
const isSpecManageModal = ref(false)
const closeSpecManageModal = () => {
    isSpecManageModal.value = false
    selectedData.value = null
}
const isCareModal = ref(false)
const closeCareModal = () => {
    isCareModal.value = false
    selectedData.value = null
}
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const toast = useToast()
const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push({ name: 'editProduct', params: { id: row.id } })
    } else if (action.type === 'variant') {
        selectedData.value = row;
        isVariantModal.value = true;
    } else if (action.type === 'care') {
        selectedData.value = row;
        isCareModal.value = true;
    } else if (action.type === 'inactive' || action.type === 'active' || action.type === 'feature' || action.type === 'unfeature' || action.type === 'coming_soon' || action.type === 'remove_coming_soon' || action.type === 'for_arrival' || action.type === 'remove_arrival') {
        manageStatus(row, action)
    } else if (action.type === 'manage_specs') {
        selectedData.value = row;
        isSpecManageModal.value = true;
    } else if (action.type === 'scrape_specs') {
        selectedData.value = row;
        isScrapeModal.value = true;
    } else if (action.type === 'warranty_add' || action.type === 'warranty_remove') {
        toggleWarranty(row)
    } else if (action.type === 'ecommerce_enable' || action.type === 'ecommerce_disable') {
        toggleEcommerce(row)
    } else if (action.type === 'chinese_add' || action.type === 'chinese_remove') {
        toggleChinese(row)
    } else if (action.type === 'unofficial_add' || action.type === 'unofficial_remove') {
        toggleUnofficial(row)
    } else if (action.type === 'refurbished_add' || action.type === 'refurbished_remove') {
        toggleRefurbished(row)
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${PRODUCTS}/${row.id}`
    }
}
const emitter = useEmitter();
const toggleWarranty = (row) => {
    Axios.post(`${PRODUCTS_WARRANTY}/${row.id}`).then((response) => {
        toast.success(response.data.message)
        emitter.emit("reload-" + tableId.value);
    }).catch((error) => {
        toast.error(error.response.data.message)
    })
}
const toggleEcommerce = (row) => {
    Axios.post(`${PRODUCTS_ECOMMERCE}/${row.id}`).then((response) => {
        toast.success(response.data.message)
        emitter.emit("reload-" + tableId.value);
    }).catch((error) => {
        toast.error(error.response.data.message)
    })
}
const toggleChinese = (row) => {
    Axios.post(`${PRODUCTS_CHINESE}/${row.id}`).then((response) => {
        toast.success(response.data.message)
        emitter.emit("reload-" + tableId.value);
    }).catch((error) => {
        toast.error(error.response.data.message)
    })
}
const toggleUnofficial = (row) => {
    Axios.post(`${PRODUCTS_UNOFFICIAL}/${row.id}`).then((response) => {
        toast.success(response.data.message)
        emitter.emit("reload-" + tableId.value);
    }).catch((error) => {
        toast.error(error.response.data.message)
    })
}
const toggleRefurbished = (row) => {
    Axios.post(`${PRODUCTS_REFURBISHED}/${row.id}`).then((response) => {
        toast.success(response.data.message)
        emitter.emit("reload-" + tableId.value);
    }).catch((error) => {
        toast.error(error.response.data.message)
    })
}
const manageStatus = (row, action) => {
    Axios.post(`${PRODUCTS_STATUS}/${row.id}`, {
        status: action.key,
        type: action.type
    }).then((response) => {
        toast.success(response.data.message)
        emitter.emit("reload-" + tableId.value);
    }).catch((error) => {
        toast.error(error.response.data.message)
    })
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('products')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_product')"
            :button-visible="canAccess('create_products')" @buttonAction="router.push({ name: 'createProduct' })"
            @action="actionTiger" />


        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <VariantModal v-if="isVariantModal" modal-id="variant-modal" :product-id="selectedData?.id" :table-id="tableId"
            @close="closeVariantModal" />

        <CareModal v-if="isCareModal" modal-id="care-modal" :product-id="selectedData?.id" :table-id="tableId"
            @close="closeCareModal" />

        <ProductSpecScrapeModal v-if="isScrapeModal" modal-id="spec-scrape-modal" :product="selectedData"
            :table-id="tableId" @close="closeScrapeModal" />

        <ProductSpecManageModal v-if="isSpecManageModal" modal-id="spec-manage-modal" :product="selectedData"
            @close="closeSpecManageModal" />

    </div>
</template>
<style scoped lang="scss"></style>
