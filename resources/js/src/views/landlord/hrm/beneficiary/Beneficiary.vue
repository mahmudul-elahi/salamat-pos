<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('beneficiaries')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_beneficiary')"
            :button-visible="canAccess('create_beneficiaries')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <beneficiary-modal v-if="isModalActive" :table-id="tableId" modal-id="beneficiary-modal"
            :selected-url="selectedData" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { Edit, Trash, CheckCircle, XmarkCircle } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import Axios from "@/services/axios";
import { HRM_BENEFICIARIES } from "@/services/endpoints/hrm.js";
import BeneficiaryModal from "./BeneficiaryModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const toast = useToast();
const emitter = useEmitter();
const tableId = ref('hrm-beneficiary-table');

const options = ref({
    url: HRM_BENEFICIARIES,
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
        { title: t('name'), key: 'name', type: 'text' },
        {
            title: t('type'), key: 'type', type: 'custom-html',
            modifier: (type) => type === 'deduction'
                ? `<span class="badge bg-warning text-dark">${t('deduction')}</span>`
                : `<span class="badge bg-info text-dark">${t('allowance')}</span>`,
        },
        {
            title: t('status'), key: 'is_active', type: 'custom-html',
            modifier: (isActive) => isActive
                ? `<span class="badge bg-success">${t('active')}</span>`
                : `<span class="badge bg-danger">${t('inactive')}</span>`,
        },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_beneficiaries') || canAccess('delete_beneficiaries') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_beneficiaries') },
        { title: t('active'), type: 'active', icon: markRaw(CheckCircle), modifier: (row) => canAccess('update_beneficiaries') && !row.is_active },
        { title: t('inactive'), type: 'inactive', icon: markRaw(XmarkCircle), modifier: (row) => canAccess('update_beneficiaries') && !!row.is_active },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_beneficiaries') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const updateStatus = (row, isActive) => {
    Axios.patch(`${HRM_BENEFICIARIES}/${row.id}`, { is_active: isActive })
        .then((response) => {
            toast.success(response.data.message);
            emitter.emit("reload-" + tableId.value);
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message);
        });
};

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_BENEFICIARIES}/${row.id}`;
    } else if (action.type === 'active' || action.type === 'inactive') {
        updateStatus(row, action.type === 'active');
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_BENEFICIARIES}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>
