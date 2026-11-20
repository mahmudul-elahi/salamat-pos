<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { BRANDS, BRAND_STATUS_CHANGE } from "@/services/endpoints/landlord.js";
import BrandModal from "@/views/landlord/pos/product/brand/BrandModal.vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Edit, Trash, Eye, EyeClosed, InfoCircle } from "@iconoir/vue";
import BrandProfile from "@/views/landlord/pos/HelperComponent/BrandProfile.vue";
import ConfirmationModal from "@/views/global/modal/ConfirmationModal.vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import router from "@/router/index.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const toast = useToast();
const emitter = useEmitter();
const tableId = ref('brand-table')
const statusChangeModal = ref(false);
const statusChangeData = ref(null);

const options = ref({
    url: BRANDS,
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
            type: 'component',
            key: 'name',
            componentName: h(BrandProfile),
        },
        {
            title: t('total_products'),
            type: 'object',
            key: 'products_count',
            modifier: (products_count => products_count ?? 0),
        },
        {
            title: t('tagline'),
            type: 'object',
            key: 'tagline',
            modifier: (tagline => tagline ?? '-'),
        },
        {
            title: t('primary_logo'),
            type: 'custom-html',
            key: 'secondary_logo',
            modifier: (primary_logo => primary_logo
                ? `<img src="${primary_logo}" style="width:40px;height:40px;border-radius:6px;object-fit:contain;border:1px solid #e5e7eb;background:#f9fafb;" />`
                : '<span style="color:#9ca3af;">—</span>'),
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
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status => status == 1
                ? `<span class="badge-square-light bg-success text-capitalize">${t('visible')}</span>`
                : `<span class="badge-square-light bg-danger text-capitalize">${t('hidden')}</span>`),
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_brands") || canAccess("delete_brands"),
        },
    ],
    filters: [
        {
            label: t('status'),
            type: 'checkbox',
            key: 'status',
            listKeyField: 'id',
            listValueField: 'name',
            options: [
                { id: 1, name: 'Active' },
                { id: 0, name: 'Inactive' },
            ],
            permission: true,
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: h(Edit),
            modifier: () => canAccess('update_brands'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: () => canAccess('delete_brands'),
        },
        {
            title: t('hide'),
            type: 'hide',
            icon: h(EyeClosed),
            modifier: (row => row.status == 1 && canAccess('update_brands')),
        },
        {
            title: t('show'),
            type: 'show',
            icon: h(Eye),
            modifier: (row => row.status != 1 && canAccess('update_brands')),
        },
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const toggleBrandStatus = (brand) => {
    Axios.patch(`${BRAND_STATUS_CHANGE}/${brand.id}/status`).then(({ data }) => {
        toast.success(data.message);
        emitter.emit('reload-' + tableId.value);
    }).catch(({ response }) => {
        if (response?.data?.message) toast.error(response.data.message);
    });
}

const openStatusChangeModal = (row) => {
    statusChangeData.value = { row, hiding: row.status == 1 };
    statusChangeModal.value = true;
};

const confirmStatusChange = () => {
    toggleBrandStatus(statusChangeData.value.row);
    statusChangeModal.value = false;
};

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${BRANDS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${BRANDS}/${row.id}`
    } else if (action.type === 'hide' || action.type === 'show') {
        openStatusChangeModal(row);
    }
}

const goToReorder = () => {
    router.push('/brands/order');
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('brands')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_brand')"
            :button-visible="canAccess('create_brands')" @buttonAction="isModalActive = true" @action="actionTiger">
            <template #extra-actions>
                <button v-if="canAccess('update_brands')" type="button" class="btn btn-primary-outline"
                    @click="goToReorder">
                    {{ $t('reorder_brands') }}
                </button>
            </template>
        </app-datatable>


        <brand-modal v-if="isModalActive" :table-id="tableId" modal-id="brand-modal" :selected-url="selectedData"
            @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <confirmation-modal v-if="statusChangeModal" modal-id="brandStatusChange" modal-class="text-warning"
            :icon="InfoCircle" :title="$t('are_you_sure')"
            :message="statusChangeData?.hiding ? $t('do_you_want_to_hide_this') : $t('do_you_want_to_show_this')"
            :table-id="tableId" :preloader="false" :first-button-name="$t('yes')" :second-button-name="$t('cancel')"
            @cancel="statusChangeModal = false" @confirm="confirmStatusChange" />

    </div>
</template>
<style scoped lang="scss"></style>
