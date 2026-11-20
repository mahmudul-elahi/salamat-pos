<script setup>
import { ref, markRaw, watch } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import { formatDateToLocal } from "@/utils/helper.js";
import { Trash, Eye } from "@iconoir/vue";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";

const { t } = useI18n();
const { canAccess } = usePermission();
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
const { isDeleteModal, deleteUrl, cancelled } = useDeleteModal();
import ApplicantDetailModal from "./ApplicantDetailModal.vue";

const tableId = ref('applicants-table');

// Configure your table options here
const options = ref({
    url: '/admin/ecommerce/applicants',
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
            title: t('name'),
            type: 'text',
            key: 'name',
        },
        {
            title: t('position'),
            type: 'object',
            key: 'career',
            modifier: (career) => career?.heading || '-'
        },
        {
            title: t('email'),
            type: 'text',
            key: 'email',
        },
        {
            title: t('phone'),
            type: 'text',
            key: 'phone',
        },
        {
            title: t('gender'),
            type: 'custom-html',
            key: 'gender',
            modifier: (gender) => {
                if (!gender) return '-';
                const colors = {
                    Male: 'success',
                    Female: 'danger'
                };
                const color = colors[gender] || 'secondary';
                return `<span class="badge-square-light bg-${color}">${gender.charAt(0).toUpperCase() + gender.slice(1)}</span>`;
            }
        },
        {
            title: t('date'),
            type: 'object',
            key: 'created_at',
            modifier: (date) => formatDateToLocal(date)
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: true
        },
    ],
    filters: [
        {
            label: t('position'),
            type: 'advance-search-select',
            key: 'career_id',
            fetchUrl: '/admin/ecommerce/careers',
            listKeyField: 'id',
            listValueField: 'heading',
            rowPerLoad: 10,
            permission: true,
        },
        {
            label: t('gender'),
            type: 'search-select',
            key: 'gender',
            options: [
                { id: 'Male', name: t('male') },
                { id: 'Female', name: t('female') },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('view_details'),
            icon: markRaw(Eye),
            type: 'view_details',
            modifier: () => true
        },

    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();

const actionTiger = (row, action) => {
    if (action.type === 'view_details') {
        isModalActive.value = true;
        selectedData.value = `/admin/ecommerce/applicants/${row.id}`;
    }
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Applicants" />

        <app-datatable :id="tableId" :options="options" :button-visible="false" @action="actionTiger" />

        <applicant-detail-modal v-if="isModalActive" :table-id="tableId" modal-id="applicant-detail-modal"
            :selected-url="selectedData" @close="closeModal" />
        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss"></style>
