<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { CATEGORIES } from "@/services/endpoints/landlord.js";
import CategoryModal from "@/views/landlord/pos/product/category/CategoryModal.vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Edit, Trash, Eye, EyeClosed, InfoCircle } from "@iconoir/vue";
import ConfirmationModal from "@/views/global/modal/ConfirmationModal.vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import router from "@/router/index.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const toast = useToast();
const emitter = useEmitter();
const tableId = ref('categories-table')
const statusChangeModal = ref(false);
const statusChangeData = ref(null);

const getInitials = (name) => {
    const parts = (name || '').trim().split(' ').filter(Boolean);
    const first = parts[0] ? parts[0][0] : '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return `${first}${last}`.toUpperCase() || 'NA';
};

const avatarHtml = (name, image, bg, color) => {
    if (image) {
        return `<img src="${image}" style="width:24px;height:24px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;margin-right:6px;vertical-align:middle;flex-shrink:0;" />`;
    }
    return `<span style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:${bg};color:${color};font-size:10px;font-weight:600;margin-right:6px;vertical-align:middle;flex-shrink:0;">${getInitials(name)}</span>`;
};

const options = ref({
    url: CATEGORIES,
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
            type: 'custom-html',
            key: 'name',
            modifier: (name, row) => {
                const avatar = avatarHtml(name, row.logo, '#e0f2f1', '#00a09d');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${name}</span></div>`;
            },
        },
        {
            title: t('parent_category'),
            type: 'object',
            key: 'parent',
            modifier: (parent => parent?.name ?? '-'),
        },
        {
            title: t('total_products'),
            type: 'object',
            key: 'products_count',
            modifier: (products_count => products_count ?? 0),
        },
        {
            title: t('secondary_logo'),
            type: 'custom-html',
            key: 'secondary_logo',
            modifier: (secondary_logo => secondary_logo
                ? `<img src="${secondary_logo}" style="width:40px;height:40px;border-radius:6px;object-fit:contain;border:1px solid #e5e7eb;background:#f9fafb;" />`
                : '<span style="color:#9ca3af;">—</span>'),
        },
        {
            title: t('banner'),
            type: 'custom-html',
            key: 'banner',
            modifier: (banner => banner
                ? `<img src="${banner}" style="width:64px;height:40px;border-radius:6px;object-fit:cover;border:1px solid #e5e7eb;background:#f9fafb;" />`
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
            permission: canAccess("update_categories") || canAccess("delete_categories"),
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
            modifier: () => canAccess('update_categories'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: () => canAccess('delete_categories'),
        },
        {
            title: t('hide'),
            type: 'hide',
            icon: h(EyeClosed),
            modifier: (row => row.status == 1 && canAccess('update_categories')),
        },
        {
            title: t('show'),
            type: 'show',
            icon: h(Eye),
            modifier: (row => row.status != 1 && canAccess('update_categories')),
        },
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const toggleCategoryStatus = (category) => {
    Axios.patch(`${CATEGORIES}/${category.id}/status`).then(({ data }) => {
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
    toggleCategoryStatus(statusChangeData.value.row);
    statusChangeModal.value = false;
};

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${CATEGORIES}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${CATEGORIES}/${row.id}`
    } else if (action.type === 'hide' || action.type === 'show') {
        openStatusChangeModal(row);
    }
}

const goToReorder = () => {
    router.push('/categories/order');
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('categories')"/>

        <app-datatable :id="tableId"
                       :options="options"
                       @action="actionTiger">
            <template #extra-actions>
                <button v-if="canAccess('update_categories')" type="button" class="btn btn-primary-outline"
                    @click="goToReorder">
                    {{ $t('reorder_category') }}
                </button>
            </template>
        </app-datatable>

        <category-modal v-if="isModalActive"
                        :table-id="tableId"
                        modal-id="category-modal"
                        :selected-url="selectedData"
                        @close="closeModal"/>

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled"/>

        <confirmation-modal
            v-if="statusChangeModal"
            modal-id="categoryStatusChange"
            modal-class="text-warning"
            :icon="InfoCircle"
            :title="$t('are_you_sure')"
            :message="statusChangeData?.hiding ? $t('do_you_want_to_hide_this') : $t('do_you_want_to_show_this')"
            :table-id="tableId"
            :preloader="false"
            :first-button-name="$t('yes')"
            :second-button-name="$t('cancel')"
            @cancel="statusChangeModal = false"
            @confirm="confirmStatusChange" />

    </div>
</template>
<style scoped lang="scss"></style>
