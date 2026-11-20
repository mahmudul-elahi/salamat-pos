<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import {
    PRODUCT_BARCODES,
    SELECTED_PRODUCT,
    SELECTED_SHOPS,
    SELECTED_SUPPLIERS,
} from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import BarcodeProductProfile from "@/views/landlord/pos/HelperComponent/BarcodeProductProfile.vue";

const { t } = useI18n();

const tableId = ref('barcode-table');


const options = ref({
    url: PRODUCT_BARCODES,
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
        // {
        //     title: t('mrp'),
        //     type: 'object',
        //     key: 'price',
        //     modifier: (price => price ? numberWithCurrencySymbol(price) : '-')
        // },


        {
            title: 'P.Price',
            type: 'custom-html',
            key: 'purchases_history',
            modifier: (values) => {
                if (!values?.length) return `-`;

                return `
        <div class="d-flex flex-column gap-1">

            ${values.map(v => `
                <div class="border-bottom p-3 bg-light">

                    <div class="fw-semibold">
                        ${numberWithCurrencySymbol(v.unit_price) ?? '-'}
                    </div>
                    <div class="text-muted small">
                        ${new Date(v.created_at)
                        .toLocaleString()}
                    </div>
                    </div>
            `).join('')}

        </div>
        `;
            }
        },
        {
            title: t('warehouse'),
            type: 'custom-html',
            key: 'status',
            modifier: (values) => {
                if (!values?.length) return `-`;

                return `
        <div class="d-flex flex-column gap-1">

            ${values.map(v => `
                <div class="border-bottom p-2 bg-light">

                    <div class="fw-semibold">${v.tenant ?? '-'}</div>


                    <div class="text-muted small">
                        ${new Date(v.updated_at)
                        .toLocaleString()}
                    </div>

                </div>
            `).join('')}

        </div>
        `;
            }
        },

    ],
    filters: [
        {
            label: t('warehouse_or_store_name'),
            type: 'advance-search-select',
            key: 'warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: true,
        },
        {
            label: t('product'),
            type: 'advance-search-select',
            key: 'product',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_PRODUCT}`,
            permission: true,
        },
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


    ]
});
// Row-level action handler
const actionTiger = (row, action) => {

};

// onMounted(fetchBarcodes);
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('barcode_list')" />
        <!-- Datatable -->
        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

    </div>
</template>
