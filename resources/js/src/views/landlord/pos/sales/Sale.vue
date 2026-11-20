<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import {
    SALES,
    SALES_PAYMENT,
    SELECTED_CUSTOMERS, SELECTED_SHOPS, SELECTED_SELLERS, SELECTED_PAYMENT_METHODS, SELECTED_BRAND
} from "@/services/endpoints/landlord.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Eye, Edit, Trash, UserCircle, DeliveryTruck, Package } from "@iconoir/vue";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import DuePaymentModal from "@/views/landlord/common/modal/DuePaymentModal.vue";
import ContactProfile from "@/views/landlord/pos/HelperComponent/ContactProfile.vue";
import SellerProfile from "@/views/landlord/pos/HelperComponent/SellerProfile.vue";
import SaleSellerModal from "@/views/landlord/pos/sales/SaleSellerModal.vue";
import SalePathaoModal from "@/views/landlord/pos/courier/SalePathaoModal.vue";
import SaleSteadfastModal from "@/views/landlord/pos/courier/SaleSteadfastModal.vue";
import SaleRedxModal from "@/views/landlord/pos/courier/SaleRedxModal.vue";
import router from "@/router/index.js";

const { t } = useI18n();
const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();


const tableId = ref('sale-table')

const paymentMethodInitials = (name) => {
    const trimmed = (name || '').trim();
    if (!trimmed) return 'NA';
    const first = trimmed[0];
    const last = trimmed[trimmed.length - 1];
    return `${first}${last}`.toUpperCase();
};

const paymentMethodLogoHtml = (pm) => {
    if (pm.logo) {
        return `<img src="${pm.logo}" style="width:22px;height:22px;border-radius:50%;object-fit:contain;border:1px solid #e5e7eb;background:#f9fafb;margin-right:5px;vertical-align:middle;flex-shrink:0;" />`;
    }
    return `<span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#e0f2f1;color:#00a09d;font-size:10px;font-weight:600;margin-right:5px;vertical-align:middle;flex-shrink:0;">${paymentMethodInitials(pm.name)}</span>`;
};

