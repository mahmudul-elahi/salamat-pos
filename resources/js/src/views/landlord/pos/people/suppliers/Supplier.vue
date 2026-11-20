<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { Edit, Trash, Star, Notes, Attachment, ClockRotateRight } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { SUPPLIERS } from "@/services/endpoints/landlord.js";
import SupplierModal from "@/views/landlord/pos/people/suppliers/SupplierModal.vue";
import SupplierRatingModal from "@/views/landlord/pos/people/suppliers/SupplierRatingModal.vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import SetNoteModal from "@/views/landlord/common/component/SetNoteModal.vue";
import PriceDocumentColumn from "@/views/landlord/common/component/PriceDocumentColumn.vue";
import PriceDocumentUploadModal from "@/views/landlord/common/component/PriceDocumentUploadModal.vue";
import AvatarProfile from "@/views/landlord/pos/people/customers/avater/AvatarProfile.vue";
import router from "@/router/index.js";

const { canAccess, hasAnyPermission } = usePermission();

const CONTACT_HISTORY_PERMISSIONS = [
    'view_sales', 'view_sale_returns', 'view_purchases', 'view_purchase_returns',
    'view_orders', 'view_telesales', 'view_order_confirmations',
];

// const store = useProfileStore();
// const { profileData } = storeToRefs(store);

const { t } = useI18n();

const tableId = ref('supplier-table')

const isRateModal = ref(false);
const selectedSupplierForRating = ref(null);

const isNoteModal = ref(false);
const selectedSupplierForNote = ref(null);

const isPriceDocumentModal = ref(false);
const selectedSupplierForPriceDocument = ref(null);

const getInitials = (name) => {
    const parts = (name || '').trim().split(' ').filter(Boolean);
    const first = parts[0] ? parts[0][0] : '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return `${first}${last}`.toUpperCase() || 'NA';
};

const businessHtml = (name, logo) => {
    if (!name) return '<span style="color:#9ca3af;">—</span>';
    const avatar = logo
        ? `<img src="${logo}" style="width:28px;height:28px;border-radius:6px;object-fit:cover;border:1px solid #e5e7eb;margin-right:6px;vertical-align:middle;" />`
        : `<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:#e0f2f1;color:#00a09d;font-size:10px;font-weight:600;margin-right:6px;vertical-align:middle;">${getInitials(name)}</span>`;
    return `<div style="display:flex;align-items:center;">${avatar}<span>${name}</span></div>`;
};

const options = ref({
    url: SUPPLIERS,
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
            componentName: h(AvatarProfile),
        },
        {
            title: t('business_name'),
            type: 'custom-html',
            key: 'business_name',
            modifier: (name, row) => businessHtml(name, row.business_logo),
        },
        {
            title: t('phone_number'),
            type: 'text',
            key: 'phone',
        },
        {
            title: t('email'),
            type: 'text',
            key: 'email',
        },
        {
            title: t('address'),
            type: 'component',
            key: 'address',
            componentName: h(AddressColumn)
        },
        {
            title: t('rating'),
            type: 'custom-html',
            key: 'rating',
            modifier: (rating) => {
                if (!rating) return '-';
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += `<span style="color:${i <= rating ? '#f5b301' : '#d9d9d9'};font-size:16px;">&#9733;</span>`;
                }
                return `<span class="text-nowrap">${stars}</span>`;
            },
        },
        {
            title: t('note'),
            type: 'component',
            key: 'note',
            componentName: h(NoteColumn),
        },
        {
            title: t('price_document'),
            type: 'component',
            key: 'price_document',
            componentName: h(PriceDocumentColumn),
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_suppliers') || canAccess('delete_suppliers') || canAccess('update_supplier_ratings') || canAccess('update_supplier_notes') || canAccess('update_supplier_price_documents')
        },
    ],
    filters: [
        // {
        //     label: t('warehouse_or_store_name'),
        //     type: 'advance-search-select',
        //     key: 'shop',
        //     listKeyField: 'id',
        //     listValueField: 'name',
        //     rowPerLoad: 10,
        //     fetchUrl: `${SELECTED_SHOPS}`,
        //     permission: canFilterByShop(),
        // },
    ],
    actions: [
        {
            title: t('history'),
            type: 'history',
            icon: markRaw(ClockRotateRight),
            modifier: () => hasAnyPermission(CONTACT_HISTORY_PERMISSIONS),
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_suppliers'),
        },

        {
            title: 'Rate Supplier',
            type: 'rate',
            icon: markRaw(Star),
            modifier: () => canAccess('update_supplier_ratings'),
        },
        {
            title: 'Set Note',
            type: 'note',
            icon: markRaw(Notes),
            modifier: () => canAccess('update_supplier_notes'),
        },
        {
            title: 'Price Document',
            type: 'price_document',
            icon: markRaw(Attachment),
            modifier: () => canAccess('update_supplier_price_documents'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_suppliers'),
        },
        // {
        //     title: t('view_details'),
        //     type: 'supplier_report_details',
        //     icon: markRaw(Eye),
        //     modifier: () => canAccess('view_supplier_purchase_reports') ||
        //         canAccess('view_supplier_purchase_return_reports') ||
        //         canAccess('view_supplier_purchase_transaction_reports') ||
        //         canAccess('view_supplier_purchase_return_transaction_reports') ||
        //         canAccess('view_supplier_ledger_reports'),
        // },
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'history') {
        router.push(`/contact-history/${row.id}`)
    } else if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${SUPPLIERS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${SUPPLIERS}/${row.id}`
    } else if (action.type === 'supplier_report_details') {
        router.push(`/supplier-report-details/${row.id}`)
    } else if (action.type === 'rate') {
        selectedSupplierForRating.value = row
        isRateModal.value = true
    } else if (action.type === 'note') {
        selectedSupplierForNote.value = row
        isNoteModal.value = true
    } else if (action.type === 'price_document') {
        selectedSupplierForPriceDocument.value = row
        isPriceDocumentModal.value = true
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('supplier_list')">
        </app-breadcrumb>

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_supplier')"
            :button-visible="canAccess('create_suppliers')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <supplier-modal v-if="isModalActive" :table-id="tableId" modal-id="supplier-modal" :selected-url="selectedData"
            @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <supplier-rating-modal v-if="isRateModal" modal-id="supplier-rating-modal" :table-id="tableId"
            :supplier="selectedSupplierForRating" :selected-url="`${SUPPLIERS}/${selectedSupplierForRating.id}/rating`"
            @close="isRateModal = false; selectedSupplierForRating = null" />

        <set-note-modal v-if="isNoteModal" modal-id="supplier-note-modal" :table-id="tableId"
            :contact="selectedSupplierForNote" :selected-url="`${SUPPLIERS}/${selectedSupplierForNote.id}/note`"
            @close="isNoteModal = false; selectedSupplierForNote = null" />

        <price-document-upload-modal v-if="isPriceDocumentModal" modal-id="supplier-price-document-modal"
            :table-id="tableId" :contact="selectedSupplierForPriceDocument"
            :upload-url="`${SUPPLIERS}/${selectedSupplierForPriceDocument.id}/price-document-chunk`"
            @close="isPriceDocumentModal = false; selectedSupplierForPriceDocument = null" />

    </div>
</template>

<style scoped lang="scss"></style>
