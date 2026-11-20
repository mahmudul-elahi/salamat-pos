<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('pre_owned_products')" />
        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_pre_owned_product')"
            :button-visible="canAccess('create_pre_owned_products')" @buttonAction="goToCreate" @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <pre-owned-care-modal v-if="isCareModalActive" modal-id="pre-owned-care-modal" :table-id="tableId"
            :pre-owned-id="selectedPreOwnedId" @close="isCareModalActive = false" />

        <pre-owned-gift-modal v-if="isGiftModalActive" modal-id="pre-owned-gift-modal" :table-id="tableId"
            :pre-owned-id="selectedPreOwnedId" :selected-url="giftSelectedUrl" @close="isGiftModalActive = false" />

        <pre-owned-price-modal v-if="isPriceModalActive" modal-id="pre-owned-price-modal" :table-id="tableId"
            :pre-owned="selectedPreOwnedRow" @close="isPriceModalActive = false" />
    </div>
</template>

<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { Edit, Trash, Check, HeartSolid, Gift, Coins } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { PRE_OWANED_PRODUCTS } from "@/services/endpoints/Ecommerce.js";
import { numberWithCurrencySymbol } from "@/utils/helper";
import ProductAttachment from "@/views/landlord/pos/product/ProductAttachment.vue";
import PreOwnedCareModal from "@/views/landlord/Ecommerce/preOwned/PreOwnedCareModal.vue";
import PreOwnedGiftModal from "@/views/landlord/Ecommerce/preOwned/PreOwnedGiftModal.vue";
import PreOwnedPriceModal from "@/views/landlord/Ecommerce/preOwned/PreOwnedPriceModal.vue";
import { GIFTS } from "@/services/endpoints/Ecommerce.js";
import Axios from "@/services/axios";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";


const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('offer-table');
const isCareModalActive = ref(false);
const isGiftModalActive = ref(false);
const isPriceModalActive = ref(false);
const giftSelectedUrl = ref(null);
const selectedPreOwnedId = ref(null);
const selectedPreOwnedRow = ref(null);

const options = ref({
    url: PRE_OWANED_PRODUCTS,
    pageSize: 10,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: true,
    filters: [
        {
            label: t('status'),
            type: 'search-select',
            key: 'is_sold',
            options: [
                { id: 'available', name: t('available') },
                { id: 'sold', name: t('sold') },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
    ],
    columns: [
        {
            title: t('name'),
            type: 'component',
            key: 'product_name',
            componentName: markRaw(ProductAttachment),
        },
        {
            title: t('variant'),
            key: 'attributes',
            type: 'text',
        },
        {
            title: t('price'),
            key: 'price',
            type: 'object',
            modifier: (val) => val ? numberWithCurrencySymbol(val) : ''
        },
        {
            title: t('serial_number'),
            key: 'serial_number',
            type: 'text'
        },
        {
            title: t('is_sold'),
            type: 'custom-html',
            key: 'is_sold',
            modifier: (value) => {

                return value ? `<span class="badge bg-danger">${t('sold')}</span>` : `<span class="badge bg-success">${t('available')}</span>`;
            }
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_status_pre_owned_products') || canAccess('update_pre_owned_products') || canAccess('delete_pre_owned_products')
        },
    ],
    actions: [
        {
            title: t('is_sold'),
            type: 'is_sold',
            icon: markRaw(Check),
            modifier: (row) => !row.is_sold && canAccess('update_status_pre_owned_products')
        },
        {
            title: t('unsold'),
            type: 'unsold',
            icon: markRaw(Check),
            modifier: (row) => row.is_sold && canAccess('update_status_pre_owned_products')
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_pre_owned_products')
        },
        {
            title: t('update_price'),
            type: 'update_price',
            icon: markRaw(Coins),
            modifier: () => canAccess('update_pre_owned_products')
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_pre_owned_products')
        },
        {
            title: 'Care',
            type: 'care',
            icon: markRaw(HeartSolid),
            modifier: () => canAccess('view_pre_owned_products_care') || canAccess('create_pre_owned_products_care'),
        }
        // {
        //     title: 'Gift',
        //     type: 'gift',
        //     icon: markRaw(Gift),
        //     modifier: () => canAccess('update_pre_owned_products'),
        // },
    ],
});

const {
    deleteUrl,
    isDeleteModal,
    cancelled
} = useDeleteModal();

const router = useRouter();

function goToCreate() {
    router.push({
        name: 'AddPreOwnedProduct'
    });
}

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push({
            name: 'editPreOwnedProduct',
            params: {
                id: row.id
            }
        });
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${PRE_OWANED_PRODUCTS}/${row.id}`;
    } else if (action.type === 'is_sold') {
        handleIsSold(row, 1);
    } else if (action.type === 'unsold') {
        handleIsSold(row, 0);
    } else if (action.type === 'care') {
        selectedPreOwnedId.value = row.id;
        isCareModalActive.value = true;
    } else if (action.type === 'update_price') {
        selectedPreOwnedRow.value = row;
        isPriceModalActive.value = true;
    } else if (action.type === 'gift') {
        selectedPreOwnedId.value = row.id;
        giftSelectedUrl.value = null;
        Axios.get(`${GIFTS}?pre_owned_id=${row.id}&per_page=1`).then(({ data }) => {
            const existing = data?.data?.[0];
            giftSelectedUrl.value = existing ? `${GIFTS}/${existing.id}` : null;
        }).catch(() => {
            giftSelectedUrl.value = null;
        }).finally(() => {
            isGiftModalActive.value = true;
        });
    }
};

const emitter = useEmitter();
const toast = useToast();


const handleIsSold = (row, status) => {
    try {
        Axios.patch(`${PRE_OWANED_PRODUCTS}/${row.id}/status`, {
            status: status
        }).then(({ data }) => {
            toast.success(data?.message);
            emitter.emit("reload-" + tableId.value);
        });
    } catch ({ response }) {
        toast.error(response?.data?.message);
    }
};
</script>

<style scoped lang="scss"></style>
