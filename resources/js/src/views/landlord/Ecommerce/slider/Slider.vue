<script setup>
import {ref, markRaw, h} from "vue";
import {useI18n} from "vue-i18n";
import {SLIDERS, SLIDER_STATUS_CHANGE} from "@/services/endpoints/Ecommerce.js";
import SliderModal from "@/views/landlord/Ecommerce/slider/SliderModal.vue";
import {useOpenModal} from "@/composable/modal/useOpenModal.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import {formatDateToLocal} from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
import {Edit, Trash, InfoCircle, CheckCircle, MinusCircle} from "@iconoir/vue";
import AttachmentsColumn from "@/views/landlord/common/component/AttachmentsColumn.vue";
import ConfirmationModal from "@/views/global/modal/ConfirmationModal.vue";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";

const {t} = useI18n();
const {canAccess} = usePermission();
const tableId = ref('slider-table')
const toast = useToast();
const emitter = useEmitter();
const statusChangeModal = ref(false);
const statusChangeData = ref(null);

const options = ref({
    url: SLIDERS,
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
            title: t('shop'),
            type: 'object',
            key: 'tenant',
            modifier: (row => row ? row.name : '-')
        },
        {
            title: t('slider_type'),
            type: 'custom-html',
            key: 'status',
            modifier: (row) => {
                if (row === 11) {
                    return `<span class="badge bg-secondary" style="max-width: 150px;">Banner</span>`;
                } else if (row === 1) {
                    return `<span class="badge bg-success" style="max-width: 150px;">Main Slider</span>`;
                }
            }
        }, {
            title: t('heading'),
            type: 'custom-html',
            key: 'heading',
            modifier: (row) => {
                return row ? `<span class="d-inline-block text-truncate" style="max-width: 150px;">${row}</span>` : '-';
            }
        },
        {
            title: t('attachments'),
            type: 'component',
            key: 'attachments',
            componentName: h(AttachmentsColumn)
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'is_active',
            modifier: (value) => {
                if (value == 1 || value === true) {
                    return `<span class="badge-square-light bg-success text-capitalize">Active</span>`
                }
                return `<span class="badge-square-light bg-danger text-capitalize">Inactive</span>`
            }
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_sliders") || canAccess("delete_sliders") || canAccess("status_update_sliders"),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_sliders'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_sliders'),
        },
        {
            title: t('activate'),
            icon: markRaw(CheckCircle),
            type: 'active',
            modifier: (row) => !row.is_active && canAccess('status_update_sliders'),
        },
        {
            title: t('inactive'),
            icon: markRaw(MinusCircle),
            type: 'inactive',
            modifier: (row) => !!row.is_active && canAccess('status_update_sliders'),
        },
    ],
})

const {isModalActive, selectedData, closeModal} = useOpenModal()
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal()


const manageStatus = (row) => {
    Axios.patch(`${SLIDER_STATUS_CHANGE}/${row.id}`).then(({data}) => {
        toast.success(data.message);
        emitter.emit('reload-' + tableId.value);
    }).catch(({response}) => {
        if (response?.data?.message) toast.error(response.data.message);
    });
}

const openStatusChangeModal = (row) => {
    statusChangeData.value = {row, status: row.is_active ? 0 : 1};
    statusChangeModal.value = true;
};

const confirmStatusChange = () => {
    manageStatus(statusChangeData.value.row);
    statusChangeModal.value = false;
};

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${SLIDERS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${SLIDERS}/${row.id}`
    } else if (action.type === 'active' || action.type === 'inactive') {
        openStatusChangeModal(row);
    }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('sliders')"/>

        <app-datatable :id="tableId"
                       :options="options"
                       :add-button-text="$t('add_slider')"
                       :button-visible="canAccess('create_sliders')"
                       @buttonAction="isModalActive = true"
                       @action="actionTiger"/>

        <slider-modal v-if="isModalActive"
                      :table-id="tableId"
                      modal-id="slider-modal"
                      :selected-url="selectedData"
                      @close="closeModal"/>

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled"/>

        <confirmation-modal
            v-if="statusChangeModal"
            modal-id="sliderStatusChange"
            modal-class="text-warning"
            :icon="InfoCircle"
            :title="$t('are_you_sure')"
            :message="statusChangeData?.status === 0 ? $t('do_you_want_to_inactive_this') : $t('do_you_want_to_active_this')"
            :table-id="tableId"
            :preloader="false"
            :first-button-name="$t('yes')"
            :second-button-name="$t('cancel')"
            @cancel="statusChangeModal = false"
            @confirm="confirmStatusChange"/>

    </div>
</template>
<style scoped lang="scss"></style>
