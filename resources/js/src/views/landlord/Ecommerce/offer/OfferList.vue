<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('offers')" />
        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_offer')"
            :button-visible="canAccess('create_offers')" @buttonAction="goToCreate" @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { Edit, Trash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { OFFERS } from "@/services/endpoints/Ecommerce.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('offer-table');

const options = ref({
    url: OFFERS,
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
        { title: t('title'), key: 'title', type: 'text' },
        { title: t('type'), key: 'type', type: 'text' },
        { title: t('start_at'), key: 'start_at', type: 'text' },
        { title: t('end_at'), key: 'end_at', type: 'text' },
        { title: t('priority'), key: 'priority', type: 'text' },
        { title: t('status'), key: 'is_active', type: 'custom-html', modifier: (val) => val ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_offers') || canAccess('delete_offers') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_offers') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_offers') },
    ],
});

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const router = useRouter();

function goToCreate() {
    router.push({ name: 'OfferCreate' });
}

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push({ name: 'OfferEdit', params: { id: row.id } });
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${OFFERS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>
