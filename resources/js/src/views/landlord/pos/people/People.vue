<script setup>
import { computed, h, markRaw, ref } from "vue";
import { useI18n } from "vue-i18n";
import { Edit, Trash, Eye, Star, Notes, Attachment, ClockRotateRight } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { PEOPLE, PEOPLE_BULK_DELETE, CUSTOMERS, SUPPLIERS, ONLINE_CONTACTS, SELECTED_DISTRICTS, SELECTED_AREAS } from "@/services/endpoints/landlord.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import router from "@/router/index.js";

import PeopleCreateModal from "@/views/landlord/pos/people/PeopleCreateModal.vue";
import CustomerModal from "@/views/landlord/pos/people/customers/CustomerModal.vue";
import SupplierModal from "@/views/landlord/pos/people/suppliers/SupplierModal.vue";
import CustomerRatingModal from "@/views/landlord/pos/people/customers/CustomerRatingModal.vue";
import SupplierRatingModal from "@/views/landlord/pos/people/suppliers/SupplierRatingModal.vue";
import RateCustomerModal from "@/views/landlord/pos/people/contact/RateCustomerModal.vue";
import OnlineCustomerViewModal from "@/views/landlord/pos/people/contact/OnlineCustomerViewModal.vue";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import SetNoteModal from "@/views/landlord/common/component/SetNoteModal.vue";
import PriceDocumentColumn from "@/views/landlord/common/component/PriceDocumentColumn.vue";
import PriceDocumentUploadModal from "@/views/landlord/common/component/PriceDocumentUploadModal.vue";
import AvatarProfile from "@/views/landlord/pos/people/customers/avater/AvatarProfile.vue";

const { canAccess, hasAnyPermission, isSupperAdmin } = usePermission();
const { t } = useI18n();

const CONTACT_HISTORY_PERMISSIONS = [
    'view_timeline_people_history', 'view_sales', 'view_sale_returns', 'view_purchases', 'view_purchase_returns',
    'view_orders', 'view_telesales', 'view_order_confirmations',
];

// A row's type + online flag decide which endpoint/permission/modal apply —
// the three source pages (Customer/Supplier/OnlineCustomer) each hardcoded
// their own, this just picks the right one per row.
const rowKind = (row) => row.online ? 'online' : (row.type === 'supplier' ? 'supplier' : 'offline');
const baseUrl = (row) => ({ online: ONLINE_CONTACTS, supplier: SUPPLIERS, offline: CUSTOMERS }[rowKind(row)]);

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

const typeBadge = (row) => ({
    online: `<span class="badge-square-light bg-info">${t('online_customer')}</span>`,
    supplier: `<span class="badge-square-light bg-warning">${t('supplier')}</span>`,
    offline: `<span class="badge-square-light bg-success">${t('offline_customer')}</span>`,
}[rowKind(row)]);

const tableId = ref('people-table');
const currentFilters = ref({});

const isCreateModalActive = ref(false);

const isModalActive = ref(false);
const editingType = ref(null); // 'offline' | 'supplier' — no edit modal for online
const selectedData = ref('');

const isViewModal = ref(false);
const isRateModal = ref(false);
const isNoteModal = ref(false);
const isPriceDocumentModal = ref(false);
const selectedRow = ref(null);

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const selectedRows = ref([]);
// Only rows this admin is actually allowed to delete get bulk-removed -
// silently including everything selected would let a partial delete look
// like it succeeded for rows the API was always going to reject anyway.
const deletableSelectedRows = computed(() => selectedRows.value.filter((row) => {
    const kind = rowKind(row);
    return (kind === 'offline' && canAccess('delete_customers'))
        || (kind === 'supplier' && canAccess('delete_suppliers'))
        || (kind === 'online' && canAccess('delete_online_contacts'));
}));
const isBulkDeleteModal = ref(false);
const bulkDeletePayload = computed(() => ({ ids: deletableSelectedRows.value.map((row) => row.id) }));

