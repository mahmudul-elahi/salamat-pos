<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { Edit, Trash, CreditCard, Star, Notes, Attachment, ClockRotateRight } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { CUSTOMERS } from "@/services/endpoints/landlord.js";
import CustomerModal from "@/views/landlord/pos/people/customers/CustomerModal.vue";
import CustomerPaymentModal from "@/views/landlord/pos/people/customers/CustomerPaymentModal.vue";
import CustomerRatingModal from "@/views/landlord/pos/people/customers/CustomerRatingModal.vue";
import router from "@/router/index.js";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import SetNoteModal from "@/views/landlord/common/component/SetNoteModal.vue";
import PriceDocumentColumn from "@/views/landlord/common/component/PriceDocumentColumn.vue";
import PriceDocumentUploadModal from "@/views/landlord/common/component/PriceDocumentUploadModal.vue";
import AvatarProfile from "@/views/landlord/pos/people/customers/avater/AvatarProfile.vue";

const { canAccess, hasAnyPermission } = usePermission();

const CONTACT_HISTORY_PERMISSIONS = [
    'view_sales', 'view_sale_returns', 'view_purchases', 'view_purchase_returns',
    'view_orders', 'view_telesales', 'view_order_confirmations',
];

const { t } = useI18n();

const tableId = ref('customer-table')

const isRateModal = ref(false);
const selectedCustomerForRating = ref(null);

const isNoteModal = ref(false);
const selectedCustomerForNote = ref(null);

const isPriceDocumentModal = ref(false);
const selectedCustomerForPriceDocument = ref(null);

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
    url: CUSTOMERS,
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
            type: 'object',
            key: 'email',
            modifier: (email => email ? email : '-')
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
            permission: canAccess("create_sales_payment") || canAccess("update_customers") || canAccess("delete_customers") || canAccess("update_customer_ratings") || canAccess("update_customer_notes") || canAccess("update_customer_price_documents")
        },
    ],
    filters: [],
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
            modifier: () => canAccess('update_customers'),
        },
        {
            title: 'Rate Customer',
            type: 'rate',
            icon: markRaw(Star),
            modifier: () => canAccess('update_customer_ratings'),
        },
        {
            title: 'Set Note',
            type: 'note',
            icon: markRaw(Notes),
            modifier: () => canAccess('update_customer_notes'),
        },
        {
            title: 'Price Document',
            type: 'price_document',
            icon: markRaw(Attachment),
            modifier: () => canAccess('update_customer_price_documents'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_customers'),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

// Additional state for payment modal
const isPaymentModalActive = ref(false);
const selectedCustomerForPayment = ref(null);

const closePaymentModal = () => {
    isPaymentModalActive.value = false;
    selectedCustomerForPayment.value = null;
}


const actionTiger = (row, action) => {
    if (action.type === 'details') {
        router.push(`/customer-report-details/${row.id}`)
    } else if (action.type === 'history') {
        router.push(`/contact-history/${row.id}`)
    } else if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${CUSTOMERS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${CUSTOMERS}/${row.id}`
    } else if (action.type === 'rate') {
        selectedCustomerForRating.value = row
        isRateModal.value = true
    } else if (action.type === 'note') {
        selectedCustomerForNote.value = row
        isNoteModal.value = true
    } else if (action.type === 'price_document') {
        selectedCustomerForPriceDocument.value = row
        isPriceDocumentModal.value = true
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('offline_customers')">
        </app-breadcrumb>

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_customer')"
            :button-visible="canAccess('create_customers')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <customer-modal v-if="isModalActive" :table-id="tableId" modal-id="customer-modal" :selected-url="selectedData"
            @close="closeModal" />



        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <customer-rating-modal v-if="isRateModal" modal-id="customer-rating-modal" :table-id="tableId"
            :customer="selectedCustomerForRating" :selected-url="`${CUSTOMERS}/${selectedCustomerForRating.id}/rating`"
            @close="isRateModal = false; selectedCustomerForRating = null" />

        <set-note-modal v-if="isNoteModal" modal-id="customer-note-modal" :table-id="tableId"
            :contact="selectedCustomerForNote" :selected-url="`${CUSTOMERS}/${selectedCustomerForNote.id}/note`"
            @close="isNoteModal = false; selectedCustomerForNote = null" />

        <price-document-upload-modal v-if="isPriceDocumentModal" modal-id="customer-price-document-modal"
            :table-id="tableId" :contact="selectedCustomerForPriceDocument"
            :upload-url="`${CUSTOMERS}/${selectedCustomerForPriceDocument.id}/price-document-chunk`"
            @close="isPriceDocumentModal = false; selectedCustomerForPriceDocument = null" />

    </div>
</template>

<style scoped lang="scss"></style>
