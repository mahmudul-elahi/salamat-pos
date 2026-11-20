<script setup>
import { ref, h, computed } from "vue";
import { useI18n } from "vue-i18n";
import {
    CLOSING_STATEMENTS,
    SELECTED_BRAND,
    SELECTED_CATEGORY, SELECTED_SHOPS,
    SELECTED_SUPPLIERS, SELECTED_PRODUCT_LIST,
    STOCK_STATEMENT_PDF, STOCK_STATEMENT_PDF_STATUS, STOCK_STATEMENT_PDF_CLEANUP
} from "@/services/endpoints/landlord.js";
import SerialNumberColumn from "@/views/landlord/common/component/SerialNumberColumn.vue";
// import QtyColumn from "@/views/landlord/common/component/QtyColumn.vue";
import { useToast } from "vue-toastification";
import Axios from "@/services/axios/index.js";
import usePermission from "@/composable/permission/usePermission.js";
import { formatDateToLocal } from "@/utils/helper.js";
import StockStatementPdfColumnsModal from "@/views/landlord/pos/statement/StockStatementPdfColumnsModal.vue";

const { canAccess, canFilterByShop } = usePermission();
const { t } = useI18n();
const toast = useToast();
const tableId = ref('stock-statement-table')
const currentFilters = ref({});

const getInitials = (name) => {
    const parts = (name || '').trim().split(' ').filter(Boolean);
    const first = parts[0] ? parts[0][0] : '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return `${first}${last}`.toUpperCase() || 'NA';
};

const avatarHtml = (name, image, bg, color) => {
    if (image) {
        return `<img src="${image}" style="width:32px;height:32px;border-radius:8px;object-fit:cover;border:1px solid #e5e7eb;" />`;
    }
    return `<span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:${bg};color:${color};font-size:11px;font-weight:600;">${getInitials(name)}</span>`;
};

const attributePillsHtml = (attributeValues) => {
    if (!attributeValues) return '';
    const values = attributeValues.split('/').map(v => v.trim()).filter(Boolean);
    if (!values.length) return '';
    const pills = values.map(v => `<span style="display:inline-block;padding:4px 12px;border-radius:999px;background:#f1f2f4;color:#4b5563;font-size:11px;font-weight:500;">${v}</span>`).join('');
    return `<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px;">${pills}</div>`;
};

const options = ref({
    url: CLOSING_STATEMENTS,
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
            title: t('sl'),
            type: 'text',
            key: 'sl',
        },
        {
            title: t('product'),
            type: 'custom-html',
            key: 'product_name',
            modifier: (name, row) => {
                const avatar = avatarHtml(name, row.product_image, '#e0f2f1', '#00a09d');
                const variant = attributePillsHtml(row.attribute_values);
                return `<div style="display:flex;align-items:center;gap:8px;"><span>${avatar}</span><div><div>${name}</div>${variant}</div></div>`;
            },
        },
        {
            title: t('brand'),
            type: 'custom-html',
            key: 'brand_name',
            modifier: (name => `<span style="white-space:nowrap;">${name ?? '-'}</span>`),
        },
        {
            title: t('category'),
            type: 'custom-html',
            key: 'category_name',
            modifier: (name => `<span style="white-space:nowrap;">${name ?? '-'}</span>`),
        },
        {
            title: t('purchase_date'),
            type: 'custom-html',
            key: 'purchase_date',
            modifier: (date => `<span style="white-space:nowrap;">${date ? formatDateToLocal(date) : '-'}</span>`),
        },
        {
            title: t('supplier'),
            type: 'custom-html',
            key: 'supplier_name',
            modifier: (name, row) => {
                if (!name || name === '-') return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(name, row.supplier_image, '#e8eaf6', '#3949ab');
                return `<div style="display:flex;align-items:center;gap:8px;"><span>${avatar}</span><span>${name}</span></div>`;
            },
        },
        {
            title: t('warehouse'),
            type: 'custom-html',
            key: 'warehouse_name',
            permission: canFilterByShop(),
            modifier: (name => `<span style="white-space:nowrap;">${name ?? '-'}</span>`),
        },
        {
            title: t('invoice_number'),
            type: 'custom-html',
            key: 'full_invoice_number',
            modifier: (invoice => `<span style="white-space:nowrap;">${invoice ?? '-'}</span>`),
        },
        {
            title: t('serial_number'),
            type: 'component',
            key: 'serial_numbers',
            componentName: h(SerialNumberColumn),
        },

        {
            title: t('in_stock'),
            type: 'object',
            accountAble: true,
            isCount: true,
            key: 'net_stock',
            modifier: (net_stock => net_stock ?? 0)
        },

    ],
    actions: [],
    filters: [
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
            label: t('category'),
            type: 'advance-search-select',
            key: 'category',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: computed(() => `${SELECTED_CATEGORY}?brand=${currentFilters.value.brand ?? ''}`),
            permission: true,
        },
        {
            label: t('product'),
            type: 'advance-search-select',
            key: 'product',
            listKeyField: 'id',
            listValueField: 'product_name',
            rowPerLoad: 10,
            fetchUrl: computed(() => `${SELECTED_PRODUCT_LIST}?category=${currentFilters.value.category ?? ''}`),
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
        {
            label: t('warehouse'),
            type: 'advance-search-select',
            key: 'warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
        },
        {
            label: t('date'),
            type: 'date-range',
            key: 'date',
            placeholder: t('select_a_range'),
            permission: true,
        },
    ]
})

