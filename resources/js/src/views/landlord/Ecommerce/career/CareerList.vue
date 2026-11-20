<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import { Edit, Trash, CheckCircle, XmarkCircle } from "@iconoir/vue";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import router from "@/router/index.js";
import { formatDateToLocal } from "@/utils/helper.js";
import { CAREERS, CAREERS_STATUS } from "@/services/endpoints/Ecommerce.js";
import { startCase } from "lodash";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('career-table');
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();
const toast = useToast();
const emitter = useEmitter();

const options = ref({
    url: CAREERS,
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
            title: t('heading'),
            type: 'custom-html',
            key: 'heading',
            modifier: (row) => row
                ? `<span class="d-inline-block text-truncate" style="max-width: 200px;">${row}</span>`
                : '-',
        },
        {
            title: t('type'),
            type: 'object',
            key: 'type',
            modifier: (type) => type ? startCase(type) : '-',
        },
        {
            title: t('vacancies'),
            type: 'text',
            key: 'vacancies',
        },
        {
            title: t('salary'),
            type: 'text',
            key: 'salary',
        },
        {
            title: t('deadline'),
            type: 'object',
            key: 'deadline',
            modifier: (deadline) => deadline ? formatDateToLocal(deadline) : '-',
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => {
                const isActive = status == 1;
                return `<span class="badge-square-light bg-${isActive ? 'success' : 'danger'}">${isActive ? 'Active' : 'Inactive'}</span>`;
            },
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_careers') || canAccess('delete_careers'),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_careers'),
        },
        {
            title: t('active'),
            type: 'active',
            key: 1,
            icon: markRaw(CheckCircle),
            modifier: (row) => canAccess('update_career_status') && row.status == 0,
        },
        {
            title: t('inactive'),
            type: 'inactive',
            key: 0,
            icon: markRaw(XmarkCircle),
            modifier: (row) => canAccess('update_career_status') && row.status == 1,
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_careers'),
        },
    ],
});

const manageStatus = (row, action) => {
    Axios.post(`${CAREERS_STATUS}/${row.id}`, { status: action.key })
        .then((response) => {
            toast.success(response.data.message);
            emitter.emit("reload-" + tableId.value);
        })
        .catch((error) => {
            toast.error(error.response.data.message);
        });
};

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push({ name: 'editCareer', params: { id: row.id } });
    } else if (action.type === 'active' || action.type === 'inactive') {
        manageStatus(row, action);
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${CAREERS}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('careers')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_career')"
            :button-visible="canAccess('create_careers')" @buttonAction="router.push({ name: 'createCareer' })"
            @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss"></style>