const options = ref({
    url: SALES,
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
            title: t('customer'),
            type: 'component',
            key: 'contact',
            componentName: h(ContactProfile),
        },

        {
            title: t('date'),
            type: 'text',
            key: 'date',
        },
        {
            title: t('invoice_number'),
            type: 'object',
            key: 'total_invoice',
            accountAble: true,
            isCount: true,
            modifier: ((row, value) => {
                return `${value.full_invoice_number}`
            })
        },
        {
            title: t('total_item'),
            type: 'object',
            key: 'total_item',
            accountAble: true,
            isCount: true,
            modifier: ((row, value) => {
                return `${value.total_item}`
            })
        },
        {
            title: 'S.Cost',
            type: 'object',
            key: 'shipping_cost',
            //accountAble: true,
            modifier: (shipping_cost => numberWithCurrencySymbol(shipping_cost)),
        },
        {
            title: t('total_amount'),
            type: 'object',
            key: 'grand_total',
            accountAble: true,
            modifier: (grand_total => numberWithCurrencySymbol(grand_total)),
        },
        {
            title: t('due_amount'),
            type: 'object',
            key: 'due_amount',
            accountAble: true,
            modifier: (due_amount => numberWithCurrencySymbol(due_amount)),
        },
        {
            title: t('received_amount'),
            type: 'object',
            key: 'received_amount',
            accountAble: true,
            modifier: (received_amount => numberWithCurrencySymbol(received_amount)),
        },
        {
            title: t('payment_method'),
            type: 'custom-html',
            key: 'payment_methods',
            modifier: (pms) => {
                if (!pms || !pms.length) return '<span style="color:#9ca3af;">—</span>';
                const rows = pms.map(pm => {
                    const logo = paymentMethodLogoHtml(pm);
                    return `<div style="display:flex;align-items:center;">${logo}<span>${pm.name}</span></div>`;
                }).join('');
                return `<div style="display:flex;flex-direction:column;gap:4px;">${rows}</div>`;
            }
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => {
                if (status === 'due')
                    return `<div class="mb-2"><span class="badge-square-light bg-danger text-capitalize">${status}</span></div>`;
                else if (status === 'partially_paid')
                    return `<div class="mb-2"><span class="badge-square-light bg-info text-capitalize">${status}</span></div>`;
                else
                    return `<div class="mb-2"><span class="badge-square-light bg-success text-capitalize">${status}</span></div>`;
            }
        },
        {
            title: t('seller'),
            type: 'component',
            key: 'seller',
            componentName: h(SellerProfile),
        },
        {
            title: 'Courir Order',
            type: 'custom-html',
            key: 'courier_order',
            modifier: (courier_order) => {
                if (courier_order) {
                    return `<div class="mb-2"><span class="badge-square-light bg-warning text-capitalize">${courier_order?.status}</span></div>`;
                } else {
                    return `-`;
                }
            },
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("download_sale_details") || canAccess("update_sales") || canAccess("delete_sales") || canAccess("create_sale_payment") || canAccess("update_seller_sales") || canAccess("create_pathao_order") || canAccess("create_steadfast_order") || canAccess("create_redx_order"),
        },
    ],

    filters: [
        {
            label: t('customer'),
            type: 'advance-search-select',
            key: 'customer',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_CUSTOMERS}`,
            permission: true,
        },
        {
            label: t('status'),
            type: 'search-select',
            key: 'status',
            options: [
                { id: 'paid', name: t('paid') },
                { id: 'partially_paid', name: t('partially_paid') }
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
        {
            label: t('warehouse_or_store_name'),
            type: 'advance-search-select',
            key: 'shop',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
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
            label: t('seller'),
            type: 'advance-search-select',
            key: 'seller',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SELLERS}`,
            permission: true,
        },
        {
            label: t('payment_method'),
            type: 'advance-search-select',
            key: 'payment_method',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_PAYMENT_METHODS}`,
            permission: true,
        },
    ],

    actions: [
        // {
        //     title: t('payment'),
        //     type: 'payment',
        //     icon: markRaw(HandCash),
        //     modifier: (row) => (row.status === 'due' || row.status === 'partially_paid') && canAccess('create_sale_payment'),
        // },
        {
            title: t('view_details'),
            type: 'sale_details',
            icon: markRaw(Eye),
            modifier: () => canAccess("download_sale_details"),
            getHref: (row) => `/sale-details/${row.id}`,
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: (row => row.status !== 'paid' && row.status !== 'partially_paid' && canAccess("update_sales")),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_sales'),
        },
        {
            title: t('update_seller'),
            type: 'update_seller',
            icon: markRaw(UserCircle),
            modifier: () => canAccess('update_seller_sales'),
        },
        {
            title: 'Send via Pathao',
            type: 'send_pathao',
            icon: markRaw(DeliveryTruck),
            modifier: (row) => row.status !== 'return' && canAccess('create_pathao_order'),
        },
        {
            title: 'Send via Steadfast',
            type: 'send_steadfast',
            icon: markRaw(Package),
            modifier: (row) => row.status !== 'return' && canAccess('create_steadfast_order'),
        },
        {
            title: 'Send via REDX',
            type: 'send_redx',
            icon: markRaw(Package),
            modifier: (row) => row.status !== 'return' && canAccess('create_redx_order'),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()


const saleData = ref({})
const salePaymentUrl = ref('')
const isSellerModalActive = ref(false)
const sellerModalRow = ref(null)
const isPathaoModalActive = ref(false)
const pathaoModalRow = ref(null)
const isSteadfastModalActive = ref(false)
const steadfastModalRow = ref(null)
const isRedxModalActive = ref(false)
const redxModalRow = ref(null)
const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push(`/edit-sale/${row.id}`)

    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${SALES}/${row.id}`
    } else if (action.type === 'payment') {
        isModalActive.value = true
        saleData.value = row
        salePaymentUrl.value = `${SALES_PAYMENT}/${row.id}`
    } else if (action.type === 'sale_details') {
        router.push(`/sale-details/${row.id}`)
    } else if (action.type === 'update_seller') {
        sellerModalRow.value = row
        isSellerModalActive.value = true
    } else if (action.type === 'send_pathao') {
        pathaoModalRow.value = row
        isPathaoModalActive.value = true
    } else if (action.type === 'send_steadfast') {
        steadfastModalRow.value = row
        isSteadfastModalActive.value = true
    } else if (action.type === 'send_redx') {
        redxModalRow.value = row
        isRedxModalActive.value = true
    }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('sale_list')" />

        <app-datatable :id="tableId" :options="options" :button-visible=false @action="actionTiger" />

        <due-payment-modal v-if="isModalActive" :table-id="tableId" modal-id="due-payment-modal" :row-data="saleData"
            :payment-url="salePaymentUrl" @close="closeModal" />


        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <sale-seller-modal v-if="isSellerModalActive" modal-id="sale-seller-modal" :table-id="tableId"
            :sale-id="sellerModalRow.id" :tenant-id="sellerModalRow.tenant_id"
            :current-seller-id="sellerModalRow.seller?.id" @close="isSellerModalActive = false" />

        <sale-pathao-modal v-if="isPathaoModalActive" modal-id="sale-pathao-modal" :table-id="tableId"
            :sale="pathaoModalRow" @close="isPathaoModalActive = false" />

        <sale-steadfast-modal v-if="isSteadfastModalActive" modal-id="sale-steadfast-modal" :table-id="tableId"
            :sale="steadfastModalRow" @close="isSteadfastModalActive = false" />

        <sale-redx-modal v-if="isRedxModalActive" modal-id="sale-redx-modal" :table-id="tableId"
            :sale="redxModalRow" @close="isRedxModalActive = false" />

    </div>
</template>
<style scoped lang="scss"></style>