const actionTiger = (row, action) => {

}

const onFilterChange = (filters) => {
    currentFilters.value = filters;
};

const preloader = ref(false);
const preloaderText = ref('');

const POLL_INTERVAL_MS = 2000;
const MAX_POLL_ATTEMPTS = 150; // ~5 minutes

const pollPdfStatus = (token, attempt = 0) => {
    if (attempt >= MAX_POLL_ATTEMPTS) {
        toast.error('Report generation is taking too long. Please try again later.');
        preloader.value = false;
        return;
    }

    setTimeout(async () => {
        try {
            const { data } = await Axios.get(`${STOCK_STATEMENT_PDF_STATUS}/${token}`);

            if (data.status === 'completed') {
                // A plain <a download> only forces a save-as dialog when the
                // browser is sure of that intent — fetching the file as a
                // blob first and downloading from an object URL guarantees
                // an actual download instead of the PDF just opening inline.
                const fileResponse = await Axios.get(data.url, { responseType: 'blob' });
                const blobUrl = URL.createObjectURL(new Blob([fileResponse.data], { type: 'application/pdf' }));
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = 'stock-statement.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(blobUrl);
                preloader.value = false;

                Axios.delete(`${STOCK_STATEMENT_PDF_CLEANUP}/${token}`).catch(() => { });
            } else if (data.status === 'failed') {
                toast.error(data.message || 'Something went wrong');
                preloader.value = false;
            } else {
                pollPdfStatus(token, attempt + 1);
            }
        } catch (error) {
            toast.error(error?.response?.data?.message || 'Something went wrong');
            preloader.value = false;
        }
    }, POLL_INTERVAL_MS);
};

const isColumnsModalActive = ref(false);

const printStockStatement = () => {
    const hasAnyFilter = currentFilters.value.brand || currentFilters.value.category
        || currentFilters.value.product || currentFilters.value.supplier
        || currentFilters.value.warehouse || currentFilters.value.date;

    if (!hasAnyFilter) {
        toast.error('Please select at least one filter');
        return;
    }

    isColumnsModalActive.value = true;
};

const generateReport = async (selectedColumns) => {
    try {
        const payload = {
            brand: currentFilters.value.brand || '',
            category: currentFilters.value.category || '',
            product: currentFilters.value.product || '',
            supplier: currentFilters.value.supplier || '',
            warehouse: currentFilters.value.warehouse || '',
            date: currentFilters.value.date || '',
            columns: selectedColumns.join(','),
        };

        preloader.value = true;
        preloaderText.value = 'Generating...';

        const { data } = await Axios.post(STOCK_STATEMENT_PDF, payload);

        pollPdfStatus(data.token);
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Something went wrong');
        preloader.value = false;
    }
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('stock_statements')" />
        <app-datatable :id="tableId" :options="options" :button-visible="canAccess('generate_stock_statement_pdf')"
            :add-button-text="preloader ? preloaderText : $t('generate_print')" @buttonAction="printStockStatement"
            @action="actionTiger" @filter-change="onFilterChange" />

        <stock-statement-pdf-columns-modal v-if="isColumnsModalActive" modal-id="stock-statement-pdf-columns-modal"
            @generate="generateReport" @close="isColumnsModalActive = false" />
    </div>
</template>
<style scoped lang="scss"></style>
