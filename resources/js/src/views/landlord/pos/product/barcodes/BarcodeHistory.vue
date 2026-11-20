<script setup>
import { h, ref } from "vue";
import { useI18n } from "vue-i18n";
import { PRODUCT_BARCODE_HISTORY, PRODUCT_SERIAL_HISTORY_DELETE, SELECTED_SUPPLIERS } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { Trash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import BarcodeProductProfile from "@/views/landlord/pos/HelperComponent/BarcodeProductProfile.vue";

const { canAccess } = usePermission();

const { t } = useI18n();

const tableId = ref('barcode-table');

// One row per actual event now (purchase/sale/sale_return/purchase_return),
// not one per warehouse - so each event needs its own label/color rather
// than a single generic "status".
const statusBadgeColor = {
    purchase: 'primary',
    sale: 'success',
    sale_return: 'warning',
    purchase_return: 'danger',
};
const statusLabel = (status) => (status ?? '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const options = ref({
    url: PRODUCT_BARCODE_HISTORY,
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
    columns: [
        {
            title: t('product_name'),
            type: 'component',
            key: 'product_name',
            componentName: h(BarcodeProductProfile),
        },
        {
            title: t('serial_number'),
            type: 'text',
            key: 'serial_number'
        },
        {
            title: t('sale_price'),
            type: 'custom-html',
            key: 'stock_history',
            modifier: (values) => {
                if (!values?.length) return `-`;

                return `
        <div class="d-flex flex-column gap-1">

            ${values.map(v => `
                <div class="border-bottom p-3 bg-light">

                    <div class="fw-semibold">
                        ${v.sale_price ? numberWithCurrencySymbol(v.sale_price) : '-'}
                    </div>
                    </div>
            `).join('')}

        </div>
        `;
            }
        },
        {
            title: t('purchase_price'),
            type: 'custom-html',
            key: 'stock_history',
            modifier: (values) => {
                if (!values?.length) return `-`;

                return `
        <div class="d-flex flex-column gap-1">

            ${values.map(v => `
                <div class="border-bottom p-3 bg-light">

                    <div class="fw-semibold">
                        ${v.purchase_price ? numberWithCurrencySymbol(v.purchase_price) : '-'}
                    </div>
                    </div>
            `).join('')}

        </div>
        `;
            }
        },
        {
            title: t('supplier'),
            type: 'custom-html',
            key: 'stock_history',
            modifier: (values) => {
                if (!values?.length) return `-`;
                return `
        <div class="d-flex flex-column gap-1">

            ${values.map(v => `
                <div class="border-bottom p-2 bg-light">

                    <div class="fw-semibold">
                        ${v.supplier ?? v.customer ?? '-'}
                        ${v.supplier ? `- <span class="badge-square-light text-capitalize bg-primary">Supplier</span>` : ''}
                        ${!v.supplier && v.customer ? `- <span class="badge-square-light text-capitalize bg-info">Customer</span>` : ''}
                    </div>


                   <div class="text-muted small">
    ${new Date(v.updated_at).toLocaleString()} - <span class="badge-square-light text-capitalize bg-info">${v.tenant}</span>
                    
</div>



                </div>
            `).join('')}

        </div>
        `;
            }
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'stock_history',
            modifier: (values) => {
                if (!values?.length) return `<span class="badge-square-light text-capitalize bg-primary">Generated</span>`;

                return `
        <div class="d-flex flex-column gap-1">

            ${values.map(v => `
                <div class="border-bottom p-2 bg-light">

                    <div class="fw-semibold">
                        ${v.tenant ?? '-'} - <span class="badge-square-light text-capitalize bg-${statusBadgeColor[v.status] ?? 'primary'}">${statusLabel(v.status)}</span>
                    </div>


                   <div class="text-muted small">
    ${new Date(v.updated_at).toLocaleString()}
</div>



                </div>
            `).join('')}

        </div>
        `;
            }
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('delete_product_barcodes')
        }
    ],
    filters: [
        {
            label: t('supplier'),
            type: 'advance-search-select',
            key: 'supplier',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SUPPLIERS}`,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: (row => row.tenant_product_serials_count < 1 && canAccess('delete_product_barcodes')),
        }
    ]
});

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${PRODUCT_SERIAL_HISTORY_DELETE}/${row.id}`
    }
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Barcode History" />
        <!-- Datatable -->
        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

    </div>
</template>
