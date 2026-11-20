<script setup>
import {h, ref} from "vue";
import {useI18n} from "vue-i18n";
import {
    WITHDRAWAL_REPORTS,
    SELECTED_SHOPS,
    WITHDRAWAL_REPORT_PDF, SELECTED_INCOME_CATEGORY, SELECTED_DIRECTORS,
    SELECTED_CONTACTS, SELECTED_EMPLOYEES
} from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import {numberWithCurrencySymbol} from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";

const {t} = useI18n();
const {canAccess, isSupperAdmin, canFilterByShop } = usePermission();
const toast = useToast();

const tableId = ref('withdrawal-report-table');
const currentFilters = ref({});

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
    url: WITHDRAWAL_REPORTS,
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
            title: t('date'),
            type: 'text',
            key: 'date',
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'full_invoice_number',
        },
        {
            title: t('title'),
            type: 'text',
            key: 'title',
        },
        {
            title: t('category'),
            type: 'object',
            key: 'category',
            modifier: (category => category?.name ?? '-'),
        },
        {
            title: t('party'),
            type: 'custom-html',
            key: 'contact',
            modifier: (contact) => {
                if (!contact) return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(contact.name, contact.profile_picture, '#e0f2f1', '#00a09d');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${contact.name}</span></div>`;
            },
        },
        {
            title: 'Director',
            type: 'custom-html',
            key: 'user',
            modifier: (user) => {
                if (!user) return '<span style="color:#9ca3af;">—</span>';
                const avatar = avatarHtml(user.name, user.profile_photo, '#e8eaf6', '#3949ab');
                return `<div style="display:flex;align-items:center;">${avatar}<span>${user.name}</span></div>`;
            },
        },
        {
            title: t('withdrawal'),
            type: 'object',
            accountAble: true,
            key: 'amount',
            modifier: (amount => numberWithCurrencySymbol(amount))
        },
        {
            title: t('note'),
            type: 'component',
            key: 'note',
            componentName: h(NoteColumn)
        },
        {
            title: t('warehouse'),
            type: 'object',
            key: 'tenant',
            modifier: (tenant => tenant?.name ?? '-'),
            permission: canFilterByShop(),
        },
        {
            title: 'Created By',
            type: 'object',
            key: 'created_by',
            modifier: (val) => val?.name ?? '-',
        },
    ],
    actions: [],
    filters: [
        {
            label: t('category'),
            type: 'advance-search-select',
            key: 'category',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_INCOME_CATEGORY}`,
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
            label: 'Director',
            type: 'advance-search-select',
            key: 'director',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_DIRECTORS}`,
            permission: canFilterByShop(),
        },
        {
            label: t('party'),
            type: 'advance-search-select',
            key: 'party',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_CONTACTS}`,
            permission: true,
        },
        {
            label: t('created_by'),
            type: 'advance-search-select',
            key: 'created_by',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_EMPLOYEES}`,
            permission: true,
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

const onFilterChange = (filters) => {
    currentFilters.value = filters;
};

const preloader = ref(false)

const downloadReport = async () => {
    try {
        const payload = {
            warehouse: currentFilters.value.warehouse || '',
            director: currentFilters.value.director || '',
            date: currentFilters.value.date || '',
        };

        preloader.value = true
        const response = await Axios.post(WITHDRAWAL_REPORT_PDF, payload, {
            responseType: "blob"
        });

        const file = new Blob([response.data], {type: "application/pdf"});
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);

    } catch (error) {
        if (error.response && error.response.data instanceof Blob) {
            const reader = new FileReader();
            reader.onload = () => {
                try {
                    const errorData = JSON.parse(reader.result);
                    toast.error(errorData.message || t('something_went_wrong'));
                } catch {
                    toast.error(t('something_went_wrong'));
                }
            };
            reader.readAsText(error.response.data);
        } else {
            toast.error(error?.response?.data?.message || t('something_went_wrong'));
        }
    } finally {
        preloader.value = false
    }
};

const actionTiger = (row, action) => {
    // No actions for now
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="t('withdrawal_report')"/>

        <app-datatable :id="tableId"
                       :options="options"
                       :button-visible="canAccess('download_withdrawal_report_pdf')"
                       :add-button-text="preloader ? 'Loading...' : t('report_download')"
                       @buttonAction="downloadReport"
                       @filter-change="onFilterChange"
                       @action="actionTiger"/>
    </div>
</template>

<style scoped lang="scss">
</style>
