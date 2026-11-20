<script setup>
import { ref, h, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { Eye, Star, Notes, Attachment } from "@iconoir/vue";
import { ONLINE_CONTACTS } from "@/services/endpoints/landlord.js";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import SetNoteModal from "@/views/landlord/common/component/SetNoteModal.vue";
import PriceDocumentColumn from "@/views/landlord/common/component/PriceDocumentColumn.vue";
import PriceDocumentUploadModal from "@/views/landlord/common/component/PriceDocumentUploadModal.vue";
import AvatarProfile from "@/views/landlord/pos/people/customers/avater/AvatarProfile.vue";
import OnlineCustomerViewModal from "@/views/landlord/pos/people/contact/OnlineCustomerViewModal.vue";
import RateCustomerModal from "@/views/landlord/pos/people/contact/RateCustomerModal.vue";
import usePermission from "@/composable/permission/usePermission.js";

const { t } = useI18n();
const { canAccess } = usePermission();

const tableId = ref('online-customer-table');
const selectedCustomer = ref(null);
const isViewModal = ref(false);
const isRateModal = ref(false);
const isNoteModal = ref(false);
const isPriceDocumentModal = ref(false);

const options = ref({
    url: ONLINE_CONTACTS,
    pageSize: 10,
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
            title: t('phone_number'),
            type: 'text',
            key: 'phone',
        },
        {
            title: t('email'),
            type: 'object',
            key: 'email',
            modifier: (email) => email ?? '-',
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
            title: t('date_of_birth'),
            type: 'text',
            key: 'date_of_birth',
        },
        {
            title: t('gender'),
            type: 'custom-html',
            key: 'gender',
            modifier: (gender) => {
                if (gender == 'male') {
                    return `<span class="badge-square-light bg-primary">${t('male')}</span>`;
                } else if (gender == 'female') {
                    return `<span class="badge-square-light bg-danger">${t('female')}</span>`;
                } else {
                    return `<span class="badge-square-light bg-warning">${t('other')}</span>`;
                }
            },
        },

        {
            title: t('created_at'),
            type: 'text',
            key: 'created_at',
        },
        {
            title: t('address'),
            type: 'component',
            key: 'address',
            componentName: h(AddressColumn),
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: true,
        },
    ],
    filters: [],
    actions: [
        {
            title: t('view'),
            icon: markRaw(Eye),
            type: 'view',
            modifier: () => true,
        },
        {
            title: 'Rate Customer',
            icon: markRaw(Star),
            type: 'rate',
            modifier: () => canAccess('update_online_contacts'),
        },
        {
            title: 'Set Note',
            icon: markRaw(Notes),
            type: 'note',
            modifier: () => canAccess('update_online_contacts'),
        },
        {
            title: 'Price Document',
            icon: markRaw(Attachment),
            type: 'price_document',
            modifier: () => canAccess('update_online_contact_price_documents'),
        },
    ],
});

const actionTiger = (row, action) => {
    if (action.type === 'view') {
        selectedCustomer.value = row;
        isViewModal.value = true;
    } else if (action.type === 'rate') {
        selectedCustomer.value = row;
        isRateModal.value = true;
    } else if (action.type === 'note') {
        selectedCustomer.value = row;
        isNoteModal.value = true;
    } else if (action.type === 'price_document') {
        selectedCustomer.value = row;
        isPriceDocumentModal.value = true;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('online_customer')" />
        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

        <online-customer-view-modal v-if="isViewModal" modal-id="online-customer-view-modal"
            :customer="selectedCustomer" @close="isViewModal = false; selectedCustomer = null" />

        <rate-customer-modal v-if="isRateModal" modal-id="rate-customer-modal" :table-id="tableId"
            :customer="selectedCustomer" :selected-url="`${ONLINE_CONTACTS}/${selectedCustomer.id}`"
            @close="isRateModal = false; selectedCustomer = null" />

        <set-note-modal v-if="isNoteModal" modal-id="online-customer-note-modal" :table-id="tableId"
            :contact="selectedCustomer" :selected-url="`${ONLINE_CONTACTS}/${selectedCustomer.id}`"
            @close="isNoteModal = false; selectedCustomer = null" />

        <price-document-upload-modal v-if="isPriceDocumentModal" modal-id="online-customer-price-document-modal"
            :table-id="tableId" :contact="selectedCustomer"
            :upload-url="`${ONLINE_CONTACTS}/${selectedCustomer.id}/price-document-chunk`"
            @close="isPriceDocumentModal = false; selectedCustomer = null" />
    </div>
</template>
