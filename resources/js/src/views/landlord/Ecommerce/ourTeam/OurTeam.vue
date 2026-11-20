<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { OUR_TEAMS } from "@/services/endpoints/Ecommerce.js";
import OurTeamModal from "@/views/landlord/Ecommerce/ourTeam/OurTeamModal.vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Edit, Trash } from "@iconoir/vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('our-team-table');

const options = ref({
    url: OUR_TEAMS,
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
            title: t('name'),
            type: 'text',
            key: 'name',
        },
        {
            title: t('designation'),
            type: 'text',
            key: 'designation',
        },
        {
            title: t('about'),
            type: 'custom-html',
            key: 'about',
            modifier: (row) => {
                return row ? `<span class="d-inline-block text-truncate" style="max-width: 200px;">${row}</span>` : '-';
            }
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_our_teams") || canAccess("delete_our_teams"),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_our_teams'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_our_teams'),
        }
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${OUR_TEAMS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${OUR_TEAMS}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('our_teams')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_team_member')"
            :button-visible="canAccess('create_our_teams')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <our-team-modal v-if="isModalActive" :table-id="tableId" modal-id="our-team-modal" :selected-url="selectedData"
            @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss"></style>