const options = ref({
    url: PEOPLE,
    pageSize: 10,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: isSupperAdmin(),
    enableBulkSelect: isSupperAdmin(),
    showSearch: true,
    showFilter: true,
    columns: [
        {
            title: t('name'),
            type: 'component',
            key: 'name',
            componentName: h(AvatarProfile),
        },
        {
            title: t('type'),
            type: 'custom-html',
            key: 'type',
            modifier: (_, row) => typeBadge(row),
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
            modifier: (email => email || '-'),
        },
        {
            title: t('address'),
            type: 'component',
            key: 'address',
            componentName: h(AddressColumn),
        },
        {
            title: t('district'),
            type: 'object',
            key: 'district',
            modifier: (district => district?.name || '-'),
        },
        {
            title: t('area'),
            type: 'object',
            key: 'area',
            modifier: (area => area?.name || '-'),
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
            title: t('created_at'),
            type: 'text',
            key: 'created_at',
        },
        {
            title: t('date_of_birth'),
            type: 'object',
            key: 'date_of_birth',
            modifier: (dob => dob || '-'),
        },
        {
            title: t('gender'),
            type: 'custom-html',
            key: 'gender',
            modifier: (gender) => {
                if (gender === 'male') return `<span class="badge-square-light bg-primary">${t('male')}</span>`;
                if (gender === 'female') return `<span class="badge-square-light bg-danger">${t('female')}</span>`;
                if (gender === 'other') return `<span class="badge-square-light bg-warning">${t('other')}</span>`;
                return '-';
            },
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_customers') || canAccess('delete_customers')
                || canAccess('update_suppliers') || canAccess('delete_suppliers')
                || canAccess('update_online_contacts'),
        },
    ],
    filters: [
        {
            label: t('type'),
            type: 'search-select',
            key: 'contact_type',
            showSearch: false,
            permission: true,
            options: [
                { id: 'offline', name: t('offline_customer') },
                { id: 'online', name: t('online_customer') },
                { id: 'supplier', name: t('supplier') },
            ],
            listKeyField: 'id',
            listValueField: 'name',
        },
        {
            label: t('district'),
            type: 'advance-search-select',
            key: 'new_district_id',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_DISTRICTS}`,
            permission: true,
        },
        {
            label: t('area'),
            type: 'advance-search-select',
            key: 'area_id',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            // The areas endpoint takes the district as a required path segment
            // (no district-less variant), so — unlike category/brand elsewhere,
            // which just appends an empty query param — this filter can't
            // resolve any fetch-url until a district is picked. Hiding it
            // until then avoids the "You should give fetch-url" toast that
            // firing it with no district produces.
            fetchUrl: computed(() => currentFilters.value.new_district_id
                ? SELECTED_AREAS(currentFilters.value.new_district_id)
                : null),
            permission: computed(() => !!currentFilters.value.new_district_id),
        },
    ],
    actions: [
        {
            title: t('history'),
            type: 'history',
            icon: markRaw(ClockRotateRight),
            modifier: () => hasAnyPermission(CONTACT_HISTORY_PERMISSIONS),
        },
        {
            title: t('view'),
            type: 'view',
            icon: markRaw(Eye),
            modifier: (row) => rowKind(row) === 'online',
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: (row) => (rowKind(row) === 'offline' && canAccess('update_customers'))
                || (rowKind(row) === 'supplier' && canAccess('update_suppliers')),
        },
        {
            title: 'Rate',
            type: 'rate',
            icon: markRaw(Star),
            modifier: (row) => (rowKind(row) === 'offline' && canAccess('update_customer_ratings'))
                || (rowKind(row) === 'supplier' && canAccess('update_supplier_ratings'))
                || (rowKind(row) === 'online' && canAccess('update_online_contacts')),
        },
        {
            title: 'Set Note',
            type: 'note',
            icon: markRaw(Notes),
            modifier: (row) => (rowKind(row) === 'offline' && canAccess('update_customer_notes'))
                || (rowKind(row) === 'supplier' && canAccess('update_supplier_notes'))
                || (rowKind(row) === 'online' && canAccess('update_online_contacts')),
        },
        {
            title: 'Price Document',
            type: 'price_document',
            icon: markRaw(Attachment),
            modifier: (row) => (rowKind(row) === 'offline' && canAccess('update_customer_price_documents'))
                || (rowKind(row) === 'supplier' && canAccess('update_supplier_price_documents'))
                || (rowKind(row) === 'online' && canAccess('update_online_contact_price_documents')),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: (row) => (rowKind(row) === 'offline' && canAccess('delete_customers'))
                || (rowKind(row) === 'supplier' && canAccess('delete_suppliers'))
                || (rowKind(row) === 'online' && canAccess('delete_online_contacts')),
        },
    ],
});

const actionTiger = (row, action) => {
    const kind = rowKind(row);

    if (action.type === 'history') {
        router.push(`/contact-history/${row.id}`);
    } else if (action.type === 'view') {
        selectedRow.value = row;
        isViewModal.value = true;
    } else if (action.type === 'edit') {
        editingType.value = kind;
        selectedData.value = `${baseUrl(row)}/${row.id}`;
        isModalActive.value = true;
    } else if (action.type === 'delete') {
        deleteUrl.value = `${baseUrl(row)}/${row.id}`;
        isDeleteModal.value = true;
    } else if (action.type === 'rate') {
        selectedRow.value = row;
        isRateModal.value = true;
    } else if (action.type === 'note') {
        selectedRow.value = row;
        isNoteModal.value = true;
    } else if (action.type === 'price_document') {
        selectedRow.value = row;
        isPriceDocumentModal.value = true;
    }
};

const closeEditModal = () => {
    isModalActive.value = false;
    editingType.value = null;
    selectedData.value = '';
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('people')" />

        <div v-if="isSupperAdmin() && deletableSelectedRows.length" class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-danger d-flex align-items-center gap-2" @click="isBulkDeleteModal = true">
                <Trash width="16" height="16" />
                {{ $t('delete') }} ({{ deletableSelectedRows.length }})
            </button>
        </div>

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_people')"
            :button-visible="canAccess('create_customers') || canAccess('create_suppliers')"
            @buttonAction="isCreateModalActive = true" @filter-change="currentFilters = $event" @action="actionTiger"
            @afterRowSelect="selectedRows = $event.rows" />

        <people-create-modal v-if="isCreateModalActive" :table-id="tableId" modal-id="people-create-modal"
            @close="isCreateModalActive = false" />

        <customer-modal v-if="isModalActive && editingType === 'offline'" :table-id="tableId"
            modal-id="people-customer-modal" :selected-url="selectedData" @close="closeEditModal" />

        <supplier-modal v-if="isModalActive && editingType === 'supplier'" :table-id="tableId"
            modal-id="people-supplier-modal" :selected-url="selectedData" @close="closeEditModal" />

        <online-customer-view-modal v-if="isViewModal" modal-id="people-online-view-modal" :customer="selectedRow"
            @close="isViewModal = false; selectedRow = null" />

        <customer-rating-modal v-if="isRateModal && rowKind(selectedRow) === 'offline'"
            modal-id="people-customer-rating-modal" :table-id="tableId" :customer="selectedRow"
            :selected-url="`${CUSTOMERS}/${selectedRow.id}/rating`" @close="isRateModal = false; selectedRow = null" />

        <supplier-rating-modal v-if="isRateModal && rowKind(selectedRow) === 'supplier'"
            modal-id="people-supplier-rating-modal" :table-id="tableId" :supplier="selectedRow"
            :selected-url="`${SUPPLIERS}/${selectedRow.id}/rating`" @close="isRateModal = false; selectedRow = null" />

        <rate-customer-modal v-if="isRateModal && rowKind(selectedRow) === 'online'"
            modal-id="people-online-rating-modal" :table-id="tableId" :customer="selectedRow"
            :selected-url="`${ONLINE_CONTACTS}/${selectedRow.id}`" @close="isRateModal = false; selectedRow = null" />

        <set-note-modal v-if="isNoteModal" modal-id="people-note-modal" :table-id="tableId" :contact="selectedRow"
            :selected-url="`${baseUrl(selectedRow)}/${selectedRow.id}${rowKind(selectedRow) === 'online' ? '' : '/note'}`"
            @close="isNoteModal = false; selectedRow = null" />

        <price-document-upload-modal v-if="isPriceDocumentModal" modal-id="people-price-document-modal"
            :table-id="tableId" :contact="selectedRow"
            :upload-url="`${baseUrl(selectedRow)}/${selectedRow.id}/price-document-chunk`"
            @close="isPriceDocumentModal = false; selectedRow = null" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <app-delete-modal v-if="isBulkDeleteModal" modal-id="people-bulk-delete-modal" :table-id="tableId"
            :bulk-url="PEOPLE_BULK_DELETE" :bulk-payload="bulkDeletePayload"
            :sub-title="`${deletableSelectedRows.length} people will be deleted permanently.`"
            @cancelled="isBulkDeleteModal = false" @confirm-success="selectedRows = []" call-back-emit />
    </div>
</template>

<style scoped lang="scss"></style>
